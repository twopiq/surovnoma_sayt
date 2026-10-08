<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\GuestBlock;
use App\Models\User;
use App\TelegramBot\TelegramMessage;
use App\TelegramBot\TelegramSdkBot;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Guest murojaatlarini cheklash: qurilma (cookie), IP va email/telefon bo'yicha
 * limitlar. Limitdan oshganlar GuestBlock sifatida saqlanadi va admin blokdan
 * chiqarmaguncha yangi murojaat yubora olmaydi.
 */
class GuestRequestGuard
{
    private const HOUR = 3600;
    private const DAY = 86400;

    public const HONEYPOT_FIELD = 'website';
    public const FORM_TIME_FIELD = '_form_started';
    public const CAPTCHA_FIELD = 'cf-turnstile-response';

    public function isWhitelisted(Request $request): bool
    {
        $list = config('guest_limits.whitelist_ips', []);

        return $list !== [] && $request->ip() && IpUtils::checkIp($request->ip(), $list);
    }

    public function captchaEnabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }

    public function captchaPassed(Request $request): bool
    {
        if (! $this->captchaEnabled()) {
            return true;
        }

        $token = (string) $request->input(self::CAPTCHA_FIELD);

        if ($token === '') {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            // Cloudflare bilan aloqa bo'lmasa murojaatlar to'xtab qolmasin
            Log::warning('Turnstile tekshiruvi bajarilmadi: '.$e->getMessage());

            return true;
        }

        return $response->successful() && $response->json('success') === true;
    }

    public function isDuplicateDescription(string $description): bool
    {
        return config('guest_limits.duplicate_window_hours') > 0
            && Cache::has($this->descriptionKey($description));
    }

    public function rememberDescription(string $description): void
    {
        $hours = (int) config('guest_limits.duplicate_window_hours');

        if ($hours > 0) {
            Cache::put($this->descriptionKey($description), true, now()->addHours($hours));
        }
    }

    public function deviceId(Request $request): string
    {
        $cookieName = config('guest_limits.device_cookie');
        $deviceId = $request->cookie($cookieName);

        if (! is_string($deviceId) || ! preg_match('/^[A-Za-z0-9-]{20,64}$/', $deviceId)) {
            $deviceId = (string) Str::uuid();
            $request->cookies->set($cookieName, $deviceId);
        }

        // 2 yil saqlanadi; EncryptCookies sababli qo'lda o'zgartirib bo'lmaydi
        Cookie::queue($cookieName, $deviceId, 60 * 24 * 730, null, null, null, true, false, 'lax');

        return $deviceId;
    }

    public function formStartedToken(): string
    {
        return encrypt(now()->timestamp);
    }

    public function activeBlock(Request $request, ?string $email = null, ?string $phone = null): ?GuestBlock
    {
        $deviceId = $this->deviceId($request);
        $ip = $this->isWhitelisted($request) ? null : $request->ip();

        return GuestBlock::query()
            ->active()
            ->where(function ($query) use ($deviceId, $ip, $email, $phone) {
                $query->where('device_id', $deviceId);

                if ($ip) {
                    $query->orWhere(fn ($q) => $q->where('scope', GuestBlock::SCOPE_IP)->where('ip', $ip));
                }

                if ($email) {
                    $query->orWhere(fn ($q) => $q->where('scope', GuestBlock::SCOPE_CONTACT)->where('email', $email));
                }

                if ($phone) {
                    $query->orWhere(fn ($q) => $q->where('scope', GuestBlock::SCOPE_CONTACT)->where('phone', $phone));
                }
            })
            ->latest('blocked_at')
            ->first();
    }

    /**
     * Validatsiyadan oldin: bloklangan, bot yoki limitdan oshganmi.
     */
    public function inspect(Request $request): ?GuestBlock
    {
        if ($block = $this->activeBlock($request)) {
            $block->registerAttempt();

            return $block;
        }

        $whitelisted = $this->isWhitelisted($request);

        if (filled($request->input(self::HONEYPOT_FIELD))) {
            $scope = $whitelisted ? GuestBlock::SCOPE_DEVICE : GuestBlock::SCOPE_IP;

            return $this->block($request, $scope, 'bot', __("Yashirin maydon to'ldirilgan", [], 'uz'));
        }

        $deviceId = $this->deviceId($request);
        $ip = $request->ip();

        $checks = [
            ["guest:device:hour:{$deviceId}", config('guest_limits.device_per_hour'), GuestBlock::SCOPE_DEVICE, 'device_hourly'],
            ["guest:device:day:{$deviceId}", config('guest_limits.device_per_day'), GuestBlock::SCOPE_DEVICE, 'device_daily'],
        ];

        // Ishonchli IP lar uchun faqat qurilma limiti ishlaydi
        if (! $whitelisted) {
            $checks[] = ["guest:ip:hour:{$ip}", config('guest_limits.ip_per_hour'), GuestBlock::SCOPE_IP, 'ip_hourly'];
            $checks[] = ["guest:ip:day:{$ip}", config('guest_limits.ip_per_day'), GuestBlock::SCOPE_IP, 'ip_daily'];
        }

        foreach ($checks as [$key, $max, $scope, $reason]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return $this->block($request, $scope, $reason, __('Limit: :max ta murojaat', ['max' => $max], 'uz'));
            }
        }

        return null;
    }

    /**
     * Validatsiyadan keyin: email va telefon bo'yicha tekshiruv.
     */
    public function inspectContact(Request $request, string $email, ?string $phone): ?GuestBlock
    {
        if ($block = $this->activeBlock($request, $email, $phone)) {
            $block->registerAttempt();

            return $block;
        }

        $max = config('guest_limits.contact_per_day');

        foreach (array_filter([$this->contactKey($email), $phone ? $this->contactKey($phone) : null]) as $key) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return $this->block($request, GuestBlock::SCOPE_CONTACT, 'contact_daily', __('Limit: :max ta murojaat', ['max' => $max], 'uz'), $email, $phone);
            }
        }

        return null;
    }

    public function submittedTooFast(Request $request): bool
    {
        $minSeconds = (int) config('guest_limits.min_fill_seconds');

        if ($minSeconds <= 0) {
            return false;
        }

        try {
            $startedAt = (int) decrypt((string) $request->input(self::FORM_TIME_FIELD));
        } catch (DecryptException) {
            return true;
        }

        return now()->timestamp - $startedAt < $minSeconds;
    }

    public function recordSubmission(Request $request, string $email, ?string $phone): void
    {
        $deviceId = $this->deviceId($request);
        $ip = $request->ip();

        RateLimiter::hit("guest:device:hour:{$deviceId}", self::HOUR);
        RateLimiter::hit("guest:device:day:{$deviceId}", self::DAY);

        if (! $this->isWhitelisted($request)) {
            RateLimiter::hit("guest:ip:hour:{$ip}", self::HOUR);
            RateLimiter::hit("guest:ip:day:{$ip}", self::DAY);
        }
        RateLimiter::hit($this->contactKey($email), self::DAY);

        if ($phone) {
            RateLimiter::hit($this->contactKey($phone), self::DAY);
        }
    }

    public function unblock(GuestBlock $block, int $adminId): void
    {
        $block->update([
            'unblocked_at' => now(),
            'unblocked_by' => $adminId,
        ]);

        // Hisoblagichlarni tozalash — aks holda keyingi urinishda darhol qayta bloklanadi
        $keys = [];

        if ($block->device_id) {
            $keys[] = "guest:device:hour:{$block->device_id}";
            $keys[] = "guest:device:day:{$block->device_id}";
        }

        if ($block->scope === GuestBlock::SCOPE_IP && $block->ip) {
            $keys[] = "guest:ip:hour:{$block->ip}";
            $keys[] = "guest:ip:day:{$block->ip}";
        }

        if ($block->scope === GuestBlock::SCOPE_CONTACT) {
            if ($block->email) {
                $keys[] = $this->contactKey($block->email);
            }

            if ($block->phone) {
                $keys[] = $this->contactKey($block->phone);
            }
        }

        foreach ($keys as $key) {
            RateLimiter::clear($key);
        }
    }

    private function block(Request $request, string $scope, string $reason, string $description, ?string $email = null, ?string $phone = null): GuestBlock
    {
        $block = GuestBlock::create([
            'scope' => $scope,
            'reason' => $reason,
            'description' => $description,
            'ip' => $request->ip(),
            'device_id' => $this->deviceId($request),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'email' => $email ?? Str::lower((string) $request->input('email')) ?: null,
            'phone' => $phone ?? ($request->input('phone') ?: null),
            'blocked_at' => now(),
            'last_attempt_at' => now(),
        ]);

        $this->alertAdmins($block);

        return $block;
    }

    private function alertAdmins(GuestBlock $block): void
    {
        if (! config('guest_limits.alert_enabled') || blank(config('services.telegram_bot.token'))) {
            return;
        }

        // Ommaviy hujumda adminlarni xabarlar bilan ko'mib tashlamaslik uchun
        if (! RateLimiter::attempt('guest:block-alert', (int) config('guest_limits.alert_per_hour'), fn () => true, self::HOUR)) {
            return;
        }

        // Har bir admin o'z tilida oladi
        $message = function (?string $locale) use ($block): TelegramMessage {
            $lines = array_filter([
                __('Sabab', [], $locale).': '.$block->reasonLabel($locale),
                __('Turi', [], $locale).': '.$block->scopeLabel($locale),
                'IP: '.$block->ip,
                __('Qurilma', [], $locale).': '.$block->deviceLabel($locale),
                $block->email ? 'Email: '.$block->email : null,
                $block->phone ? __('Telefon', [], $locale).': '.$block->phone : null,
            ]);

            return new TelegramMessage(
                __('Yangi mehmon bloki: :block', ['block' => "BLOK-{$block->id}"], $locale),
                implode("\n", $lines),
                route('admin.guest-blocks.index'),
                locale: $locale,
            );
        };

        // Javob qaytgandan keyin yuboriladi — foydalanuvchi Telegramni kutmaydi
        dispatch(function () use ($message) {
            $bot = app(TelegramSdkBot::class);

            User::role(UserRole::Admin->value)
                ->get()
                ->each(function (User $admin) use ($bot, $message) {
                    foreach ($admin->telegramChatIds() as $chatId) {
                        $bot->sendMessage((string) $chatId, $message($admin->preferredLocale()));
                    }
                });
        })->afterResponse();
    }

    private function descriptionKey(string $description): string
    {
        $normalized = preg_replace('/\s+/u', ' ', Str::lower(trim($description)));

        return 'guest:description:'.sha1($normalized);
    }

    private function contactKey(string $value): string
    {
        return 'guest:contact:day:'.sha1(Str::lower(trim($value)));
    }
}

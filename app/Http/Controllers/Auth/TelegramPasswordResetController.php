<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use App\Support\PasswordResetUsers;
use App\TelegramBot\TelegramMessage;
use App\TelegramBot\TelegramSdkBot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Parolni Telegram orqali tiklash: bog'langan Telegram akkauntga 6 xonali kod yuboriladi,
 * kod to'g'ri kiritilsa odatdagi "yangi parol" sahifasiga (Laravel tiklash tokeni bilan) o'tiladi.
 */
class TelegramPasswordResetController extends Controller
{
    public const CODE_MINUTES = 10;

    public const MAX_TRIES = 5;

    public function __construct(protected AuditService $audit)
    {
    }

    public function send(Request $request, TelegramSdkBot $bot): RedirectResponse
    {
        abort_unless(filled(config('services.telegram_bot.token')), 404);

        $data = $request->validate(['login' => ['required', 'string', 'max:255']], [], ['login' => __('Email yoki login')]);
        $identifier = trim($data['login']);
        $this->throttle('tg-reset-send:'.$request->ip(), 10, 'login');

        $user = PasswordResetUsers::find($identifier);
        $chats = $user ? PasswordResetUsers::telegramChats($user) : [];

        // Bitta akkauntga daqiqasiga bittadan ko'p kod yuborilmaydi.
        if ($chats && RateLimiter::attempt('tg-reset-user:'.$user->id, 1, fn () => true, 60)) {
            $code = (string) random_int(100000, 999999);
            Cache::put($this->key($user), ['hash' => Hash::make($code), 'tries' => 0], now()->addMinutes(self::CODE_MINUTES));

            $message = new TelegramMessage(
                __('Parolni tiklash kodi', [], 'uz'),
                __(":code\n\nKod :n daqiqa amal qiladi. Uni hech kimga aytmang. Agar siz parolni tiklashni so'ramagan bo'lsangiz, bu xabarga e'tibor bermang.", ['code' => $code, 'n' => self::CODE_MINUTES], 'uz'),
            );
            foreach ($chats as $chatId) {
                $bot->sendMessage($chatId, $message);
            }

            $this->audit->log($user->id, 'auth.password_reset_code_sent', 'Parolni tiklash kodi Telegram orqali yuborildi', $user, ['ip' => $request->ip()]);
        }

        // Kod kiritish sahifasi yangilansa ham login yo'qolmasin: sessiyada saqlanadi.
        $request->session()->put('reset_login', $identifier);

        return redirect()->route('password.telegram.verify')
            ->with('status', __("Agar akkauntingizga Telegram ulangan bo'lsa, botga 6 xonali kod yuborildi."));
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        abort_unless(filled(config('services.telegram_bot.token')), 404);

        $login = old('login', session('reset_login'));

        if (! $login) {
            return redirect()->route('password.request');
        }

        return view('auth.telegram-reset-code', ['login' => $login]);
    }

    public function verify(Request $request): RedirectResponse
    {
        abort_unless(filled(config('services.telegram_bot.token')), 404);

        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string'],
        ], [], ['code' => __('Kod')]);
        $this->throttle('tg-reset-verify:'.$request->ip(), 20, 'code');

        $user = PasswordResetUsers::find($data['login']);
        $entry = $user ? Cache::get($this->key($user)) : null;
        $code = preg_replace('/\D/', '', $data['code']);

        if (! $entry || ! Hash::check($code, $entry['hash'])) {
            // Har bir kod uchun cheklangan urinish: oshsa kod bekor qilinadi.
            if ($entry && ++$entry['tries'] >= self::MAX_TRIES) {
                Cache::forget($this->key($user));
            } elseif ($entry) {
                Cache::put($this->key($user), $entry, now()->addMinutes(self::CODE_MINUTES));
            }

            throw ValidationException::withMessages(['code' => __("Kod noto'g'ri yoki muddati tugagan. Yangi kod so'rang.")])
                ->redirectTo(route('password.telegram.verify'));
        }

        Cache::forget($this->key($user));
        $request->session()->forget('reset_login');
        $this->audit->log($user->id, 'auth.password_reset_code_verified', 'Telegram kodi tasdiqlandi', $user, ['ip' => $request->ip()]);

        return redirect()->route('password.reset', ['token' => Password::createToken($user), 'email' => $user->email]);
    }

    private function key(User $user): string
    {
        return 'password-reset-code:'.$user->id;
    }

    private function throttle(string $key, int $perTenMinutes, string $field): void
    {
        if (RateLimiter::tooManyAttempts($key, $perTenMinutes)) {
            throw ValidationException::withMessages([$field => __("Juda ko'p urinish. :n daqiqadan keyin qayta urinib ko'ring.", ['n' => (int) ceil(RateLimiter::availableIn($key) / 60)])]);
        }

        RateLimiter::hit($key, 600);
    }
}

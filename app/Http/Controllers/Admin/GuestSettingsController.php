<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuestSetting;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuestSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.guest-blocks.settings', [
            'limits' => config('guest_limits'),
            'turnstileSiteKey' => config('services.turnstile.site_key'),
            'turnstileSecretSet' => filled(config('services.turnstile.secret_key')),
            'telegramConfigured' => filled(config('services.telegram_bot.token')),
            'currentIp' => $request->ip(),
        ]);
    }

    public function update(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'device_per_hour' => ['required', 'integer', 'min:1', 'max:1000'],
            'device_per_day' => ['required', 'integer', 'min:1', 'max:10000'],
            'ip_per_hour' => ['required', 'integer', 'min:1', 'max:1000'],
            'ip_per_day' => ['required', 'integer', 'min:1', 'max:10000'],
            'contact_per_day' => ['required', 'integer', 'min:1', 'max:1000'],
            'min_fill_seconds' => ['required', 'integer', 'min:0', 'max:120'],
            'post_per_minute' => ['required', 'integer', 'min:1', 'max:1000'],
            'duplicate_window_hours' => ['required', 'integer', 'min:0', 'max:720'],
            'max_files' => ['required', 'integer', 'min:0', 'max:10'],
            'max_file_size_mb' => ['required', 'numeric', 'min:0.5', 'max:20'],
            'alert_enabled' => ['nullable', 'boolean'],
            'alert_per_hour' => ['required', 'integer', 'min:1', 'max:100'],
            'whitelist_ips' => ['nullable', 'string', 'max:5000'],
            'turnstile_site_key' => ['nullable', 'string', 'max:255'],
            'turnstile_secret_key' => ['nullable', 'string', 'max:255'],
            'turnstile_clear' => ['nullable', 'boolean'],
        ], [
            'max_file_size_mb.max' => __('Fayl hajmi 20 MB dan oshmasligi kerak.'),
        ]);

        $whitelist = $this->parseIpList((string) ($data['whitelist_ips'] ?? ''));

        if ($whitelist['invalid'] !== []) {
            return back()->withInput()->withErrors([
                'whitelist_ips' => __("Noto'g'ri IP yoki tarmoq: :list", ['list' => implode(', ', $whitelist['invalid'])]),
            ]);
        }

        $values = collect($data)->only([
            'device_per_hour', 'device_per_day', 'ip_per_hour', 'ip_per_day', 'contact_per_day',
            'min_fill_seconds', 'post_per_minute', 'duplicate_window_hours', 'max_files', 'alert_per_hour',
        ])->all();

        $values['max_file_size_kb'] = (int) round($data['max_file_size_mb'] * 1024);
        $values['alert_enabled'] = $request->boolean('alert_enabled');
        $values['whitelist_ips'] = $whitelist['valid'];
        $values['turnstile_site_key'] = trim((string) ($data['turnstile_site_key'] ?? ''));

        // Secret bo'sh qoldirilsa avvalgisi saqlanadi
        if ($request->boolean('turnstile_clear')) {
            $values['turnstile_site_key'] = '';
            $values['turnstile_secret_key'] = '';
        } elseif (filled($data['turnstile_secret_key'] ?? null)) {
            $values['turnstile_secret_key'] = trim($data['turnstile_secret_key']);
        }

        GuestSetting::store($values);

        $audit->log($request->user()->id, 'guest_settings.updated', __('Mehmon himoyasi sozlamalari yangilandi', [], 'uz'), null, [
            'whitelist_ips' => $whitelist['valid'],
        ]);

        return back()->with('status', __('Sozlamalar saqlandi.'));
    }

    /**
     * @return array{valid: list<string>, invalid: list<string>}
     */
    private function parseIpList(string $raw): array
    {
        $items = array_values(array_unique(array_filter(array_map('trim', preg_split('/[\s,;]+/', $raw)))));
        $valid = [];
        $invalid = [];

        foreach ($items as $item) {
            [$ip, $mask] = array_pad(explode('/', $item, 2), 2, null);
            $isV6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
            $isIp = filter_var($ip, FILTER_VALIDATE_IP) !== false;
            $maskOk = $mask === null || (ctype_digit($mask) && (int) $mask <= ($isV6 ? 128 : 32));

            if ($isIp && $maskOk) {
                $valid[] = $item;
            } else {
                $invalid[] = $item;
            }
        }

        return ['valid' => $valid, 'invalid' => $invalid];
    }
}

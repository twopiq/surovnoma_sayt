<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Admin paneldan o'zgartiriladigan guest himoya sozlamalari.
 * Bazadagi qiymat bo'lsa .env/config dagisini almashtiradi.
 */
class GuestSetting extends Model
{
    private const CACHE_KEY = 'guest_settings.all';

    // kalit => [config yo'li, turi]
    public const FIELDS = [
        'device_per_hour' => ['guest_limits.device_per_hour', 'int'],
        'device_per_day' => ['guest_limits.device_per_day', 'int'],
        'ip_per_hour' => ['guest_limits.ip_per_hour', 'int'],
        'ip_per_day' => ['guest_limits.ip_per_day', 'int'],
        'contact_per_day' => ['guest_limits.contact_per_day', 'int'],
        'min_fill_seconds' => ['guest_limits.min_fill_seconds', 'int'],
        'post_per_minute' => ['guest_limits.post_per_minute', 'int'],
        'duplicate_window_hours' => ['guest_limits.duplicate_window_hours', 'int'],
        'max_files' => ['guest_limits.max_files', 'int'],
        'max_file_size_kb' => ['guest_limits.max_file_size_kb', 'int'],
        'alert_enabled' => ['guest_limits.alert_enabled', 'bool'],
        'alert_per_hour' => ['guest_limits.alert_per_hour', 'int'],
        'whitelist_ips' => ['guest_limits.whitelist_ips', 'list'],
        'turnstile_site_key' => ['services.turnstile.site_key', 'string'],
        'turnstile_secret_key' => ['services.turnstile.secret_key', 'secret'],
    ];

    protected $fillable = ['key', 'value'];

    public static function applyToConfig(): void
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());

        foreach ($stored as $key => $value) {
            if (! isset(self::FIELDS[$key])) {
                continue;
            }

            [$path, $type] = self::FIELDS[$key];
            config([$path => self::cast($value, $type)]);
        }
    }

    public static function store(array $values): void
    {
        foreach ($values as $key => $value) {
            [, $type] = self::FIELDS[$key];

            $value = match ($type) {
                'bool' => $value ? '1' : '0',
                'list' => json_encode(array_values($value)),
                'secret' => filled($value) ? encrypt($value) : '',
                default => (string) ($value ?? ''),
            };

            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
        static::applyToConfig();
    }

    private static function cast(?string $value, string $type): mixed
    {
        return match ($type) {
            'int' => (int) $value,
            'bool' => $value === '1',
            'list' => json_decode((string) $value, true) ?: [],
            'secret' => self::decryptOrNull($value),
            default => filled($value) ? $value : null,
        };
    }

    private static function decryptOrNull(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return decrypt($value);
        } catch (\Throwable) {
            return null;
        }
    }
}

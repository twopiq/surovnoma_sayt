<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Carbon;

/**
 * Avtomatik zahira jadvali. Qiymatlar admin paneldan (system_settings), bo'lmasa config/database-backup.php dan.
 */
class BackupSchedule
{
    public const FREQUENCIES = [
        '1h' => 'Har soatda',
        '3h' => 'Har 3 soatda',
        '6h' => 'Har 6 soatda',
        '12h' => 'Har 12 soatda',
        'daily' => 'Kuniga bir marta',
    ];

    private const HOURS = ['1h' => 1, '3h' => 3, '6h' => 6, '12h' => 12, 'daily' => 24];

    public static function enabled(): bool
    {
        return (bool) (int) SystemSetting::get('backup.enabled', config('database-backup.enabled', true) ? 1 : 0);
    }

    public static function frequency(): string
    {
        $value = (string) SystemSetting::get('backup.frequency', '6h');

        return array_key_exists($value, self::FREQUENCIES) ? $value : '6h';
    }

    /** Kunlik zahira vaqti, "HH:MM". */
    public static function dailyAt(): string
    {
        $value = (string) SystemSetting::get('backup.daily_at', '02:00');

        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : '02:00';
    }

    public static function keep(): int
    {
        return max(1, min(365, (int) SystemSetting::get('backup.keep', config('database-backup.keep', 30))));
    }

    public static function cron(): string
    {
        $frequency = self::frequency();

        if ($frequency === 'daily') {
            [$hour, $minute] = array_map('intval', explode(':', self::dailyAt()));

            return "{$minute} {$hour} * * *";
        }

        $hours = self::HOURS[$frequency];

        return $hours === 1 ? '0 * * * *' : "0 */{$hours} * * *";
    }

    public static function intervalHours(): int
    {
        return self::HOURS[self::frequency()];
    }

    /**
     * Avtomatik zahira o'z vaqtida olinyaptimi (serverda cron ishlayaptimi).
     *
     * @return 'ok'|'late'|'never'|'off'
     */
    public static function health(?Carbon $lastScheduledAt): string
    {
        if (! self::enabled()) {
            return 'off';
        }

        if (! $lastScheduledAt) {
            return 'never';
        }

        return $lastScheduledAt->gte(now()->subHours(self::intervalHours() * 2 + 1)) ? 'ok' : 'late';
    }
}

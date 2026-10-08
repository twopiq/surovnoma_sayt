<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tizim holati tekshiruvlari: har biri ['group', 'label', 'value', 'status' => ok|warn|fail|info, 'hint'].
 */
class SystemHealth
{
    public function __construct(
        protected DatabaseBackupManager $backups,
        protected LogReader $logs,
    ) {
    }

    public function checks(): array
    {
        $production = app()->environment('production');
        $checks = [];
        $add = function (string $group, string $label, string $value, string $status = 'info', ?string $hint = null) use (&$checks): void {
            $checks[] = compact('group', 'label', 'value', 'status', 'hint');
        };

        // Ilova
        $add('Ilova', 'Laravel / PHP', app()->version().' / '.PHP_VERSION, version_compare(PHP_VERSION, '8.2.0', '>=') ? 'ok' : 'fail');
        $add('Ilova', 'Muhit (APP_ENV)', (string) config('app.env'), 'info');
        $add('Ilova', 'Debug rejimi (APP_DEBUG)', config('app.debug') ? 'yoqilgan' : "o'chirilgan",
            config('app.debug') ? ($production ? 'fail' : 'warn') : 'ok',
            config('app.debug') ? "Serverda APP_DEBUG=false bo'lishi shart: xatolarda kod va sozlamalar ko'rinib qoladi." : null);
        $add('Ilova', 'Sayt manzili (APP_URL)', (string) config('app.url'), str_starts_with((string) config('app.url'), 'https://') || ! $production ? 'ok' : 'warn',
            str_starts_with((string) config('app.url'), 'https://') ? null : 'Serverda HTTPS manzil ishlating.');
        $add('Ilova', 'Konfiguratsiya / route keshi', (app()->configurationIsCached() ? 'config ✓' : 'config ✗').' · '.(app()->routesAreCached() ? 'route ✓' : 'route ✗'),
            $production && ! app()->configurationIsCached() ? 'warn' : 'ok',
            $production && ! app()->configurationIsCached() ? 'Tezlik uchun: php artisan optimize' : null);

        // Baza
        try {
            $started = microtime(true);
            DB::select('select 1');
            $ms = round((microtime(true) - $started) * 1000, 1);
            $add('Baza', 'Ulanish', config('database.default')." · {$ms} ms", 'ok');
        } catch (\Throwable $e) {
            $add('Baza', 'Ulanish', 'ulanib bo\'lmadi', 'fail', $this->logs->mask($e->getMessage()));
        }

        if (config('database.default') === 'sqlite') {
            $dbPath = (string) config('database.connections.sqlite.database');
            $add('Baza', 'Baza fayli hajmi', is_file($dbPath) ? $this->size((int) filesize($dbPath)) : '—', is_file($dbPath) ? 'info' : 'fail');
        }

        $add('Baza', "Bajarilmagan migratsiyalar", (string) $this->pendingMigrations(), $this->pendingMigrations() > 0 ? 'fail' : 'ok',
            $this->pendingMigrations() > 0 ? 'Serverda: php artisan migrate --force' : null);

        // Disk va papkalar
        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());

        if ($free !== false && $total) {
            $percent = round($free / $total * 100);
            $add('Server', "Diskda bo'sh joy", $this->size((int) $free)." ({$percent}%)", $percent < 5 ? 'fail' : ($percent < 15 ? 'warn' : 'ok'),
                $percent < 15 ? "Disk to'lib qolmoqda: eski loglar va zahiralarni tozalang." : null);
        }

        foreach (['storage/logs' => storage_path('logs'), 'storage/app' => storage_path('app'), 'bootstrap/cache' => base_path('bootstrap/cache')] as $label => $path) {
            $writable = is_dir($path) && is_writable($path);
            $add('Server', "Yozish huquqi: {$label}", $writable ? 'bor' : "yo'q", $writable ? 'ok' : 'fail', $writable ? null : 'Papka egasi/huquqlarini tekshiring (www-data).');
        }

        // Fon jarayonlari
        $heartbeat = SystemSetting::get('scheduler.heartbeat');
        $heartbeatAt = $heartbeat ? Carbon::parse($heartbeat) : null;
        $add('Fon jarayonlari', 'Rejalashtiruvchi (cron)', $heartbeatAt ? $heartbeatAt->diffForHumans() : "hali ishlamagan",
            $heartbeatAt && $heartbeatAt->gte(now()->subMinutes(5)) ? 'ok' : 'fail',
            $heartbeatAt && $heartbeatAt->gte(now()->subMinutes(5)) ? null : "Serverda cron'ga qo'shing: * * * * * php artisan schedule:run — busiz zahira, deadline ogohlantirishlari ishlamaydi.");

        $add('Fon jarayonlari', 'Navbat drayveri', (string) config('queue.default'), 'info');

        if (Schema::hasTable('jobs')) {
            $pending = DB::table('jobs')->count();
            $oldest = DB::table('jobs')->min('created_at');
            $stuck = $oldest && Carbon::createFromTimestamp((int) $oldest)->lt(now()->subMinutes(10));
            $add('Fon jarayonlari', 'Navbatdagi vazifalar', (string) $pending, $stuck ? 'warn' : 'ok',
                $stuck ? "Vazifalar 10 daqiqadan ko'p kutyapti — queue:work ishlayotganini tekshiring." : null);
        }

        if (Schema::hasTable('failed_jobs')) {
            $failed = DB::table('failed_jobs')->count();
            $add('Fon jarayonlari', 'Xato bergan vazifalar', (string) $failed, $failed > 0 ? 'warn' : 'ok',
                $failed > 0 ? "Ko'rish: php artisan queue:failed · qayta: php artisan queue:retry all" : null);
        }

        if ($this->backups->isSupported()) {
            $latest = $this->backups->latest();
            $latestAt = $latest ? Carbon::parse($latest['modified_at']) : null;
            $add('Fon jarayonlari', 'Oxirgi zahira', $latestAt ? $latestAt->format('d.m.Y H:i').' ('.$latestAt->diffForHumans().')' : "yo'q",
                ! $latestAt ? 'fail' : ($latestAt->lt(now()->subDays(2)) ? 'warn' : 'ok'),
                ! $latestAt || $latestAt->lt(now()->subDays(2)) ? 'Sozlamalar → Zahira nusxalar sahifasini tekshiring.' : null);
        }

        // Integratsiyalar va xavfsizlik
        $mailer = (string) config('mail.default');
        $add('Integratsiyalar', 'Pochta', $mailer, in_array($mailer, ['log', 'array'], true) ? 'warn' : 'ok',
            in_array($mailer, ['log', 'array'], true) ? "Xatlar yuborilmaydi (log): parolni tiklash ishlamaydi. MAIL_MAILER=smtp sozlang." : null);
        $telegram = filled(config('services.telegram_bot.token'));
        $add('Integratsiyalar', 'Telegram bot', $telegram ? '@'.ltrim((string) config('services.telegram_bot.username'), '@') : 'sozlanmagan', $telegram ? 'ok' : 'warn');
        $add('Integratsiyalar', 'Telegram webhook maxfiy kaliti', filled(config('services.telegram_bot.webhook_secret')) ? 'bor' : "yo'q",
            filled(config('services.telegram_bot.webhook_secret')) ? 'ok' : ($production ? 'warn' : 'info'),
            filled(config('services.telegram_bot.webhook_secret')) ? null : 'TELEGRAM_WEBHOOK_SECRET qo\'ying — aks holda botga soxta xabar yuborish mumkin.');
        $add('Xavfsizlik', 'Sessiya cookie (secure)', config('session.secure') ? 'faqat HTTPS' : "o'chiq", config('session.secure') || ! $production ? 'ok' : 'warn');
        $add('Xavfsizlik', 'Sessiya shifrlash', config('session.encrypt') ? 'yoqilgan' : "o'chiq", config('session.encrypt') ? 'ok' : 'info');

        $counts = $this->logs->recentCounts(24);
        $errors = ($counts['error'] ?? 0) + ($counts['critical'] ?? 0) + ($counts['alert'] ?? 0) + ($counts['emergency'] ?? 0);
        $add('Loglar', 'Xatolar (oxirgi 24 soat)', (string) $errors, $errors > 20 ? 'fail' : ($errors > 0 ? 'warn' : 'ok'),
            $errors > 0 ? 'Tafsilotlar: Loglar → Server loglari.' : null);
        $add('Loglar', 'Ogohlantirishlar (oxirgi 24 soat)', (string) ($counts['warning'] ?? 0), 'info');

        return $checks;
    }

    private function pendingMigrations(): int
    {
        static $pending = null;

        if ($pending !== null) {
            return $pending;
        }

        try {
            $files = collect(glob(database_path('migrations/*.php')) ?: [])->map(fn ($file) => basename($file, '.php'));
            $ran = Schema::hasTable('migrations') ? DB::table('migrations')->pluck('migration') : collect();

            return $pending = $files->diff($ran)->count();
        } catch (\Throwable) {
            return $pending = 0;
        }
    }

    private function size(int $bytes): string
    {
        return match (true) {
            $bytes >= 1073741824 => round($bytes / 1073741824, 1).' GB',
            $bytes >= 1048576 => round($bytes / 1048576, 1).' MB',
            default => max(1, round($bytes / 1024)).' KB',
        };
    }
}

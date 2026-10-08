<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Baza zahirasi va tiklash.
 *  - SQLite: butun baza fayli (`.sqlite`, VACUUM INTO bilan izchil nusxa).
 *  - MySQL/MariaDB/PostgreSQL: mantiqiy zahira (`.json.gz`) — barcha jadvallar ma'lumoti, tashqi dastursiz
 *    (mysqldump/pg_dump va exec() shart emas). Jadval tuzilmasi migratsiyalardan olinadi.
 */
class DatabaseBackupManager
{
    public const SUPPORTED_DRIVERS = ['sqlite', 'mysql', 'mariadb', 'pgsql'];

    private const FORMAT = 'rtt-db-backup';

    /** Zahiraga kirmaydigan vaqtinchalik jadvallar. */
    private const EXCLUDED_TABLES = [
        'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches',
        'telescope_entries', 'telescope_entries_tags', 'telescope_monitoring',
    ];

    private const CHUNK = 250;

    /**
     * @param  'auto'|'file'|'json'  $format  auto: SQLite → fayl, boshqalar → json
     */
    public function backup(?string $label = null, ?int $keep = null, string $format = 'auto'): array
    {
        $this->ensureSupported();

        $backupDirectory = $this->backupDirectory();
        File::ensureDirectoryExists($backupDirectory);

        $useFile = $format === 'file' || ($format === 'auto' && $this->driver() === 'sqlite');
        $base = $backupDirectory.DIRECTORY_SEPARATOR.$this->sanitizeLabel($label ?: 'backup').'-'.now()->format('Ymd-His');

        if ($useFile) {
            $databasePath = $this->sqlitePath();

            if (! File::exists($databasePath)) {
                throw new RuntimeException("Baza fayli topilmadi: {$databasePath}");
            }

            $backupPath = $base.'.sqlite';
            $this->snapshot($databasePath, $backupPath);
        } else {
            $backupPath = $base.'.json.gz';
            $this->dumpLogical($backupPath);
        }

        $this->prune($keep ?? config('database-backup.keep', 30));

        return [
            'path' => $backupPath,
            'size' => File::size($backupPath),
        ];
    }

    public function restore(string $fileName, bool $createSafetyBackup = true): array
    {
        $this->ensureSupported();
        $backupPath = $this->resolveBackupPath($fileName);
        $isLogical = str_ends_with($backupPath, '.json.gz');

        if ($isLogical) {
            $this->readHeader($backupPath); // format tekshiruvi
        } elseif ($this->driver() !== 'sqlite') {
            throw new RuntimeException('SQLite fayl zahirasini '.$this->driver().' bazasiga tiklab bo‘lmaydi. .json.gz zahirani tanlang.');
        } elseif (! $this->isSqliteFile($backupPath)) {
            throw new RuntimeException("Zahira fayli buzilgan yoki SQLite bazasi emas: {$fileName}");
        }

        $safetyBackup = $createSafetyBackup ? $this->backup('pre-restore', null, $isLogical ? 'json' : 'auto') : null;

        if ($isLogical) {
            $this->restoreLogical($backupPath);
        } else {
            $databasePath = $this->sqlitePath();

            DB::purge(config('database.default'));
            DB::disconnect(config('database.default'));

            if (! File::copy($backupPath, $databasePath)) {
                throw new RuntimeException('Baza faylini tiklab bo‘lmadi.');
            }

            DB::purge(config('database.default'));
        }

        return [
            'restored_from' => $backupPath,
            'safety_backup' => $safetyBackup['path'] ?? null,
        ];
    }

    public function list(): Collection
    {
        $backupDirectory = $this->backupDirectory();

        if (! File::isDirectory($backupDirectory)) {
            return collect();
        }

        return collect(File::files($backupDirectory))
            ->filter(fn ($file) => $this->isBackupName($file->getFilename()))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values()
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'path' => $file->getRealPath(),
                'size' => $file->getSize(),
                'modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ]);
    }

    public function latest(): ?array
    {
        return $this->list()->first();
    }

    /** Zahira faylining to'liq yo'li (faqat zahira papkasi ichidan). */
    public function pathFor(string $fileName): string
    {
        return $this->resolveBackupPath($fileName);
    }

    public function delete(string $fileName): void
    {
        File::delete($this->resolveBackupPath($fileName));
    }

    public function isSupported(): bool
    {
        return in_array($this->driver(), self::SUPPORTED_DRIVERS, true);
    }

    public function driver(): string
    {
        return (string) DB::connection(config('database.default'))->getDriverName();
    }

    // ---------------------------------------------------------------- mantiqiy zahira (json.gz)

    protected function dumpLogical(string $path): void
    {
        $tables = $this->orderedTables();
        $gz = gzopen($path, 'wb6');

        if (! $gz) {
            throw new RuntimeException('Zahira faylini yaratib bo‘lmadi: '.$path);
        }

        try {
            gzwrite($gz, json_encode([
                'format' => self::FORMAT,
                'version' => 1,
                'driver' => $this->driver(),
                'created_at' => now()->toIso8601String(),
                'tables' => $tables,
            ], JSON_UNESCAPED_UNICODE)."\n");

            foreach ($tables as $table) {
                gzwrite($gz, json_encode(['t' => $table])."\n");

                foreach (DB::table($table)->cursor() as $row) {
                    gzwrite($gz, json_encode(['r' => $this->encodeRow((array) $row)], JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION)."\n");
                }
            }
        } catch (\Throwable $e) {
            gzclose($gz);
            File::delete($path);

            throw new RuntimeException('Zahira olishda xato: '.$e->getMessage(), 0, $e);
        }

        gzclose($gz);
    }

    protected function restoreLogical(string $path): void
    {
        $header = $this->readHeader($path);
        $existing = $this->orderedTables();
        $tables = array_values(array_intersect($header['tables'] ?? [], $existing));
        $columns = collect($tables)->mapWithKeys(fn (string $table) => [$table => array_flip(Schema::getColumnListing($table))])->all();
        $driver = $this->driver();
        $connection = DB::connection(config('database.default'));

        if ($driver === 'sqlite') {
            $connection->statement('PRAGMA foreign_keys = OFF');
        }

        try {
            $connection->transaction(function () use ($connection, $driver, $path, $tables, $columns, $existing): void {
                if (in_array($driver, ['mysql', 'mariadb'], true)) {
                    $connection->statement('SET FOREIGN_KEY_CHECKS=0');
                }

                // Tozalash: bolalar jadvallaridan boshlab (FK buzilmasin)
                if ($driver === 'pgsql' && $tables !== []) {
                    $connection->statement('TRUNCATE '.collect($tables)->map(fn ($t) => '"'.$t.'"')->implode(', ').' RESTART IDENTITY CASCADE');
                } else {
                    foreach (array_reverse($tables) as $table) {
                        DB::table($table)->delete();
                    }
                }

                $gz = gzopen($path, 'rb');
                gzgets($gz); // sarlavha
                $table = null;
                $buffer = [];

                $flush = function () use (&$buffer, &$table): void {
                    if ($table !== null && $buffer !== []) {
                        DB::table($table)->insert($buffer);
                    }

                    $buffer = [];
                };

                while (($line = gzgets($gz)) !== false) {
                    $item = json_decode($line, true);

                    if (isset($item['t'])) {
                        $flush();
                        $table = in_array($item['t'], $tables, true) ? $item['t'] : null;

                        continue;
                    }

                    if ($table === null || ! isset($item['r'])) {
                        continue;
                    }

                    // Faqat hozirgi jadvalda mavjud ustunlar
                    $buffer[] = array_intersect_key($this->decodeRow($item['r']), $columns[$table]);

                    if (count($buffer) >= self::CHUNK) {
                        $flush();
                    }
                }

                $flush();
                gzclose($gz);

                if (in_array($driver, ['mysql', 'mariadb'], true)) {
                    $connection->statement('SET FOREIGN_KEY_CHECKS=1');
                }

                if ($driver === 'pgsql') {
                    foreach ($tables as $t) {
                        if (isset($columns[$t]['id'])) {
                            $connection->statement(
                                "SELECT setval(pg_get_serial_sequence('\"{$t}\"', 'id'), COALESCE(MAX(id), 1), MAX(id) IS NOT NULL) FROM \"{$t}\""
                            );
                        }
                    }
                }
            });
        } finally {
            if ($driver === 'sqlite') {
                $connection->statement('PRAGMA foreign_keys = ON');
            }
        }
    }

    protected function readHeader(string $path): array
    {
        $gz = @gzopen($path, 'rb');
        $header = $gz ? json_decode((string) gzgets($gz), true) : null;

        if ($gz) {
            gzclose($gz);
        }

        if (! is_array($header) || ($header['format'] ?? null) !== self::FORMAT) {
            throw new RuntimeException('Zahira fayli buzilgan yoki noma’lum formatda: '.basename($path));
        }

        return $header;
    }

    /** Jadvallar FK bog'lanishlari bo'yicha tartiblangan: avval ota jadvallar. */
    protected function orderedTables(): array
    {
        $driver = $this->driver();
        $tables = collect(Schema::getTables())
            ->when($driver === 'pgsql', fn (Collection $items) => $items->filter(fn ($t) => in_array($t['schema'] ?? 'public', ['public', null], true)))
            ->pluck('name')
            ->reject(fn (string $name) => in_array($name, self::EXCLUDED_TABLES, true) || str_starts_with($name, 'sqlite_'))
            ->values()
            ->all();

        $deps = [];

        foreach ($tables as $table) {
            $deps[$table] = collect(Schema::getForeignKeys($table))
                ->pluck('foreign_table')
                ->filter(fn ($parent) => $parent !== $table && in_array($parent, $tables, true))
                ->unique()
                ->values()
                ->all();
        }

        $ordered = [];

        while ($deps !== []) {
            $ready = array_keys(array_filter($deps, fn (array $parents) => array_diff($parents, $ordered) === []));

            if ($ready === []) { // aylanma bog'lanish — qolganini o'z tartibida
                $ready = array_keys($deps);
            }

            foreach ($ready as $table) {
                $ordered[] = $table;
                unset($deps[$table]);
            }
        }

        return $ordered;
    }

    protected function encodeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_resource($value)) {
                $value = stream_get_contents($value);
            }

            if (is_string($value) && ! mb_check_encoding($value, 'UTF-8')) {
                $value = ['__b64' => base64_encode($value)];
            }

            $row[$key] = $value;
        }

        return $row;
    }

    protected function decodeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_array($value) && array_key_exists('__b64', $value)) {
                $row[$key] = base64_decode($value['__b64']);
            }
        }

        return $row;
    }

    // ---------------------------------------------------------------- SQLite fayl zahirasi

    /**
     * Izchil nusxa: SQLite «VACUUM INTO» ishlab turgan bazadan tranzaksiya bo'yicha butun nusxa oladi
     * (oddiy fayl nusxasi yozish paytida buzilishi mumkin). Eski SQLite bo'lsa — fayl nusxasi.
     */
    protected function snapshot(string $databasePath, string $backupPath): void
    {
        try {
            DB::connection(config('database.default'))
                ->statement("VACUUM INTO '".str_replace("'", "''", $backupPath)."'");

            if (File::exists($backupPath)) {
                return;
            }
        } catch (\Throwable) {
            File::delete($backupPath);
        }

        DB::disconnect(config('database.default'));

        if (! File::copy($databasePath, $backupPath)) {
            throw new RuntimeException('Baza zahirasini yaratib bo‘lmadi.');
        }
    }

    protected function isSqliteFile(string $path): bool
    {
        $handle = @fopen($path, 'rb');

        if (! $handle) {
            return false;
        }

        $header = fread($handle, 16);
        fclose($handle);

        return $header === "SQLite format 3\0";
    }

    // ---------------------------------------------------------------- umumiy

    protected function prune(int $keep): void
    {
        if ($keep < 1) {
            return;
        }

        $this->list()
            ->slice($keep)
            ->each(fn (array $backup) => File::delete($backup['path']));
    }

    protected function ensureSupported(): void
    {
        if (! $this->isSupported()) {
            throw new RuntimeException('Bu baza turi ('.$this->driver().') uchun zahira qo‘llab-quvvatlanmaydi.');
        }
    }

    protected function sqlitePath(): string
    {
        $databasePath = config('database.connections.sqlite.database');

        if (! is_string($databasePath) || $databasePath === '' || $databasePath === ':memory:') {
            throw new RuntimeException('Sqlite baza fayli sozlanmagan.');
        }

        return $databasePath;
    }

    protected function backupDirectory(): string
    {
        $path = config('database-backup.path');

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Backup papkasi sozlanmagan.');
        }

        return $path;
    }

    protected function isBackupName(string $name): bool
    {
        return str_ends_with($name, '.sqlite') || str_ends_with($name, '.json.gz');
    }

    protected function resolveBackupPath(string $fileName): string
    {
        if ($fileName === 'latest') {
            $latest = $this->latest();

            if (! $latest) {
                throw new RuntimeException('Restore uchun backup topilmadi.');
            }

            return $latest['path'];
        }

        $candidate = $this->backupDirectory().DIRECTORY_SEPARATOR.basename($fileName);

        if (! $this->isBackupName($candidate) || ! File::exists($candidate)) {
            throw new RuntimeException("Backup fayli topilmadi: {$fileName}");
        }

        return $candidate;
    }

    protected function sanitizeLabel(string $label): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9_-]+/', '-', $label) ?: 'backup';

        return trim($sanitized, '-');
    }
}

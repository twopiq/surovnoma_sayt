<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class DatabaseBackupManager
{
    public function backup(?string $label = null, ?int $keep = null): array
    {
        $databasePath = $this->databasePath();
        $backupDirectory = $this->backupDirectory();

        if (! File::exists($databasePath)) {
            throw new RuntimeException("Baza fayli topilmadi: {$databasePath}");
        }

        File::ensureDirectoryExists($backupDirectory);

        $safeLabel = $this->sanitizeLabel($label ?: 'backup');
        $timestamp = now()->format('Ymd-His');
        $backupPath = $backupDirectory.DIRECTORY_SEPARATOR."{$safeLabel}-{$timestamp}.sqlite";

        $this->snapshot($databasePath, $backupPath);

        $this->prune($keep ?? config('database-backup.keep', 30));

        return [
            'path' => $backupPath,
            'size' => File::size($backupPath),
        ];
    }

    public function restore(string $fileName, bool $createSafetyBackup = true): array
    {
        $databasePath = $this->databasePath();
        $backupPath = $this->resolveBackupPath($fileName);

        if (! $this->isSqliteFile($backupPath)) {
            throw new RuntimeException("Zahira fayli buzilgan yoki SQLite bazasi emas: {$fileName}");
        }

        $safetyBackup = null;

        if ($createSafetyBackup && File::exists($databasePath)) {
            $safetyBackup = $this->backup('pre-restore', null);
        }

        DB::purge(config('database.default'));
        DB::disconnect(config('database.default'));

        if (! File::copy($backupPath, $databasePath)) {
            throw new RuntimeException('Baza faylini tiklab bo‘lmadi.');
        }

        DB::purge(config('database.default'));

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
            ->filter(fn ($file) => $file->getExtension() === 'sqlite')
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
        return config('database.default') === 'sqlite';
    }

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

    protected function prune(int $keep): void
    {
        if ($keep < 1) {
            return;
        }

        $this->list()
            ->slice($keep)
            ->each(fn (array $backup) => File::delete($backup['path']));
    }

    protected function databasePath(): string
    {
        if (config('database.default') !== 'sqlite') {
            throw new RuntimeException('Hozircha faqat sqlite baza uchun backup/restore qo‘llab-quvvatlanadi.');
        }

        $databasePath = config('database.connections.sqlite.database');

        if (! is_string($databasePath) || $databasePath === '') {
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

    protected function resolveBackupPath(string $fileName): string
    {
        $backupDirectory = $this->backupDirectory();

        if ($fileName === 'latest') {
            $latest = $this->latest();

            if (! $latest) {
                throw new RuntimeException('Restore uchun backup topilmadi.');
            }

            return $latest['path'];
        }

        $candidate = $backupDirectory.DIRECTORY_SEPARATOR.basename($fileName);

        if (! str_ends_with($candidate, '.sqlite') || ! File::exists($candidate)) {
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

<?php

namespace App\Console\Commands;

use App\Support\DatabaseBackupManager;
use Illuminate\Console\Command;
use Throwable;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'db:backup {--label=manual : Backup nomi uchun qisqa label} {--keep= : Nechta backup saqlab qolish kerak}';

    protected $description = 'Sqlite bazaning zahira nusxasini yaratadi';

    public function handle(DatabaseBackupManager $manager): int
    {
        try {
            $backup = $manager->backup(
                label: $this->option('label') ?: 'manual',
                keep: $this->option('keep') !== null ? (int) $this->option('keep') : null,
            );
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Backup yaratildi.');
        $this->line("Fayl: {$backup['path']}");
        $this->line("Hajm: {$backup['size']} bayt");

        return self::SUCCESS;
    }
}

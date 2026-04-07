<?php

namespace App\Console\Commands;

use App\Support\DatabaseBackupManager;
use Illuminate\Console\Command;

class ListDatabaseBackupsCommand extends Command
{
    protected $signature = 'db:backup-list';

    protected $description = 'Mavjud baza backup fayllarini ko‘rsatadi';

    public function handle(DatabaseBackupManager $manager): int
    {
        $backups = $manager->list();

        if ($backups->isEmpty()) {
            $this->warn('Hozircha backup fayllari yo‘q.');

            return self::SUCCESS;
        }

        $this->table(
            ['Nomi', 'Vaqti', 'Hajmi', 'Yo‘li'],
            $backups->map(fn (array $backup) => [
                $backup['name'],
                $backup['modified_at'],
                $backup['size'],
                $backup['path'],
            ])->all(),
        );

        return self::SUCCESS;
    }
}

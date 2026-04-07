<?php

namespace App\Console\Commands;

use App\Support\DatabaseBackupManager;
use Illuminate\Console\Command;
use Throwable;

class RestoreDatabaseCommand extends Command
{
    protected $signature = 'db:restore {file=latest : Backup fayl nomi yoki latest} {--no-safety-backup : Restore oldidan qo‘shimcha safety-backup olmaslik} {--force : Tasdiqsiz restore qilish}';

    protected $description = 'Sqlite bazani backup fayldan tiklaydi';

    public function handle(DatabaseBackupManager $manager): int
    {
        $file = (string) $this->argument('file');

        if (! $this->option('force') && ! $this->confirm("Rostdan ham {$file} dan bazani tiklaysizmi?")) {
            $this->warn('Restore bekor qilindi.');

            return self::INVALID;
        }

        try {
            $result = $manager->restore(
                fileName: $file,
                createSafetyBackup: ! $this->option('no-safety-backup'),
            );
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Baza tiklandi.');
        $this->line("Restore manbasi: {$result['restored_from']}");

        if ($result['safety_backup']) {
            $this->line("Safety backup: {$result['safety_backup']}");
        }

        return self::SUCCESS;
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseBackupCommandsTest extends TestCase
{
    protected string $databasePath;

    protected string $backupPath;

    protected string $originalDefaultConnection;

    protected mixed $originalSqliteDatabase;

    protected mixed $originalBackupPath;

    protected mixed $originalBackupKeep;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databasePath = database_path('testing-backup.sqlite');
        $this->backupPath = storage_path('app/testing-backups');
        $this->originalDefaultConnection = (string) config('database.default');
        $this->originalSqliteDatabase = config('database.connections.sqlite.database');
        $this->originalBackupPath = config('database-backup.path');
        $this->originalBackupKeep = config('database-backup.keep');

        File::delete($this->databasePath);
        File::deleteDirectory($this->backupPath);
        File::ensureDirectoryExists(dirname($this->databasePath));
        File::ensureDirectoryExists($this->backupPath);
        File::put($this->databasePath, '');

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', $this->databasePath);
        config()->set('database-backup.path', $this->backupPath);
        config()->set('database-backup.keep', 5);

        DB::purge('sqlite');
        Schema::connection('sqlite')->create('sample_items', function ($table) {
            $table->id();
            $table->string('name');
        });

        DB::connection('sqlite')->table('sample_items')->insert([
            ['name' => 'before-backup'],
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        config()->set('database.default', $this->originalDefaultConnection);
        config()->set('database.connections.sqlite.database', $this->originalSqliteDatabase);
        config()->set('database-backup.path', $this->originalBackupPath);
        config()->set('database-backup.keep', $this->originalBackupKeep);
        DB::purge($this->originalDefaultConnection);
        File::delete($this->databasePath);
        File::deleteDirectory($this->backupPath);

        parent::tearDown();
    }

    public function test_database_can_be_backed_up_and_restored(): void
    {
        Artisan::call('db:backup', [
            '--label' => 'test',
            '--keep' => 5,
        ]);

        $backupFile = collect(File::files($this->backupPath))->first();

        $this->assertNotNull($backupFile);

        DB::connection('sqlite')->table('sample_items')->truncate();
        DB::connection('sqlite')->table('sample_items')->insert([
            ['name' => 'after-change'],
        ]);

        $this->assertSame(1, DB::connection('sqlite')->table('sample_items')->count());
        $this->assertSame('after-change', DB::connection('sqlite')->table('sample_items')->value('name'));

        Artisan::call('db:restore', [
            'file' => $backupFile->getFilename(),
            '--force' => true,
        ]);

        $this->assertSame(1, DB::connection('sqlite')->table('sample_items')->count());
        $this->assertSame('before-backup', DB::connection('sqlite')->table('sample_items')->value('name'));
    }
}

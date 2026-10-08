<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Support\BackupSchedule;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminBackupPageTest extends TestCase
{
    use RefreshDatabase;

    private string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupPath = storage_path('app/testing-admin-backups');
        File::deleteDirectory($this->backupPath);
        config()->set('database-backup.path', $this->backupPath);
        $this->seed(DatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupPath);

        parent::tearDown();
    }

    public function test_admin_manages_backup_settings_and_files(): void
    {
        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.backups.index'))
            ->assertOk()
            ->assertSee('Zahira nusxalar')
            ->assertSee('Avtomatik zahira');

        $this->actingAs($admin)->put(route('admin.backups.settings'), [
            'enabled' => '1',
            'frequency' => 'daily',
            'daily_at' => '03:30',
            'keep' => 14,
        ])->assertRedirect();

        $this->assertSame('30 3 * * *', BackupSchedule::cron());
        $this->assertSame(14, BackupSchedule::keep());
        $this->assertTrue(BackupSchedule::enabled());

        SystemSetting::put('backup.frequency', '3h');
        $this->assertSame('0 */3 * * *', BackupSchedule::cron());

        // Testlar xotiradagi sqlite'da ishlaydi — fayl zahirasini qo'lda yaratamiz
        File::ensureDirectoryExists($this->backupPath);
        File::put($this->backupPath.'/manual-20261008-120000.sqlite', "SQLite format 3\0".str_repeat('x', 100));

        $this->actingAs($admin)->get(route('admin.backups.index'))->assertSee('manual-20261008-120000.sqlite');
        $this->actingAs($admin)->get(route('admin.backups.download', 'manual-20261008-120000.sqlite'))->assertOk()->assertDownload();
        $this->actingAs($admin)->get(route('admin.backups.download', '..env'))->assertNotFound();

        $this->actingAs($admin)
            ->post(route('admin.backups.restore', 'manual-20261008-120000.sqlite'), ['password' => 'xato-parol'])
            ->assertSessionHasErrors('restore');

        $this->actingAs($admin)->delete(route('admin.backups.destroy', 'manual-20261008-120000.sqlite'))->assertRedirect();
        $this->assertFileDoesNotExist($this->backupPath.'/manual-20261008-120000.sqlite');
    }

    public function test_non_admin_cannot_open_backups(): void
    {
        $manager = User::query()->where('email', 'manager@rtt.local')->firstOrFail();

        $this->actingAs($manager)->get(route('admin.backups.index'))->assertForbidden();
        $this->actingAs($manager)->post(route('admin.backups.store'))->assertForbidden();
    }
}

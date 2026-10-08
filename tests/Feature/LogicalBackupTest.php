<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Support\DatabaseBackupManager;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

/**
 * MySQL/PostgreSQL serverlarida ishlatiladigan mantiqiy zahira (.json.gz) — test bazasida (sqlite) majburan sinaladi.
 */
class LogicalBackupTest extends TestCase
{
    use RefreshDatabase;

    private string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupPath = storage_path('app/testing-logical-backups');
        File::deleteDirectory($this->backupPath);
        config()->set('database-backup.path', $this->backupPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupPath);

        parent::tearDown();
    }

    public function test_logical_backup_and_restore_bring_back_all_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $manager = app(DatabaseBackupManager::class);

        $ticket = Ticket::query()->firstOrFail();
        $user = User::query()->where('email', 'requester@rtt.local')->firstOrFail();
        $ticketCount = Ticket::query()->count();
        $userCount = User::query()->count();
        $originalName = $user->name;

        $backup = $manager->backup('test', 5, 'json');

        $this->assertStringEndsWith('.json.gz', $backup['path']);
        $this->assertGreaterThan(0, $backup['size']);

        // O'zgarishlar: murojaat o'chirildi, ism o'zgardi, yangi foydalanuvchi qo'shildi
        $ticket->delete();
        $user->forceFill(['name' => 'Ozgargan Ism'])->save();
        User::factory()->create(['email' => 'new-after-backup@example.test']);

        $result = $manager->restore(basename($backup['path']), createSafetyBackup: true);

        $this->assertNotNull($result['safety_backup']);
        $this->assertSame($ticketCount, Ticket::query()->count());
        $this->assertSame($userCount, User::query()->count());
        $this->assertModelExists($ticket);
        $this->assertSame($originalName, $user->fresh()->name);
        $this->assertDatabaseMissing('users', ['email' => 'new-after-backup@example.test']);
        $this->assertTrue($user->fresh()->hasRole('requester'));
    }

    public function test_broken_logical_backup_is_rejected(): void
    {
        File::ensureDirectoryExists($this->backupPath);
        file_put_contents('compress.zlib://'.$this->backupPath.'/broken-20261008-000000.json.gz', "{\"format\":\"other\"}\n");

        $this->expectException(RuntimeException::class);

        app(DatabaseBackupManager::class)->restore('broken-20261008-000000.json.gz');
    }
}

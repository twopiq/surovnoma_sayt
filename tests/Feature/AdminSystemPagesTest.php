<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuditService;
use App\Support\LogReader;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminSystemPagesTest extends TestCase
{
    use RefreshDatabase;

    private string $logFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logFile = storage_path('logs/testing-admin-view.log');
        File::put($this->logFile, implode("\n", [
            '['.now()->format('Y-m-d H:i:s').'] testing.ERROR: Bot xatosi token=123456789:ABCDEFGHIJKLMNOPQRSTUVWXYZabcdef1234 password=Sir123',
            '#0 /var/www/app/Some.php(10): stack trace qatori',
            '['.now()->format('Y-m-d H:i:s').'] testing.INFO: Oddiy ma\'lumot',
        ])."\n");
    }

    protected function tearDown(): void
    {
        File::delete($this->logFile);

        parent::tearDown();
    }

    public function test_admin_sees_health_and_logs(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();
        app(AuditService::class)->log($admin->id, 'test.event', 'Sinov audit yozuvi', null, ['a' => 1]);

        $this->actingAs($admin)->get(route('admin.system.health'))
            ->assertOk()
            ->assertSee('Tizim holati')
            ->assertSee('Rejalashtiruvchi (cron)')
            ->assertSee('Debug rejimi');

        $this->actingAs($admin)->get(route('admin.system.logs', ['event' => 'test.event']))
            ->assertOk()
            ->assertSee('Sinov audit yozuvi');

        $this->actingAs($admin)->get(route('admin.system.logs', ['tab' => 'server', 'file' => 'testing-admin-view.log', 'level' => 'error']))
            ->assertOk()
            ->assertSee('Bot xatosi')
            ->assertSee('stack trace qatori')
            ->assertDontSee('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdef1234')
            ->assertDontSee('Sir123')
            ->assertDontSee("Oddiy ma'lumot", false);

        $this->actingAs($admin)->get(route('admin.system.logs.download', 'testing-admin-view.log'))->assertOk()->assertDownload();
        $this->actingAs($admin)->get(route('admin.system.logs.download', '..env'))->assertNotFound();
    }

    public function test_log_reader_masks_secrets(): void
    {
        $masked = app(LogReader::class)->mask('Authorization: Bearer abcdefghijklmnopqrstuvwxyz base64:QUJDREVGR0hJSktMTU5PUFFSU1RVVldYWVo=');

        $this->assertStringNotContainsString('abcdefghijklmnopqrstuvwxyz', $masked);
        $this->assertStringNotContainsString('QUJDREVGR0hJSktMTU5PUFFSU1RVVldYWVo', $masked);
    }

    public function test_non_admin_cannot_open_system_pages(): void
    {
        $this->seed(DatabaseSeeder::class);
        $manager = User::query()->where('email', 'manager@rtt.local')->firstOrFail();

        $this->actingAs($manager)->get(route('admin.system.health'))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.system.logs'))->assertForbidden();
    }
}

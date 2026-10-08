<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GuestBlock;
use App\Models\GuestSetting;
use App\Models\Ticket;
use App\Models\User;
use App\Services\GuestRequestGuard;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GuestRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        config([
            'guest_limits.device_per_hour' => 2,
            'guest_limits.device_per_day' => 10,
            'guest_limits.ip_per_hour' => 50,
            'guest_limits.ip_per_day' => 50,
            'guest_limits.contact_per_day' => 50,
            'guest_limits.min_fill_seconds' => 0,
            'guest_limits.post_per_minute' => 100,
            'guest_limits.duplicate_window_hours' => 0,
            'guest_limits.alert_enabled' => false,
            'guest_limits.whitelist_ips' => [],
            'services.turnstile.site_key' => null,
            'services.turnstile.secret_key' => null,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Guest Tester',
            'email' => 'guest@example.test',
            'phone' => '+998 90 123 45 67',
            'category_id' => Category::query()->where('is_active', true)->value('id'),
            'description' => 'Bu guest limit testi uchun yaratilgan yetarlicha uzun tavsif matni.',
            GuestRequestGuard::FORM_TIME_FIELD => encrypt(now()->subMinute()->timestamp),
        ], $overrides);
    }

    private function submit(array $overrides = [])
    {
        return $this->withCookie(config('guest_limits.device_cookie'), 'test-device-0000000000001')
            ->post(route('guest.store'), $this->payload($overrides));
    }

    public function test_device_is_blocked_after_exceeding_limit_and_admin_can_unblock(): void
    {
        $before = Ticket::query()->count();

        $this->submit()->assertOk();
        $this->submit()->assertOk();
        $this->submit()->assertStatus(429);

        $this->assertSame($before + 2, Ticket::query()->count());

        $block = GuestBlock::query()->firstOrFail();
        $this->assertSame('device_hourly', $block->reason);
        $this->assertSame('test-device-0000000000001', $block->device_id);
        $this->assertNotNull($block->ip);

        $this->submit()->assertStatus(429);
        $this->assertSame(2, $block->fresh()->attempts_count);

        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.guest-blocks.index'))
            ->assertOk()
            ->assertSee('BLOK-'.$block->id);
        $this->actingAs($admin)->post(route('admin.guest-blocks.unblock', $block))->assertRedirect();

        $this->assertNotNull($block->fresh()->unblocked_at);

        auth()->logout();
        $this->submit()->assertOk();
    }

    public function test_honeypot_blocks_bot(): void
    {
        $before = Ticket::query()->count();

        $this->submit([GuestRequestGuard::HONEYPOT_FIELD => 'http://spam.example'])->assertStatus(429);

        $this->assertSame('bot', GuestBlock::query()->value('reason'));
        $this->assertSame($before, Ticket::query()->count());
    }

    public function test_requester_cannot_open_guest_blocks_page(): void
    {
        $requester = User::query()->where('email', 'requester@rtt.local')->firstOrFail();

        $this->actingAs($requester)->get(route('admin.guest-blocks.index'))->assertForbidden();
    }

    public function test_duplicate_description_is_rejected(): void
    {
        config(['guest_limits.duplicate_window_hours' => 24]);
        $before = Ticket::query()->count();

        $this->submit()->assertOk();
        $this->submit(['description' => "  BU guest limit testi uchun   yaratilgan yetarlicha uzun tavsif matni. "])
            ->assertSessionHasErrors('description');

        $this->assertSame($before + 1, Ticket::query()->count());
    }

    public function test_whitelisted_ip_skips_ip_limits(): void
    {
        config([
            'guest_limits.whitelist_ips' => ['127.0.0.0/8'],
            'guest_limits.ip_per_hour' => 1,
        ]);

        $this->submit()->assertOk();
        $this->submit()->assertOk();

        $this->assertSame(0, GuestBlock::query()->count());
    }

    public function test_turnstile_failure_rejects_submission(): void
    {
        config([
            'services.turnstile.site_key' => 'site',
            'services.turnstile.secret_key' => 'secret',
        ]);
        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()
            ->push(['success' => false])
            ->push(['success' => true])]);
        $before = Ticket::query()->count();

        $this->submit()->assertSessionHasErrors('captcha');
        $this->submit([GuestRequestGuard::CAPTCHA_FIELD => 'bad'])->assertSessionHasErrors('captcha');
        $this->assertSame($before, Ticket::query()->count());

        $this->submit([GuestRequestGuard::CAPTCHA_FIELD => 'token'])->assertOk();
    }

    public function test_admin_can_change_settings_from_ui(): void
    {
        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.guest-blocks.settings'))->assertOk()->assertSee('Ishonchli IP');

        $payload = [
            'device_per_hour' => 7, 'device_per_day' => 20, 'ip_per_hour' => 30, 'ip_per_day' => 90,
            'contact_per_day' => 6, 'min_fill_seconds' => 3, 'post_per_minute' => 15,
            'duplicate_window_hours' => 12, 'max_files' => 2, 'max_file_size_mb' => 1.5,
            'alert_enabled' => 1, 'alert_per_hour' => 4,
            'whitelist_ips' => "10.0.0.5\n192.168.1.0/24",
            'turnstile_site_key' => 'site-key', 'turnstile_secret_key' => 'secret-key',
        ];

        $this->actingAs($admin)->put(route('admin.guest-blocks.settings.update'), $payload)
            ->assertRedirect()->assertSessionHasNoErrors();

        config(['guest_limits.device_per_hour' => 1, 'services.turnstile.secret_key' => null]);
        GuestSetting::applyToConfig();

        $this->assertSame(7, config('guest_limits.device_per_hour'));
        $this->assertSame(1536, config('guest_limits.max_file_size_kb'));
        $this->assertSame(['10.0.0.5', '192.168.1.0/24'], config('guest_limits.whitelist_ips'));
        $this->assertSame('secret-key', config('services.turnstile.secret_key'));
        $this->assertNotSame('secret-key', GuestSetting::query()->where('key', 'turnstile_secret_key')->value('value'));

        // Secret bo'sh qoldirilsa saqlanib qoladi
        $this->actingAs($admin)->put(route('admin.guest-blocks.settings.update'), ['turnstile_secret_key' => ''] + $payload);
        $this->assertSame('secret-key', config('services.turnstile.secret_key'));

        $this->actingAs($admin)->put(route('admin.guest-blocks.settings.update'), ['whitelist_ips' => 'abc'] + $payload)
            ->assertSessionHasErrors('whitelist_ips');
    }

    public function test_admin_can_block_ip_manually(): void
    {
        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.guest-blocks.store'), ['type' => 'ip', 'value' => '127.0.0.1'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('manual', GuestBlock::query()->value('reason'));

        auth()->logout();
        $this->submit()->assertStatus(429);
    }

    public function test_guest_file_count_is_limited(): void
    {
        config(['guest_limits.max_files' => 1]);

        $this->submit(['attachments' => [
            UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'),
        ]])->assertSessionHasErrors('attachments');
    }
}

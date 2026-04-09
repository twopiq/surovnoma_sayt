<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\SlaProfile;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DeadlineAlertsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gets_warning_and_overdue_notifications_without_duplicates(): void
    {
        Carbon::setTestNow('2026-04-09 10:00:00');
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();
        $slaProfile = SlaProfile::query()->firstOrFail();
        $ticket = Ticket::query()->firstOrFail();

        $ticket->forceFill([
            'status' => TicketStatus::Assigned,
            'sla_profile_id' => $slaProfile->id,
            'deadline_at' => now()->copy()->addMinutes($slaProfile->warning_minutes - 1),
            'metadata' => [],
        ])->save();

        Artisan::call('tickets:send-deadline-alerts');

        $admin->refresh();
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame('deadline_warning', $admin->notifications()->first()->data['kind']);

        Artisan::call('tickets:send-deadline-alerts');
        $this->assertSame(1, $admin->fresh()->notifications()->count());

        $ticket->forceFill([
            'deadline_at' => now()->copy()->subMinutes(5),
        ])->save();

        Artisan::call('tickets:send-deadline-alerts');

        $admin->refresh();
        $this->assertSame(2, $admin->notifications()->count());
        $this->assertEqualsCanonicalizing(
            ['deadline_warning', 'deadline_overdue'],
            $admin->notifications->map(fn ($notification) => $notification->data['kind'])->all(),
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}

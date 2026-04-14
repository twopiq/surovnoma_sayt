<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_current_month_completed_work_report_by_default(): void
    {
        $this->seed(DatabaseSeeder::class);

        $manager = User::query()->where('email', 'manager@rtt.local')->firstOrFail();
        $currentMonthTicket = Ticket::query()->firstOrFail();
        $previousMonthTicket = Ticket::query()->skip(1)->firstOrFail();

        $currentMonthTicket->forceFill([
            'status' => TicketStatus::Completed,
            'completed_at' => now(),
        ])->save();

        $previousMonthTicket->forceFill([
            'status' => TicketStatus::Completed,
            'completed_at' => now()->subMonthNoOverflow(),
        ])->save();

        $response = $this->actingAs($manager)->get(route('manager.dashboard'));

        $response->assertOk();
        $response->assertSee('Oylik hisobot');
        $response->assertSee('Tanlangan ishlar');
        $response->assertSee('Faol ishlar kesimi');
        $response->assertSee('1');
        $response->assertDontSee($previousMonthTicket->reference);
    }

    public function test_admin_can_open_dashboard_report(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();
        $admin->syncRoles([UserRole::Admin->value]);

        $response = $this->actingAs($admin)->get(route('manager.dashboard'));

        $response->assertOk();
        $response->assertSee('Oylik hisobot');
    }

    public function test_dashboard_completed_work_report_can_be_exported_as_excel(): void
    {
        $this->seed(DatabaseSeeder::class);

        $manager = User::query()->where('email', 'manager@rtt.local')->firstOrFail();
        $ticket = Ticket::query()->firstOrFail();

        $ticket->forceFill([
            'status' => TicketStatus::Completed,
            'completed_at' => now(),
        ])->save();

        $excelResponse = $this->actingAs($manager)->get(route('manager.dashboard.export', [
            'stat' => 'selected',
            'month' => now()->format('Y-m'),
        ]));

        $excelResponse->assertOk();
        $excelResponse->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $this->assertStringContainsString($ticket->reference, $excelResponse->streamedContent());
    }
}

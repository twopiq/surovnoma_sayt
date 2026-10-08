<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\DashboardWidget;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_every_widget_with_visibility_toggles(): void
    {
        $this->seed(DatabaseSeeder::class);
        Ticket::query()->firstOrFail()->forceFill(['status' => TicketStatus::Completed, 'completed_at' => now()])->save();

        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();

        $this->actingAs($admin)->get(route('app.dashboard'))
            ->assertOk()
            ->assertSee('Murojaatlar hisoboti')
            ->assertSee('Kunlik trend')
            ->assertSee('Ijrochilar KPI reytingi')
            ->assertSee("Bo'limlar")
            ->assertSee('Rahbarda yashirin');

        foreach (['today', '7d', 'month'] as $period) {
            $this->actingAs($admin)->get(route('app.dashboard', ['period' => $period, 'compare' => '0']))->assertOk();
        }

        $this->actingAs($admin)
            ->get(route('app.dashboard', ['period' => 'range', 'from' => now()->subDays(3)->toDateString(), 'to' => now()->toDateString()]))
            ->assertOk();
    }

    public function test_manager_sees_only_widgets_allowed_by_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();
        $manager = User::query()->where('email', 'manager@rtt.local')->firstOrFail();
        $manager->forceFill(['can_access_app_dashboard' => true])->save();

        $this->actingAs($manager)->get(route('app.dashboard'))
            ->assertOk()
            ->assertSee('Kunlik trend')
            ->assertDontSee('Ijrochilar KPI reytingi')
            ->assertDontSee('Rahbarda');

        $this->actingAs($admin)->post(route('app.dashboard.widgets.toggle', 'trend'))->assertRedirect();
        $this->assertFalse(DashboardWidget::managerVisibility()['trend']);

        $this->actingAs($manager)->get(route('app.dashboard'))->assertDontSee('<h3>Kunlik trend</h3>', false);
        $this->actingAs($admin)->get(route('app.dashboard', ['view' => 'manager']))
            ->assertOk()
            ->assertDontSee('<h3>Kunlik trend</h3>', false)
            ->assertSee("Rahbar shu ko'rinishni ko'radi", false);

        $this->actingAs($manager)->post(route('app.dashboard.widgets.toggle', 'executors'))->assertForbidden();
    }

    public function test_dashboard_report_can_be_exported(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();

        $this->actingAs($admin)->get(route('app.dashboard.export', ['format' => 'csv']))
            ->assertOk()
            ->assertDownload();
    }
}

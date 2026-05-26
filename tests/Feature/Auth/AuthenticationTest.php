<?php

namespace Tests\Feature\Auth;

use App\Enums\ExternalStatus;
use App\Enums\TicketStatus;
use App\Models\User;
use App\Models\Ticket;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('app.home', absolute: false));
    }

    public function test_users_can_authenticate_using_login_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Ali Valiyev',
            'login' => 'ali.valiyev',
        ]);

        $response = $this->post('/login', [
            'login' => 'ali.valiyev',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('app.home', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_logout_can_redirect_to_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout', [
            'redirect' => 'login',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_admin_login_marks_expired_deadline_tickets_as_overdue(): void
    {
        Carbon::setTestNow('2026-04-09 10:00:00');
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();
        $executor = User::query()->where('email', 'executor@rtt.local')->firstOrFail();
        $tickets = Ticket::query()
            ->where('assigned_executor_id', $executor->id)
            ->take(2)
            ->get();

        $this->assertCount(2, $tickets);

        Ticket::query()
            ->whereKeyNot($tickets->pluck('id'))
            ->update(['deadline_at' => null]);

        foreach ($tickets as $ticket) {
            $ticket->forceFill([
                'status' => TicketStatus::Assigned,
                'external_status' => ExternalStatus::InProgress,
                'assigned_executor_id' => $executor->id,
                'deadline_at' => now()->subMinutes(15),
                'metadata' => [],
            ])->save();
        }

        $this->post('/login', [
            'login' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('app.home', absolute: false));

        foreach ($tickets as $ticket) {
            $ticket->refresh();

            $this->assertSame(TicketStatus::Overdue, $ticket->status);
            $this->assertSame(ExternalStatus::Overdue, $ticket->external_status);
            $this->assertNull($ticket->assigned_executor_id);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}

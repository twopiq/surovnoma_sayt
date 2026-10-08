<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_executor_return_puts_ticket_back_to_unaccepted_pool_without_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $executor = User::query()->where('email', 'executor@rtt.local')->firstOrFail();
        $ticket = Ticket::query()->where('assigned_executor_id', $executor->id)->firstOrFail();
        $before = $ticket->only(['assigned_department_id', 'category_id', 'priority', 'deadline_at']);

        $this->actingAs($executor)->post(route('executor.tickets.start', $ticket));
        $this->actingAs($executor)->post(route('executor.tickets.return', $ticket), [
            'reason' => 'Qaytarish uchun test sababi.',
        ])->assertRedirect(route('executor.tickets.index'));

        $ticket->refresh();

        $this->assertNull($ticket->assigned_executor_id);
        $this->assertSame(TicketStatus::New, $ticket->status);
        $this->assertFalse($ticket->hasPendingReturnRequest());
        $this->assertSame($before['assigned_department_id'], $ticket->assigned_department_id);
        $this->assertSame($before['category_id'], $ticket->category_id);
        $this->assertDatabaseHas('ticket_return_requests', [
            'ticket_id' => $ticket->id,
            'executor_id' => $executor->id,
            'reason' => 'Qaytarish uchun test sababi.',
        ]);
        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'to_status' => TicketStatus::New->value,
            'note' => 'Qaytarish uchun test sababi.',
        ]);

        // Murojaat umumiy navbatda — ijrochilar uni qayta olishi mumkin
        $this->actingAs($executor)
            ->get(route('executor.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Bajarishga olish');
    }

    public function test_admin_assignment_resolves_pending_return_request(): void
    {
        $this->seed(DatabaseSeeder::class);

        $executor = User::query()->where('email', 'executor@rtt.local')->firstOrFail();
        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();
        $ticket = Ticket::query()->where('assigned_executor_id', $executor->id)->firstOrFail();

        $this->actingAs($executor)->post(route('executor.tickets.start', $ticket));
        $this->actingAs($executor)->post(route('executor.tickets.return', $ticket), [
            'reason' => 'Qaytarish uchun test sababi.',
        ]);

        $this->assertNull($ticket->fresh()->assigned_executor_id);

        $departmentId = Department::query()->firstOrFail()->id;
        $categoryId = Category::query()->firstOrFail()->id;

        $this->actingAs($admin)->post(route('admin.dispatch.assign', $ticket), [
            'assigned_department_id' => $departmentId,
            'assigned_executor_id' => $executor->id,
            'category_id' => $categoryId,
            'priority' => TicketPriority::Medium->value,
            'note' => 'Qayta ko‘rib chiqildi.',
            'confirm_overload' => '1',
            'confirmed_overload_executor_id' => $executor->id,
            'confirmed_overload_priority' => TicketPriority::Medium->value,
        ]);

        $this->assertFalse($ticket->fresh()->hasPendingReturnRequest());
        $this->assertSame(TicketStatus::Assigned, $ticket->fresh()->status);
    }

    public function test_admin_reject_redirects_to_dispatch_index_and_resolves_pending_request(): void
    {
        $this->seed(DatabaseSeeder::class);

        $executor = User::query()->where('email', 'executor@rtt.local')->firstOrFail();
        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();
        $ticket = Ticket::query()->where('assigned_executor_id', $executor->id)->firstOrFail();

        $this->actingAs($executor)->post(route('executor.tickets.start', $ticket));
        $this->actingAs($executor)->post(route('executor.tickets.return', $ticket), [
            'reason' => 'Qaytarish uchun test sababi.',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.dispatch.reject', $ticket), [
            'reason' => 'Rad etish uchun test sababi.',
        ]);

        $response
            ->assertRedirect(route('admin.dispatch.index'))
            ->assertSessionHas('status', 'Murojaat rad etildi va yopildi.');

        $this->assertSame(TicketStatus::Rejected, $ticket->fresh()->status);
        $this->assertFalse($ticket->fresh()->hasPendingReturnRequest());
    }

    public function test_guest_tracking_result_page_has_home_button(): void
    {
        $this->seed(DatabaseSeeder::class);

        $ticket = Ticket::query()->where('channel', 'guest')->firstOrFail();
        session()->put("guest_ticket_access.{$ticket->id}", true);

        $this->get(route('guest.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('href="'.route('home').'"', false);
    }

    public function test_admin_can_clear_executor_assignment_and_return_ticket_to_common_pool(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();
        $executor = User::query()->where('email', 'executor@rtt.local')->firstOrFail();
        $ticket = Ticket::query()->where('assigned_executor_id', $executor->id)->firstOrFail();

        $secondExecutor = User::factory()->create([
            'name' => 'Second Executor',
            'email' => 'executor2@example.test',
            'approved_at' => now(),
            'is_active' => true,
        ]);
        $secondExecutor->assignRole(UserRole::Executor->value);

        $departmentId = Department::query()->firstOrFail()->id;
        $categoryId = Category::query()->firstOrFail()->id;

        $response = $this->actingAs($admin)->post(route('admin.dispatch.assign', $ticket), [
            'assigned_department_id' => $departmentId,
            'assigned_executor_id' => null,
            'category_id' => $categoryId,
            'priority' => TicketPriority::Medium->value,
            'note' => "Ijrochi bo'shatildi.",
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('status', 'Murojaat taqsimlandi.');

        $ticket->refresh();

        $this->assertNull($ticket->assigned_executor_id);
        $this->assertSame(TicketStatus::New, $ticket->status);

        $this->actingAs($secondExecutor)
            ->get(route('executor.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Bajarishga olish');
    }
}

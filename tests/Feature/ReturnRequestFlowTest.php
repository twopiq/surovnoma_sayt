<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
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

    public function test_executor_return_button_stays_inactive_while_request_is_pending(): void
    {
        $this->seed(DatabaseSeeder::class);

        $executor = User::query()->where('email', 'executor@rtt.local')->firstOrFail();
        $ticket = Ticket::query()->where('assigned_executor_id', $executor->id)->firstOrFail();

        $this->actingAs($executor)->post(route('executor.tickets.start', $ticket));
        $this->actingAs($executor)->post(route('executor.tickets.return', $ticket), [
            'reason' => 'Qaytarish uchun test sababi.',
        ]);

        $response = $this->actingAs($executor)->get(route('executor.tickets.show', $ticket->fresh()));

        $response
            ->assertOk()
            ->assertSee("So'rov yuborildi")
            ->assertSee("Admindan javob kelmaguncha qayta so'rov yuborib bo'lmaydi.");
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

        $this->assertTrue($ticket->fresh()->hasPendingReturnRequest());

        $departmentId = Department::query()->firstOrFail()->id;
        $categoryId = Category::query()->firstOrFail()->id;

        $this->actingAs($admin)->post(route('admin.dispatch.assign', $ticket), [
            'assigned_department_id' => $departmentId,
            'assigned_executor_id' => $executor->id,
            'category_id' => $categoryId,
            'priority' => TicketPriority::Medium->value,
            'note' => 'Qayta ko‘rib chiqildi.',
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
            ->assertSee('Home');
    }
}

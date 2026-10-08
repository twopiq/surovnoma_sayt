<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketStatusNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TicketCommentAndCancelTest extends TestCase
{
    use RefreshDatabase;

    private User $requester;

    private User $executor;

    private User $admin;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->requester = User::query()->where('email', 'requester@rtt.local')->firstOrFail();
        $this->executor = User::query()->where('email', 'executor@rtt.local')->firstOrFail();
        $this->admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();

        $this->ticket = Ticket::query()->where('assigned_executor_id', $this->executor->id)->firstOrFail();
        $this->ticket->forceFill(['requester_id' => $this->requester->id, 'status' => TicketStatus::InProgress])->save();
    }

    public function test_public_comment_by_admin_notifies_requester(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.dispatch.comment', $this->ticket), ['body' => 'Ertaga kelib ko\'ramiz.', 'is_public' => 1])
            ->assertRedirect();

        Notification::assertSentTo($this->requester, TicketStatusNotification::class,
            fn (TicketStatusNotification $n) => str_contains($n->toArray($this->requester)['body'], 'Ertaga kelib'));
    }

    public function test_internal_comment_does_not_notify_requester(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.dispatch.comment', $this->ticket), ['body' => 'Ichki izoh matni']);

        Notification::assertNotSentTo($this->requester, TicketStatusNotification::class);
    }

    public function test_requester_comment_notifies_executor(): void
    {
        Notification::fake();

        $this->actingAs($this->requester)
            ->post(route('tickets.comment', $this->ticket), ['body' => 'Muammo hali ham bor.']);

        Notification::assertSentTo($this->executor, TicketStatusNotification::class);
        Notification::assertNotSentTo($this->requester, TicketStatusNotification::class);
    }

    public function test_requester_can_cancel_active_ticket(): void
    {
        Notification::fake();

        $this->actingAs($this->requester)
            ->post(route('tickets.cancel', $this->ticket), ['reason' => 'Muammo o\'zi hal bo\'ldi'])
            ->assertRedirect(route('tickets.show', $this->ticket));

        $ticket = $this->ticket->fresh();
        $this->assertSame(TicketStatus::Cancelled, $ticket->status);
        $this->assertNotNull($ticket->closed_at);
        $this->assertDatabaseHas('ticket_status_histories', ['ticket_id' => $ticket->id, 'to_status' => 'cancelled']);
        Notification::assertSentTo($this->executor, TicketStatusNotification::class);

        // ijrochining faol ro'yxatidan chiqadi
        $this->actingAs($this->executor)->get(route('executor.tickets.index'))
            ->assertOk()->assertDontSee($ticket->reference);
    }

    public function test_finished_or_foreign_ticket_cannot_be_cancelled(): void
    {
        $this->ticket->forceFill(['status' => TicketStatus::Completed])->save();

        $this->actingAs($this->requester)->post(route('tickets.cancel', $this->ticket));
        $this->assertSame(TicketStatus::Completed, $this->ticket->fresh()->status);

        $other = User::factory()->create();
        $other->assignRole('requester');
        $this->actingAs($other)->post(route('tickets.cancel', $this->ticket))->assertForbidden();
    }
}

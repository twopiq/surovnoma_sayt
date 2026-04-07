<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketStatusNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class NotificationAndAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_requester_sees_allowed_attachment_formats_message(): void
    {
        $this->seed(DatabaseSeeder::class);

        $requester = User::query()->where('email', 'requester@rtt.local')->firstOrFail();

        $response = $this->actingAs($requester)->post(route('tickets.store'), [
            'description' => "Bu murojaat notog'ri formatdagi fayl xabarini tekshirish uchun yetarlicha uzun tavsifdir.",
            'attachments' => [
                UploadedFile::fake()->create('evidence.txt', 10, 'text/plain'),
            ],
        ]);

        $response->assertSessionHasErrors([
            'attachments.0' => "Fayl formati noto'g'ri. Faqat JPG, JPEG, PNG, PDF, DOC va DOCX formatlariga ruxsat beriladi.",
        ]);
    }

    public function test_guest_can_submit_ticket_and_see_tracking_code(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->post(route('guest.store'), [
            'name' => 'Guest User',
            'email' => 'guest@example.test',
            'phone' => '+998901112233',
            'department' => 'Kafedra',
            'job_title' => 'Mutaxassis',
            'description' => 'Bu guest forma uchun yaratilgan va yuborishga yetadigan uzun tavsif matni hisoblanadi.',
        ]);

        $response
            ->assertOk()
            ->assertSee('Murojaat qabul qilindi')
            ->assertSee('Maxfiy tracking code');

        $this->assertDatabaseCount('tickets', 4);
    }

    public function test_guest_sees_allowed_attachment_formats_message(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->from(route('guest.create'))->post(route('guest.store'), [
            'name' => 'Guest User',
            'description' => "Bu guest forma uchun notog'ri formatni tekshirishga yetadigan uzun tavsif matni hisoblanadi.",
            'attachments' => [
                UploadedFile::fake()->create('evidence.txt', 10, 'text/plain'),
            ],
        ]);

        $response
            ->assertRedirect(route('guest.create'))
            ->assertSessionHasErrors([
                'attachments.0' => "Fayl formati noto'g'ri. Faqat JPG, JPEG, PNG, PDF, DOC va DOCX formatlariga ruxsat beriladi.",
            ]);
    }

    public function test_notifications_can_be_marked_read_individually_and_in_bulk(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'requester@rtt.local')->firstOrFail();
        $this->actingAs($user);

        $user->notify(new TicketStatusNotification('Birinchi', 'Birinchi matn', '/tickets/1'));
        $user->notify(new TicketStatusNotification('Ikkinchi', 'Ikkinchi matn', '/tickets/2'));

        $targetNotification = $user->fresh()->notifications()->latest()->firstOrFail();

        $this->get(route('notifications.show', $targetNotification->id))
            ->assertRedirect($targetNotification->data['url']);

        $this->assertNotNull($user->fresh()->notifications()->find($targetNotification->id)?->read_at);
        $this->assertSame(1, $user->fresh()->unreadNotifications()->count());

        $this->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_executor_claim_button_changes_with_status_and_can_reaccept_returned_ticket(): void
    {
        $this->seed(DatabaseSeeder::class);

        $executor = User::query()->where('email', 'executor@rtt.local')->firstOrFail();
        $ticket = Ticket::query()->where('assigned_executor_id', $executor->id)->firstOrFail();

        $this->actingAs($executor)
            ->get(route('executor.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Qabul qilish');

        $this->actingAs($executor)
            ->post(route('executor.tickets.start', $ticket))
            ->assertSessionHas('status', 'Murojaat qabul qilindi.');

        $ticket->refresh();

        $this->assertSame(TicketStatus::InProgress, $ticket->status);

        $this->actingAs($executor)
            ->get(route('executor.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Qabul qilindi');

        $this->actingAs($executor)
            ->post(route('executor.tickets.return', $ticket), [
                'reason' => 'Bu qayta taqsimlash uchun test sababi.',
            ])
            ->assertSessionHas('status');

        $ticket->refresh();

        $this->assertSame(TicketStatus::Returned, $ticket->status);

        $this->actingAs($executor)
            ->get(route('executor.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Qayta qabul qilish');

        $this->actingAs($executor)
            ->post(route('executor.tickets.start', $ticket))
            ->assertSessionHas('status', 'Murojaat qayta qabul qilindi.');

        $this->assertSame(TicketStatus::InProgress, $ticket->fresh()->status);
    }
}

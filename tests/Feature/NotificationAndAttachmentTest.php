<?php

namespace Tests\Feature;

use App\Enums\ExternalStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketStatusNotification;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
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
            'phone' => '+998 90 111 22 33',
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
            'email' => 'guest@example.test',
            'phone' => '+998 90 111 22 33',
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

    public function test_notifications_can_be_deleted_individually_and_in_bulk(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'requester@rtt.local')->firstOrFail();
        $this->actingAs($user);

        $user->notify(new TicketStatusNotification('Birinchi', 'Birinchi matn', '/tickets/1'));
        $user->notify(new TicketStatusNotification('Ikkinchi', 'Ikkinchi matn', '/tickets/2'));

        $notification = $user->fresh()->notifications()->latest()->firstOrFail();

        $this->delete(route('notifications.destroy', $notification->id))
            ->assertRedirect()
            ->assertSessionHas('notifications_open', true);

        $this->assertSame(1, $user->fresh()->notifications()->count());

        $this->post(route('notifications.clear-all'))
            ->assertRedirect()
            ->assertSessionHas('notifications_open', true);

        $this->assertSame(0, $user->fresh()->notifications()->count());
    }

    public function test_old_notifications_are_purged_after_midnight(): void
    {
        Carbon::setTestNow('2026-04-09 23:55:00');
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'requester@rtt.local')->firstOrFail();
        $user->notify(new TicketStatusNotification('Eski', 'Eski bildirishnoma', '/tickets/1'));

        Carbon::setTestNow('2026-04-10 00:01:00');
        $user->notify(new TicketStatusNotification('Yangi', 'Yangi bildirishnoma', '/tickets/2'));

        Artisan::call('notifications:purge-expired');

        $this->assertSame(1, $user->fresh()->notifications()->count());
        $this->assertSame('Yangi', $user->fresh()->notifications()->first()->data['title']);

        Carbon::setTestNow();
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

    public function test_executor_can_see_and_claim_unassigned_ticket(): void
    {
        $this->seed(DatabaseSeeder::class);

        $executor = User::query()->where('email', 'executor@rtt.local')->firstOrFail();

        $ticket = Ticket::create([
            'reference' => 'RTT-TEST-UNASSIGNED',
            'channel' => 'operator',
            'requester_name' => 'Test User',
            'requester_email' => 'free@example.test',
            'description' => 'Bu ijrochi uchun bo\'sh murojaatni test qilishga yetadigan tavsif matni.',
            'priority' => TicketPriority::Low,
            'status' => TicketStatus::Assigned,
            'external_status' => ExternalStatus::InProgress,
        ]);

        $this->actingAs($executor)
            ->get(route('executor.tickets.index'))
            ->assertOk()
            ->assertSee("Bo'sh murojaatlar", false)
            ->assertSee('RTT-TEST-UNASSIGNED');

        $this->actingAs($executor)
            ->get(route('executor.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Bajarishga olish');

        $this->actingAs($executor)
            ->post(route('executor.tickets.start', $ticket))
            ->assertSessionHas('status', 'Murojaat qabul qilindi.');

        $ticket->refresh();

        $this->assertSame($executor->id, $ticket->assigned_executor_id);
        $this->assertSame(TicketStatus::InProgress, $ticket->status);
    }

    public function test_executor_complete_redirects_to_index_and_disables_repeat_complete(): void
    {
        $this->seed(DatabaseSeeder::class);

        $executor = User::query()->where('email', 'executor@rtt.local')->firstOrFail();
        $ticket = Ticket::query()->where('assigned_executor_id', $executor->id)->firstOrFail();

        $this->actingAs($executor)
            ->post(route('executor.tickets.start', $ticket))
            ->assertSessionHas('status', 'Murojaat qabul qilindi.');

        $response = $this->actingAs($executor)->post(route('executor.tickets.complete', $ticket), [
            'note' => 'Ish bajarildi.',
            'proofs' => [
                UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
            ],
        ]);

        $response
            ->assertRedirect(route('executor.tickets.index'))
            ->assertSessionHas('status', 'Murojaat bajarildi deb yuborildi.');

        $ticket->refresh();

        $this->assertSame(TicketStatus::Completed, $ticket->status);

        $this->actingAs($executor)
            ->get(route('executor.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Murojaat allaqachon bajarildi deb yuborilgan.')
            ->assertSee('cursor-not-allowed bg-slate-300 text-slate-600', false)
            ->assertDontSee("Adminga qaytarish");

        $this->actingAs($executor)
            ->get(route('executor.tickets.archive'))
            ->assertOk()
            ->assertSee($ticket->reference);

        $this->actingAs($executor)
            ->get(route('executor.tickets.index'))
            ->assertOk()
            ->assertDontSee('Moodle kursida yakuniy test ochilmayapti va baholash bo‘limi bo‘sh chiqmoqda.');
    }

    public function test_executor_cannot_claim_ticket_beyond_workload_limit(): void
    {
        $this->seed(DatabaseSeeder::class);

        $executor = User::query()->where('email', 'executor@rtt.local')->firstOrFail();

        foreach (range(1, 5) as $index) {
            Ticket::create([
                'reference' => sprintf('RTT-LOAD-%02d', $index),
                'channel' => 'operator',
                'requester_name' => 'Load User',
                'requester_email' => "load{$index}@example.test",
                'description' => 'Bu ijrochi yuklama limitini tekshirish uchun yaratilgan test murojaati.',
                'priority' => TicketPriority::Low,
                'status' => TicketStatus::InProgress,
                'external_status' => ExternalStatus::InProgress,
                'assigned_executor_id' => $executor->id,
            ]);
        }

        $availableTicket = Ticket::create([
            'reference' => 'RTT-LOAD-FULL',
            'channel' => 'operator',
            'requester_name' => 'Overflow User',
            'requester_email' => 'overflow@example.test',
            'description' => 'Bu ortiqcha yuklama bo\'lganda qabul qilinmasligi kerak bo\'lgan murojaat.',
            'priority' => TicketPriority::Low,
            'status' => TicketStatus::Assigned,
            'external_status' => ExternalStatus::InProgress,
        ]);

        $this->actingAs($executor)
            ->post(route('executor.tickets.start', $availableTicket))
            ->assertSessionHasErrors([
                'claim' => "Joriy yuklama limiti oshib ketadi. Bir vaqtning o'zida ko'pi bilan 1 ta shoshilinch va 1 ta past, yoki 2 ta yuqori, yoki 3 ta o'rta, yoki 5 ta past topshiriqni olish mumkin.",
            ]);

        $availableTicket->refresh();

        $this->assertNull($availableTicket->assigned_executor_id);
        $this->assertSame(TicketStatus::Assigned, $availableTicket->status);
    }

    public function test_executor_archive_detail_back_button_returns_to_archive(): void
    {
        $this->seed(DatabaseSeeder::class);

        $executor = User::query()->where('email', 'executor@rtt.local')->firstOrFail();
        $ticket = Ticket::query()->where('assigned_executor_id', $executor->id)->firstOrFail();

        $this->actingAs($executor)
            ->post(route('executor.tickets.start', $ticket))
            ->assertSessionHas('status', 'Murojaat qabul qilindi.');

        $this->actingAs($executor)
            ->post(route('executor.tickets.complete', $ticket), [
                'note' => 'Arxiv testi uchun bajarildi.',
                'proofs' => [
                    UploadedFile::fake()->create('archive-proof.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('executor.tickets.index'));

        $this->actingAs($executor)
            ->get(route('executor.tickets.archive'))
            ->assertOk()
            ->assertSee(route('executor.tickets.show', ['ticket' => $ticket, 'source' => 'archive']), false);

        $this->actingAs($executor)
            ->get(route('executor.tickets.show', ['ticket' => $ticket, 'source' => 'archive']))
            ->assertOk()
            ->assertSee(route('executor.tickets.archive'), false)
            ->assertDontSee('>Arxiv</a>', false)
            ->assertSee('<span class="inline-flex items-center px-1 pt-1 border-b-2 border-indigo-400 text-sm font-medium leading-5 text-gray-900 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out cursor-default" aria-current="page">', false)
            ->assertSee('Arxiv', false)
            ->assertSee('href="'.route('executor.tickets.index').'"', false);
    }
}

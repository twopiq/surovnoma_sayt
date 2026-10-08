<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\GuestBlock;
use App\Models\Ticket;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_guest_pages_use_new_design(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee("Qaysi yo'l sizga mos?")
            ->assertSee('Akkauntsiz murojaat yuboring')
            ->assertSee(route('guest.create'), false);

        $this->get(route('guest.create'))
            ->assertOk()
            ->assertSee('Murojaat yuborish')
            ->assertSee("Keyin nima bo'ladi?");

        $this->get(route('guest.track'))
            ->assertOk()
            ->assertSee('Murojaatni topish');
    }

    public function test_guest_can_see_ticket_progress_after_lookup(): void
    {
        $this->seed(DatabaseSeeder::class);

        $ticket = Ticket::query()->firstOrFail();
        $ticket->forceFill(['status' => TicketStatus::InProgress])->save();

        $this->withSession(["guest_ticket_access.{$ticket->id}" => true])
            ->get(route('guest.tickets.show', $ticket))
            ->assertOk()
            ->assertSee($ticket->reference)
            ->assertSee('aria-current="step"', false)
            ->assertSee("Ma'lumotlar");
    }

    public function test_blocked_page_shows_block_number(): void
    {
        $block = GuestBlock::query()->create([
            'scope' => GuestBlock::SCOPE_IP,
            'reason' => 'manual',
            'ip' => '127.0.0.1',
            'blocked_at' => now(),
        ]);

        $this->get(route('guest.create'))
            ->assertStatus(429)
            ->assertSee('BLOK-'.$block->id)
            ->assertSee('Nima qilish kerak?');
    }
}

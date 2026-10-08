<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\View\Components\TicketCard;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_shows_deadline_tone_and_grouped_files(): void
    {
        $this->seed(DatabaseSeeder::class);

        $ticket = Ticket::query()->firstOrFail();
        $ticket->update(['status' => TicketStatus::InProgress, 'deadline_at' => now()->subMinutes(30)]);

        foreach (['a.jpg', 'b.png', 'c.pdf'] as $name) {
            TicketAttachment::create([
                'ticket_id' => $ticket->id, 'disk' => 'local', 'path' => "tickets/{$ticket->id}/{$name}",
                'original_name' => $name, 'mime_type' => 'application/octet-stream', 'size_bytes' => 1, 'context' => 'request',
            ]);
        }

        $card = new TicketCard($ticket->fresh());

        $this->assertSame('late', $card->tone);
        $this->assertSame('30 daqiqa kechikdi', $card->pill);
        $this->assertSame(3, $card->filesTotal);
        $this->assertSame(['Rasm ×2', 'PDF'], array_column($card->files, 'text'));

        $ticket->update(['deadline_at' => now()->addHours(5)]);
        $this->assertSame('soon', (new TicketCard($ticket->fresh()))->tone);

        $ticket->update(['status' => TicketStatus::Completed]);
        $this->assertSame('Bajarilgan', (new TicketCard($ticket->fresh()))->pill);

        $ticket->update(['status' => TicketStatus::New, 'deadline_at' => null]);
        $this->assertSame('none', (new TicketCard($ticket->fresh()))->tone);
    }
}

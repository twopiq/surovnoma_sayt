<?php

namespace Tests\Feature;

use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_ticket_can_be_tracked_with_reference_and_code(): void
    {
        [$ticket, $trackingCode] = app(TicketService::class)->create([
            'channel' => 'guest',
            'requester_name' => 'Guest Tester',
            'requester_email' => 'guest@example.test',
            'description' => 'Bu guest tracking test uchun yaratilgan yetarlicha uzun tavsif matni.',
        ]);

        $response = $this->post(route('guest.lookup'), [
            'reference' => $ticket->reference,
            'tracking_code' => $trackingCode,
        ]);

        $response->assertRedirect(route('guest.tickets.show', $ticket));
    }
}

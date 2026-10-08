<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreadcrumbTest extends TestCase
{
    use RefreshDatabase;

    private const NAV = "aria-label=\"Yo'l ko'rsatkich\"";

    public function test_breadcrumb_shows_only_on_inner_pages_with_working_parent_link(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();
        $requester = User::query()->where('email', 'requester@rtt.local')->firstOrFail();
        $ticket = Ticket::query()->firstOrFail();

        // Yuqori darajadagi sahifalarda yo'q
        foreach ([route('admin.dispatch.tickets'), route('app.dashboard'), route('admin.users.list')] as $url) {
            $this->actingAs($admin)->get($url)->assertOk()->assertDontSee(self::NAV, false);
        }

        // Ichki sahifa: ochilgan joyga qarab ota sahifa
        $this->actingAs($admin)->get(route('admin.dispatch.show', ['ticket' => $ticket, 'source' => 'archive']))
            ->assertOk()
            ->assertSee(self::NAV, false)
            ->assertSee('href="'.route('admin.dispatch.archive').'"', false)
            ->assertSee('aria-current="page">'.$ticket->reference.'</span>', false);

        $this->actingAs($admin)->get(route('admin.dispatch.show', ['ticket' => $ticket, 'source' => 'home']))
            ->assertSee('>Bosh sahifa</a>', false);

        $this->actingAs($admin)->get(route('admin.dispatch.status', 'new'))
            ->assertSee('>Doska</a>', false)
            ->assertSee('aria-current="page">Yangi</span>', false);

        // Murojaatchi: faqat o'z bo'limi, admin havolalari yo'q
        $own = Ticket::query()->where('requester_id', $requester->id)->first();

        $this->actingAs($requester)->get(route('tickets.create'))
            ->assertOk()
            ->assertSee('href="'.route('tickets.index').'"', false)
            ->assertSee('Yangi murojaat')
            ->assertDontSee(route('admin.dispatch.tickets'), false);

        if ($own) {
            $this->actingAs($requester)->get(route('tickets.show', $own))->assertSee('>Murojaatlarim</a>', false);
        }

        // Eski inglizcha yorliqlar qolmagan
        $this->actingAs($admin)->get(route('admin.dispatch.show', $ticket))
            ->assertDontSee('Ticket card')
            ->assertDontSee('Ticket management');
    }
}

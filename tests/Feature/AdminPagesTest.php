<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\HolidayException;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();
    }

    public function test_redesigned_admin_pages_render(): void
    {
        $ticket = Ticket::query()->firstOrFail();
        $user = User::query()->where('email', 'requester@rtt.local')->firstOrFail();
        $archived = Ticket::query()->skip(1)->firstOrFail();
        $archived->forceFill([
            'status' => \App\Enums\TicketStatus::Completed,
            'completed_at' => now(),
            'deadline_at' => now()->addHour(),
        ])->save();

        $pages = [
            route('app.home') => ['Murojaatlar boshqaruvi', 'Tayinlash'],
            route('app.home', ['tab' => 'archive']) => ['Murojaatlar boshqaruvi'],
            route('admin.dispatch.tickets', ['ticket' => $ticket->id, 'q' => mb_substr($ticket->reference, 0, 6)]) => ['Murojaatlar', $ticket->reference],
            route('admin.dispatch.tickets', ['overdue' => 1]) => ['Kechikkan'],
            route('admin.users.list') => ['Foydalanuvchilar', 'Foydalanuvchini tanlang'],
            route('admin.users.list', ['user' => $user->id, 'segment' => 'new']) => [$user->email, 'Dashboardga ruxsat'],
            route('admin.users.index') => ['Tasdiq kutayotganlar'],
            route('admin.users.index', ['tab' => 'rejected']) => ['Rad etilganlar'],
            route('admin.users.profile', ['user' => $user->id]) => ['Rol va holat', 'Faollik'],
            route('admin.users.create') => ['Yangi foydalanuvchi'],
            route('admin.guest-blocks.index', ['status' => 'all']) => ['Mehmon himoyasi', 'Himoya holati'],
            route('admin.dispatch.deadlines') => ['Deadline sozlamalari', 'Muddat shkalasi'],
            route('admin.dispatch.work-schedule') => ['Ish kunlari', 'Bayram va istisno kunlar'],
            route('admin.dispatch.archive', ['period' => 'all']) => ['Murojaatlar arxivi', $archived->reference, 'Holat tarixi', 'Muddatida'],
            route('admin.dispatch.archive', ['status' => 'rejected', 'period' => '7']) => ['Murojaatlar arxivi'],
        ];

        foreach ($pages as $url => $texts) {
            $response = $this->actingAs($this->admin)->get($url);
            $response->assertOk();

            foreach ($texts as $text) {
                $response->assertSee($text);
            }
        }

        $this->actingAs($this->admin)->get(route('admin.users.recent'))->assertRedirect(route('admin.users.list', ['segment' => 'new']));
    }

    public function test_admin_sidebar_uses_grouped_menu(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dispatch.tickets'))
            ->assertOk()
            ->assertSee('Bosh sahifa')
            ->assertSee('Mehmon himoyasi')
            ->assertSee('Ish kunlari')
            ->assertDontSee('Ticket management')
            ->assertDontSee('Users CRUD');
    }

    public function test_holidays_can_be_added_and_removed(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.dispatch.holidays.store'), ['date' => '2026-12-31', 'name' => 'Yangi yil arafasi', 'is_working_override' => '0'])
            ->assertRedirect();

        $holiday = HolidayException::query()->whereDate('date', '2026-12-31')->firstOrFail();
        $this->assertFalse($holiday->is_working_override);

        $this->actingAs($this->admin)->delete(route('admin.dispatch.holidays.destroy', $holiday))->assertRedirect();
        $this->assertModelMissing($holiday);
    }

    public function test_user_can_be_updated_from_side_panel_and_receive_reset_link(): void
    {
        Notification::fake();
        $user = User::query()->where('email', 'requester@rtt.local')->firstOrFail();

        $this->actingAs($this->admin)
            ->patch(route('admin.users.profile.update', $user), [
                'name' => 'Yangi Ism',
                'email' => $user->email,
                'role' => UserRole::Requester->value,
                'status' => 'active',
                'return' => 'list',
                'keep' => ['segment' => 'new', 'role' => 'admin'],
            ])
            ->assertRedirect(route('admin.users.list', ['segment' => 'new', 'role' => 'admin', 'user' => $user->id]));

        $this->assertSame('Yangi Ism', $user->fresh()->name);

        $this->actingAs($this->admin)->post(route('admin.users.password-reset', $user))->assertRedirect();
        Notification::assertSentTo($user, \App\Notifications\ResetPasswordNotification::class);
    }
}

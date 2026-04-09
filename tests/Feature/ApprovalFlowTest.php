<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use App\Notifications\TicketStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_pending_requester_account(): void
    {
        Notification::fake();

        $department = Department::query()->create([
            'name' => 'Test Department',
            'code' => 'TDEP',
            'is_active' => true,
        ]);

        Role::findOrCreate(UserRole::Admin->value, 'web');

        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin->value);

        $response = $this->post('/register', [
            'name' => 'New Requester',
            'email' => 'new@example.test',
            'phone' => '+998 90 000 00 01',
            'job_title' => 'Teacher',
            'department_id' => $department->id,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('pending-approval'));

        $user = User::query()->where('email', 'new@example.test')->firstOrFail();

        $this->assertNull($user->approved_at);
        $this->assertTrue($user->hasRole(UserRole::Requester->value));
        $this->assertSame('+998 90 000 00 01', $user->phone);

        Notification::assertSentTo(
            $admin,
            TicketStatusNotification::class,
            fn (TicketStatusNotification $notification, array $channels, User $notifiable): bool => $channels === ['database']
                && $notifiable->is($admin)
        );
    }

    public function test_unapproved_user_is_redirected_to_pending_approval(): void
    {
        Role::findOrCreate(UserRole::Requester->value, 'web');

        $user = User::factory()->create([
            'approved_at' => null,
            'is_active' => true,
        ]);
        $user->assignRole(UserRole::Requester->value);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('pending-approval'));
    }

    public function test_registration_requires_exact_uzbek_phone_format(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'New Requester',
            'email' => 'new@example.test',
            'phone' => '+998 90 000 00',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors([
                'phone' => "Telefon raqami +998 99 999 99 99 ko'rinishida bo'lishi va 9 ta raqamdan iborat bo'lishi kerak.",
            ]);
    }

    public function test_registration_requires_name_and_surname(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'NewRequester',
            'email' => 'new@example.test',
            'phone' => '+998 90 000 00 01',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors([
                'name' => "F.I.Sh. kamida ism va familiyadan iborat bo'lishi kerak.",
            ]);
    }

    public function test_admin_can_reject_pending_registration_request(): void
    {
        Role::findOrCreate(UserRole::Admin->value, 'web');
        Role::findOrCreate(UserRole::Requester->value, 'web');

        $admin = User::factory()->create([
            'approved_at' => now(),
            'is_active' => true,
        ]);
        $admin->assignRole(UserRole::Admin->value);

        $pendingUser = User::factory()->create([
            'approved_at' => null,
            'is_active' => true,
        ]);
        $pendingUser->assignRole(UserRole::Requester->value);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $pendingUser), [
            'decision' => 'reject',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('status', "Ro'yxatdan o'tish so'rovi rad etildi.");

        $pendingUser->refresh();

        $this->assertNull($pendingUser->approved_at);
        $this->assertFalse($pendingUser->is_active);
    }

    public function test_rejected_user_sees_rejected_message_on_pending_approval_page(): void
    {
        Role::findOrCreate(UserRole::Requester->value, 'web');

        $user = User::factory()->create([
            'approved_at' => null,
            'is_active' => false,
        ]);
        $user->assignRole(UserRole::Requester->value);

        $this->actingAs($user)
            ->get(route('pending-approval'))
            ->assertOk()
            ->assertSee("So'rov rad etildi");
    }
}

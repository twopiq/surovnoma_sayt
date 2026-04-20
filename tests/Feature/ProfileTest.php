<?php

namespace Tests\Feature;

use App\Enums\AvailabilityStatus;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_settings_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/app/settings');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_can_be_updated_from_settings(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/app/settings/email', [
                'email' => 'settings@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/app/settings');

        $user->refresh();

        $this->assertSame('settings@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_profile_work_information_can_be_updated(): void
    {
        $department = Department::query()->create([
            'name' => 'Texnik xizmat',
            'code' => 'TECH',
            'description' => 'Texnik xizmat',
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
                'phone' => '+998 90 123 45 67',
                'job_title' => 'Yetakchi mutaxassis',
                'department_id' => $department->id,
                'availability_status' => AvailabilityStatus::Busy->value,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('+998 90 123 45 67', $user->phone);
        $this->assertSame('Yetakchi mutaxassis', $user->job_title);
        $this->assertSame($department->id, $user->department_id);
        $this->assertSame(AvailabilityStatus::Busy, $user->availability_status);
    }

    public function test_profile_name_requires_name_and_surname(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => 'Singleword',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasErrors('name')
            ->assertRedirect('/profile');
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+998 91 234 56 78',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('pending-approval', absolute: false));
        $this->assertGuest();
        $response->assertSessionHas('pending_approval_email', 'test@example.com');
        $this->assertDatabaseHas(User::class, [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone' => '+998 91 234 56 78',
            'login' => 'test.user',
        ]);
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification as ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_link_can_be_requested_by_login_and_unknown_accounts_get_the_same_answer(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $known = $this->post('/forgot-password', ['email' => $user->login]);
        Notification::assertSentTo($user, ResetPassword::class);

        // Akkaunt bormi-yo'qmi oshkor qilinmaydi: javob bir xil, xato yo'q.
        $unknown = $this->post('/forgot-password', ['email' => 'yoq-bunday@example.test']);
        $unknown->assertSessionHasNoErrors();
        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));
    }

    public function test_other_sessions_are_signed_out_after_a_reset(): void
    {
        Notification::fake();
        config(['session.driver' => 'database']);

        $user = User::factory()->create();
        \Illuminate\Support\Facades\DB::table('sessions')->insert(['id' => 'eski', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post('/reset-password', ['token' => $notification->token, 'email' => $user->email, 'password' => 'yangi-parol-123', 'password_confirmation' => 'yangi-parol-123'])
                ->assertRedirect(route('login'));

            return true;
        });

        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->count());
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }
}

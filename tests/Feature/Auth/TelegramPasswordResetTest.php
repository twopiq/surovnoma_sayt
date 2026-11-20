<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\TelegramBot\TelegramMessage;
use App\TelegramBot\TelegramSdkBot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class TelegramPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{0: string, 1: TelegramMessage}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram_bot.token' => 'test-token']);

        $bot = Mockery::mock(TelegramSdkBot::class);
        $bot->shouldReceive('sendMessage')->andReturnUsing(function (string $chatId, TelegramMessage $message) {
            $this->sent[] = [$chatId, $message];

            return true;
        });
        $this->app->instance(TelegramSdkBot::class, $bot);
    }

    private function linkedUser(): User
    {
        // Bildirishnomalar o'chirilgan bo'lsa ham kod yuboriladi.
        return User::factory()->create(['telegram_chat_id' => '555', 'telegram_notifications_enabled' => false]);
    }

    private function sentCode(): string
    {
        $this->assertNotEmpty($this->sent, 'Telegramga xabar yuborilmadi');
        preg_match('/\b(\d{6})\b/', end($this->sent)[1]->body, $m);

        return $m[1];
    }

    public function test_code_sent_to_telegram_leads_to_the_reset_form_and_the_password_changes(): void
    {
        $user = $this->linkedUser();

        $this->get('/forgot-password')->assertOk()->assertSee('Telegram orqali');

        $this->post('/forgot-password/telegram', ['login' => $user->login])->assertRedirect(route('password.telegram.verify'));
        $this->assertSame('555', $this->sent[0][0]);
        $code = $this->sentCode();

        $this->get(route('password.telegram.verify'))->assertOk()->assertSee($user->login);

        $redirect = $this->post('/forgot-password/telegram/code', ['login' => $user->login, 'code' => $code])->headers->get('Location');
        $this->assertStringContainsString('/reset-password/', $redirect);
        parse_str(parse_url($redirect, PHP_URL_QUERY), $query);
        $token = basename(parse_url($redirect, PHP_URL_PATH));

        $this->post('/reset-password', ['token' => $token, 'email' => $query['email'], 'password' => 'yangi-parol-123', 'password_confirmation' => 'yangi-parol-123'])
            ->assertSessionHasNoErrors()->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('yangi-parol-123', $user->fresh()->password));

        // Kod bir martalik.
        $this->post('/forgot-password/telegram/code', ['login' => $user->login, 'code' => $code])->assertSessionHasErrors('code');
    }

    public function test_wrong_codes_are_limited_and_unknown_accounts_get_the_same_answer(): void
    {
        $user = $this->linkedUser();
        $this->post('/forgot-password/telegram', ['login' => $user->email]);
        $code = $this->sentCode();
        $wrong = $code === '111111' ? '222222' : '111111';

        foreach (range(1, 5) as $i) {
            $this->post('/forgot-password/telegram/code', ['login' => $user->email, 'code' => $wrong])->assertSessionHasErrors('code');
        }
        // 5 ta xatodan keyin to'g'ri kod ham ishlamaydi — kod bekor qilingan.
        $this->post('/forgot-password/telegram/code', ['login' => $user->email, 'code' => $code])->assertSessionHasErrors('code');

        // Mavjud bo'lmagan va Telegram ulanmagan akkauntlarga ham xuddi shu javob, xabar yuborilmaydi.
        $this->sent = [];
        $plain = User::factory()->create(['telegram_chat_id' => null]);
        foreach (['yoq-bunday', $plain->login] as $login) {
            $this->post('/forgot-password/telegram', ['login' => $login])
                ->assertRedirect(route('password.telegram.verify'))->assertSessionHasNoErrors();
        }
        $this->assertSame([], $this->sent);
    }

    public function test_a_new_code_can_not_be_requested_more_than_once_a_minute(): void
    {
        $user = $this->linkedUser();
        $this->post('/forgot-password/telegram', ['login' => $user->login]);
        $this->post('/forgot-password/telegram', ['login' => $user->login]);

        $this->assertCount(1, $this->sent);
    }

    public function test_telegram_option_is_hidden_when_the_bot_is_not_configured(): void
    {
        config(['services.telegram_bot.token' => null]);

        $this->get('/forgot-password')->assertOk()->assertDontSee('Telegram orqali');
        $this->post('/forgot-password/telegram', ['login' => 'x'])->assertNotFound();
    }

    public function test_mail_test_command_reports_the_log_mailer(): void
    {
        config(['mail.default' => 'log']);

        $this->artisan('mail:test', ['to' => 'yoq'])->assertFailed();
        $this->artisan('mail:test', ['to' => 'test@example.test'])->expectsOutputToContain('MAIL_MAILER=log')->assertSuccessful();
    }
}

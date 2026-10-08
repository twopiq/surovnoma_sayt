<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\TicketStatusNotification;
use App\TelegramBot\TelegramSdkBot;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MultiTelegramTest extends TestCase
{
    use RefreshDatabase;

    private array $sentTo = [];

    public function test_admin_can_link_several_telegram_chats_and_receives_on_all(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->mockTelegramBot();

        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();

        $this->start($admin, '5001', 'admin_phone');
        $this->start($admin->fresh(), '5002', 'admin_desktop');

        $admin->refresh();
        $this->assertSame('5001', (string) $admin->telegram_chat_id);
        $this->assertSame(['5001', '5002'], $admin->telegramChatIds());

        // Qo'shimcha chatdan ham bot foydalanuvchini taniydi: shu chatni uzsa, asosiysi qoladi
        $this->postJson(route('telegram.webhook'), ['message' => ['chat' => ['id' => '5002'], 'text' => '/unlink']])->assertNoContent();
        $this->assertSame(['5001'], $admin->fresh()->telegramChatIds());

        $this->start($admin->fresh(), '5002', 'admin_desktop');
        $this->sentTo = [];
        $admin->fresh()->notify(new TicketStatusNotification('Sinov', 'Xabar', route('app.home')));

        $this->assertEqualsCanonicalizing(['5001', '5002'], $this->sentTo);

        // Asosiy chat uzilsa, qo'shimchasi asosiyga ko'tariladi
        $admin->fresh()->unlinkTelegramChat('5001');
        $this->assertSame('5002', (string) $admin->fresh()->telegram_chat_id);
        $this->assertSame(['5002'], $admin->fresh()->telegramChatIds());
    }

    public function test_user_can_log_out_of_site_account_from_bot_menu(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->mockTelegramBot();

        $executor = User::query()->where('email', 'executor@rtt.local')->firstOrFail();
        $this->start($executor, '8001', 'exec');
        $this->assertSame(['8001'], $executor->fresh()->telegramChatIds());

        // Tasdiqlash so'ralganda hali uzilmaydi
        $this->tapButton('8001', 'unlink:ask');
        $this->assertSame(['8001'], $executor->fresh()->telegramChatIds());

        $this->tapButton('8001', 'unlink:cancel');
        $this->assertSame(['8001'], $executor->fresh()->telegramChatIds());

        $this->tapButton('8001', 'unlink:confirm');
        $this->assertSame([], $executor->fresh()->telegramChatIds());

        // Ulanmagan chatda "Saytdagi akkauntni ulash" yo'riqnomasi ishlaydi
        $this->sentTo = [];
        $this->tapButton('8001', 'link');
        $this->assertContains('8001', $this->sentTo);
    }

    public function test_non_admin_keeps_single_telegram_chat(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->mockTelegramBot();

        $executor = User::query()->where('email', 'executor@rtt.local')->firstOrFail();

        $this->start($executor, '6001', null);
        $this->start($executor->fresh(), '6002', null);

        $this->assertSame(['6002'], $executor->fresh()->telegramChatIds());
        $this->assertDatabaseCount('user_telegram_accounts', 0);
    }

    public function test_settings_page_lists_accounts_and_disconnects_one(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();
        $admin->linkTelegramChat('7001', 'first');
        $admin->fresh()->linkTelegramChat('7002', 'second');

        $this->actingAs($admin)->get(route('app.settings'))
            ->assertOk()
            ->assertSee('Ulangan akkauntlar (2)')
            ->assertSee('@second')
            ->assertSee('Yana Telegram akkaunt ulash');

        $this->actingAs($admin)->delete(route('settings.telegram.disconnect-chat', '7002'))->assertRedirect(route('app.settings'));
        $this->assertSame(['7001'], $admin->fresh()->telegramChatIds());

        $other = User::query()->where('email', 'executor@rtt.local')->firstOrFail();
        $this->actingAs($other)->delete(route('settings.telegram.disconnect-chat', '7001'))->assertNotFound();
    }

    private function start(User $user, string $chatId, ?string $username): void
    {
        if (! $user->telegram_link_token) {
            $user->forceFill(['telegram_link_token' => \Illuminate\Support\Str::random(48)])->save();
        }

        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => $chatId],
                'from' => array_filter(['username' => $username]),
                'text' => '/start '.$user->telegram_link_token,
            ],
        ])->assertNoContent();
    }

    private function tapButton(string $chatId, string $data): void
    {
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'cb-'.$data,
                'data' => $data,
                'message' => ['chat' => ['id' => $chatId]],
            ],
        ])->assertNoContent();
    }

    private function mockTelegramBot(): void
    {
        config([
            'services.telegram_bot.webhook_secret' => null,
            'services.telegram_bot.token' => 'test-token',
        ]);

        $bot = Mockery::mock(TelegramSdkBot::class);
        $bot->shouldReceive('webhookUpdate')->andReturnUsing(fn ($request): array => $request->all());
        $bot->shouldReceive('answerCallbackQuery')->zeroOrMoreTimes()->andReturn(true);
        $bot->shouldReceive('sendMessage')->zeroOrMoreTimes()->andReturnUsing(function (string $chatId) {
            $this->sentTo[] = $chatId;

            return true;
        });

        $this->app->instance(TelegramSdkBot::class, $bot);
    }
}

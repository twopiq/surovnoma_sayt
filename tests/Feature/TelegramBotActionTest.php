<?php

namespace Tests\Feature;

use App\Enums\ExternalStatus;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\TelegramBot\TelegramSdkBot;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TelegramBotActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_executor_can_complete_ticket_from_telegram(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->mockTelegramBot();

        $executor = $this->linkedExecutor();
        $ticket = Ticket::query()->where('assigned_executor_id', $executor->id)->firstOrFail();

        $ticket->forceFill([
            'status' => TicketStatus::InProgress,
            'external_status' => ExternalStatus::InProgress,
        ])->save();

        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'complete-callback',
                'data' => 'executor:complete:'.$ticket->id,
                'message' => ['chat' => ['id' => '1001']],
            ],
        ])->assertNoContent();

        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => '1001'],
                'text' => 'Telegram orqali bajarildi.',
            ],
        ])->assertNoContent();

        $ticket->refresh();

        $this->assertSame(TicketStatus::Completed, $ticket->status);
        $this->assertNotNull($ticket->completed_at);
        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'to_status' => TicketStatus::Completed->value,
            'note' => 'Telegram orqali bajarildi.',
        ]);
    }

    public function test_executor_can_return_ticket_from_telegram(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->mockTelegramBot();

        $executor = $this->linkedExecutor();
        $ticket = Ticket::query()->where('assigned_executor_id', $executor->id)->firstOrFail();

        $ticket->forceFill([
            'status' => TicketStatus::InProgress,
            'external_status' => ExternalStatus::InProgress,
        ])->save();

        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'return-callback',
                'data' => 'executor:return:'.$ticket->id,
                'message' => ['chat' => ['id' => '1001']],
            ],
        ])->assertNoContent();

        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => '1001'],
                'text' => "Ma'lumot yetarli emas.",
            ],
        ])->assertNoContent();

        $ticket->refresh();

        $this->assertSame(TicketStatus::Returned, $ticket->status);
        $this->assertTrue($ticket->hasPendingReturnRequest());
    }

    public function test_executor_can_comment_ticket_from_telegram(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->mockTelegramBot();

        $executor = $this->linkedExecutor();
        $ticket = Ticket::query()->where('assigned_executor_id', $executor->id)->firstOrFail();

        $this->postJson(route('telegram.webhook'), [
            'callback_query' => [
                'id' => 'comment-callback',
                'data' => 'executor:comment:'.$ticket->id,
                'message' => ['chat' => ['id' => '1001']],
            ],
        ])->assertNoContent();

        $this->postJson(route('telegram.webhook'), [
            'message' => [
                'chat' => ['id' => '1001'],
                'text' => 'Telegramdan izoh.',
            ],
        ])->assertNoContent();

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->id,
            'user_id' => $executor->id,
            'body' => 'Telegramdan izoh.',
            'is_public' => true,
        ]);
    }

    private function linkedExecutor(): User
    {
        $executor = User::query()->where('email', 'executor@rtt.local')->firstOrFail();
        $executor->forceFill([
            'telegram_chat_id' => '1001',
            'telegram_notifications_enabled' => true,
        ])->save();

        return $executor;
    }

    private function mockTelegramBot(): void
    {
        config(['services.telegram_bot.webhook_secret' => null]);

        $bot = Mockery::mock(TelegramSdkBot::class);
        $bot->shouldReceive('webhookUpdate')
            ->andReturnUsing(fn ($request): array => $request->all());
        $bot->shouldReceive('answerCallbackQuery')
            ->zeroOrMoreTimes()
            ->andReturn(true);
        $bot->shouldReceive('sendMessage')
            ->zeroOrMoreTimes()
            ->andReturn(true);

        $this->app->instance(TelegramSdkBot::class, $bot);
    }
}

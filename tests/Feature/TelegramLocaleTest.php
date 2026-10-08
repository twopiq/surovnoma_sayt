<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\TicketStatusNotification;
use App\TelegramBot\TelegramMessage;
use App\TelegramBot\TelegramSdkBot;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

/**
 * Bot tilini ruscha/inglizchaga almashtirgandan keyin har bir menyu va tugma javobida o'zbekcha matn qolmasligi.
 */
class TelegramLocaleTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<TelegramMessage> */
    private array $messages = [];

    private const BUTTONS = [
        'profile', 'notifications:toggle', 'notifications:toggle', 'link', 'unlink:ask', 'unlink:cancel', 'guest:track',
        'requester:tickets', 'requester:create', 'executor:tasks', 'executor:available', 'executor:overdue',
        'operator:tickets', 'admin:summary', 'admin:overdue', 'admin:users', 'manager:summary',
    ];

    public function test_bot_speaks_only_the_chosen_language(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->mockTelegramBot();

        $problems = [];
        $chat = 9100;

        foreach (['ru' => 'Русский', 'en' => 'English'] as $locale => $choice) {
            foreach (['admin', 'executor', 'operator', 'manager', 'requester', null] as $role) {
                $chatId = (string) $chat++;

                if ($role) {
                    $user = User::query()->where('email', "{$role}@rtt.local")->firstOrFail();
                    $user->forceFill(['telegram_link_token' => Str::random(48)])->save();
                    $this->send($chatId, '/start '.$user->telegram_link_token);
                }

                // Tilni tanlash — shundan keyingi barcha javoblar tekshiriladi
                $this->send($chatId, $choice);
                $this->messages = [];

                $this->send($chatId, '/start');
                $this->send($chatId, '/menu');
                $this->send($chatId, '/profile');
                $this->send($chatId, __('Sozlamalar', [], $locale));
                $this->send($chatId, __('Tilni almashtirish', [], $locale));
                $this->send($chatId, __('Asosiy menyu', [], $locale));

                foreach (self::BUTTONS as $button) {
                    $this->tap($chatId, $button);
                }

                $this->send($chatId, 'qisqa');  // holat kiritish (masalan qisqa tavsif)
                $this->send($chatId, '/cancel');

                if ($role === 'executor') {
                    $ticketId = DB::table('tickets')->where('assigned_executor_id', $user->id)->value('id');
                    $this->tap($chatId, "executor:comment:{$ticketId}");
                    $this->send($chatId, 'ok');
                    $this->tap($chatId, "executor:return:{$ticketId}");
                    $this->send($chatId, '/cancel');
                }

                if ($role === null) {
                    $categoryId = DB::table('categories')->where('is_active', true)->value('id');
                    $this->tap($chatId, 'guest:create');
                    $this->tap($chatId, "guest:category:{$categoryId}");
                    $this->send($chatId, '123');
                }

                foreach ($this->texts() as $text) {
                    foreach ($this->uzbekLines($text) as $line) {
                        $problems[$line] = "[{$locale}] ".($role ?? 'mehmon').": «{$line}»";
                    }
                }
            }
        }

        // Bildirishnoma ham foydalanuvchi tilida
        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();
        $admin->forceFill(['locale' => 'en'])->save();
        $this->messages = [];
        $admin->fresh()->notify(new TicketStatusNotification(__('Murojaat holati yangilandi', [], 'uz'), __(':ref holati: :status', ['ref' => 'RTT-1', 'status' => 'Jarayonda'], 'uz'), route('app.home')));

        foreach ($this->texts() as $text) {
            foreach ($this->uzbekLines($text) as $line) {
                $problems[$line] = "[en] bildirishnoma: «{$line}»";
            }
        }

        $this->assertSame([], array_values($problems), "Botda o'zbekcha qolgan matnlar:\n".implode("\n", $problems));
    }

    private function texts(): array
    {
        $texts = [];

        foreach ($this->messages as $message) {
            $texts[] = $message->title;
            $texts[] = $message->body;

            foreach ($message->buttons as $row) {
                foreach ($row as $button) {
                    $texts[] = is_array($button) ? (string) ($button['text'] ?? '') : (string) $button;
                }
            }

            foreach ($message->replyKeyboard as $row) {
                foreach ((array) $row as $button) {
                    $texts[] = is_array($button) ? (string) ($button['text'] ?? '') : (string) $button;
                }
            }
        }

        return $texts;
    }

    private function uzbekLines(string $text): array
    {
        // Foydalanuvchi ma'lumotlari va til nomlari (O'zbekcha tugmasi) tekshirilmaydi
        $skip = DB::table('users')->pluck('name')
            ->merge(DB::table('categories')->pluck('name'))
            ->merge(DB::table('departments')->pluck('name'))
            ->merge(DB::table('tickets')->pluck('description'))
            ->merge(DB::table('ticket_comments')->pluck('body'))
            ->merge(["O'zbekcha"])
            ->filter()
            ->sortByDesc(fn ($v) => mb_strlen((string) $v));

        foreach ($skip as $value) {
            $text = str_replace((string) $value, ' ', $text);
        }

        $found = [];

        foreach (preg_split('/\R/u', $text) as $line) {
            $words = preg_split("/[^\\p{L}'ʼ‘’]+/u", mb_strtolower($line), -1, PREG_SPLIT_NO_EMPTY);

            foreach ($words as $word) {
                $word = str_replace(['ʼ', '‘', '’'], "'", trim($word, "'"));

                if (in_array($word, NoUzbekLeakTest::WORDS, true) || preg_match("/^[a-z]+(o'|g')[a-z]*$/", $word)) {
                    $found[] = trim($line);
                    break;
                }
            }
        }

        return $found;
    }

    private function send(string $chatId, string $text): void
    {
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => $chatId], 'from' => ['first_name' => 'Test'], 'text' => $text],
        ])->assertNoContent();
    }

    private function tap(string $chatId, string $data): void
    {
        $this->postJson(route('telegram.webhook'), [
            'callback_query' => ['id' => 'cb', 'data' => $data, 'message' => ['chat' => ['id' => $chatId]]],
        ])->assertNoContent();
    }

    private function mockTelegramBot(): void
    {
        config(['services.telegram_bot.webhook_secret' => null, 'services.telegram_bot.token' => 'test-token']);

        $bot = Mockery::mock(TelegramSdkBot::class);
        $bot->shouldReceive('webhookUpdate')->andReturnUsing(fn ($request): array => $request->all());
        $bot->shouldReceive('answerCallbackQuery')->zeroOrMoreTimes()->andReturn(true);
        $bot->shouldReceive('inlineKeyboard')->zeroOrMoreTimes()->andReturn([]);
        $bot->shouldReceive('replyKeyboard')->zeroOrMoreTimes()->andReturn([]);
        $bot->shouldReceive('sendMessage')->zeroOrMoreTimes()->andReturnUsing(function (string $chatId, TelegramMessage $message) {
            $this->messages[] = $message;

            return true;
        });

        $this->app->instance(TelegramSdkBot::class, $bot);
    }
}

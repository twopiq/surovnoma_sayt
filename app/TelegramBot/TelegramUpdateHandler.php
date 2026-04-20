<?php

namespace App\TelegramBot;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TelegramUpdateHandler
{
    public function __construct(
        protected TelegramSdkBot $bot,
    ) {
    }

    public function handle(array $update): void
    {
        if (! $this->telegramSchemaReady()) {
            return;
        }

        if (isset($update['callback_query']) && is_array($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);

            return;
        }

        $message = $update['message'] ?? $update['edited_message'] ?? null;

        if (! is_array($message)) {
            return;
        }

        $chatId = $message['chat']['id'] ?? null;
        $text = trim((string) ($message['text'] ?? ''));

        if (! $chatId || $text === '') {
            return;
        }

        $chatId = (string) $chatId;

        if (Str::startsWith($text, '/start')) {
            $this->handleStart($chatId, $text, $message);

            return;
        }

        if (Str::startsWith($text, '/profile')) {
            $this->sendProfile($chatId);

            return;
        }

        if (Str::startsWith($text, ['/on', '/notifications_on'])) {
            $this->setNotifications($chatId, true);

            return;
        }

        if (Str::startsWith($text, ['/off', '/notifications_off'])) {
            $this->setNotifications($chatId, false);

            return;
        }

        if (Str::startsWith($text, '/unlink')) {
            $this->handleUnlink($chatId);

            return;
        }

        $this->sendMenu($chatId, $this->userByChat($chatId));
    }

    protected function handleCallback(array $callback): void
    {
        $chatId = $callback['message']['chat']['id'] ?? null;
        $callbackId = $callback['id'] ?? null;
        $data = (string) ($callback['data'] ?? '');

        if (! $chatId) {
            return;
        }

        $chatId = (string) $chatId;
        $user = $this->userByChat($chatId);

        if ($callbackId) {
            $this->bot->answerCallbackQuery((string) $callbackId);
        }

        match ($data) {
            'profile' => $this->sendProfile($chatId),
            'notifications:toggle' => $this->toggleNotifications($chatId),
            'notifications:on' => $this->setNotifications($chatId, true),
            'notifications:off' => $this->setNotifications($chatId, false),
            'link' => $this->sendLinkHelp($chatId),
            default => $this->sendMenu($chatId, $user),
        };
    }

    protected function handleStart(string $chatId, string $text, array $message): void
    {
        $token = trim((string) preg_replace('/^\/start(?:@\S+)?\s*/', '', $text));

        if ($token === '') {
            $this->sendGreeting($chatId, $this->userByChat($chatId));

            return;
        }

        $user = User::query()
            ->where('telegram_link_token', $token)
            ->first();

        if (! $user) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                'Assalomu alaykum!',
                "Ulash kodi noto'g'ri yoki yangilangan. Saytdagi Sozlamalar sahifasidan Telegram botni qayta oching.",
                null,
                $this->menuButtons(null),
            ));

            return;
        }

        $from = $message['from'] ?? [];
        $chat = $message['chat'] ?? [];

        $user->forceFill([
            'telegram_chat_id' => $chatId,
            'telegram_username' => $from['username'] ?? $chat['username'] ?? null,
            'telegram_link_token' => Str::random(48),
            'telegram_notifications_enabled' => true,
            'telegram_linked_at' => now(),
        ])->save();

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Assalomu alaykum!',
            "Muvaffaqiyatli ulandingiz. Telegram akkauntingiz sayt profilingizga bog'landi. Endi yangi tizim xabarlari shu chatga avtomatik yuboriladi.",
            null,
            $this->menuButtons($user->fresh()),
        ));
    }

    protected function sendGreeting(string $chatId, ?User $user): void
    {
        if (! $user) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                'Assalomu alaykum!',
                "RTT Markazi botiga xush kelibsiz. Botdan foydalanish uchun avval saytga kiring va Sozlamalar bo'limidan Telegram botni oching.",
                null,
                $this->menuButtons(null),
            ));

            return;
        }

        $status = $this->notificationsEnabled($user) ? 'yoqilgan' : "o'chirilgan";

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Assalomu alaykum!',
            "RTT Markazi botiga xush kelibsiz. Akkauntingiz ulangan. Telegram xabarnomalari: {$status}.",
            null,
            $this->menuButtons($user),
        ));
    }

    protected function sendMenu(string $chatId, ?User $user): void
    {
        if (! $user) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                'RTT Markazi',
                "Saytga ulanish uchun saytdagi Sozlamalar bo'limidan Telegram botni oching.",
                null,
                $this->menuButtons(null),
            ));

            return;
        }

        $status = $this->notificationsEnabled($user) ? 'yoqilgan' : "o'chirilgan";

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'RTT Markazi',
            "Akkaunt ulangan. Telegram xabarnomalari: {$status}.",
            null,
            $this->menuButtons($user),
        ));
    }

    protected function sendProfile(string $chatId): void
    {
        $user = $this->userByChat($chatId);

        if (! $user) {
            $this->sendLinkHelp($chatId);

            return;
        }

        $lines = [
            'F.I.O.: '.$user->name,
            'Login: '.($user->login ?: '-'),
            'Email: '.$user->email,
            'Telefon: '.($user->phone ?: '-'),
            'Lavozim: '.($user->job_title ?: '-'),
            "Bo'lim: ".($user->department?->name ?: '-'),
            'Rol: '.$user->display_role,
            'Bandlik: '.($user->availability_status?->label() ?? '-'),
            'Telegram xabarnomalari: '.($this->notificationsEnabled($user) ? 'Yoqilgan' : "O'chirilgan"),
        ];

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Profil ma\'lumotlari',
            implode("\n", $lines),
            null,
            $this->menuButtons($user),
        ));
    }

    protected function setNotifications(string $chatId, bool $enabled): void
    {
        $user = $this->userByChat($chatId);

        if (! $user) {
            $this->sendLinkHelp($chatId);

            return;
        }

        $user->forceFill([
            'telegram_notifications_enabled' => $enabled,
        ])->save();

        $this->bot->sendMessage($chatId, new TelegramMessage(
            $enabled ? 'Xabarnomalar yoqildi' : "Xabarnomalar o'chirildi",
            $enabled
                ? 'Yangi tizim xabarlari shu chatga yuboriladi.'
                : "Yangi tizim xabarlari Telegramga yuborilmaydi. Saytdagi Bildirishnomalar bo'limida ko'rinaveradi.",
            null,
            $this->menuButtons($user->fresh()),
        ));
    }

    protected function toggleNotifications(string $chatId): void
    {
        $user = $this->userByChat($chatId);

        if (! $user) {
            $this->sendLinkHelp($chatId);

            return;
        }

        $this->setNotifications($chatId, ! $this->notificationsEnabled($user));
    }

    protected function sendLinkHelp(string $chatId): void
    {
        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Saytga ulash',
            "Avval saytga kiring, Sozlamalar bo'limini oching va Telegram botni ochish tugmasini bosing. Bot akkauntingizni avtomatik ulaydi.",
            null,
            $this->menuButtons(null),
        ));
    }

    protected function handleUnlink(string $chatId): void
    {
        $user = $this->userByChat($chatId);

        if (! $user) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                'Akkaunt topilmadi',
                "Bu Telegram chat hali hech bir akkauntga ulanmagan.",
                null,
                $this->menuButtons(null),
            ));

            return;
        }

        $user->forceFill([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_link_token' => Str::random(48),
            'telegram_notifications_enabled' => true,
            'telegram_linked_at' => null,
        ])->save();

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Telegram uzildi',
            "Bu chatga tizim xabarlari yuborilmaydi. Qayta ulash uchun saytdagi Sozlamalar bo'limidan Telegram botni oching.",
            null,
            $this->menuButtons(null),
        ));
    }

    protected function menuButtons(?User $user): array
    {
        if (! $user) {
            return [
                [
                    ['text' => 'Saytga ulash', 'callback_data' => 'link'],
                ],
            ];
        }

        $notificationButton = $this->notificationsEnabled($user)
            ? ['text' => "Xabarnomani o'chirish", 'callback_data' => 'notifications:toggle']
            : ['text' => 'Xabarnomani yoqish', 'callback_data' => 'notifications:toggle'];

        return [
            [
                ['text' => "Profilni ko'rish", 'callback_data' => 'profile'],
                $notificationButton,
            ],
        ];
    }

    protected function userByChat(string $chatId): ?User
    {
        return User::query()
            ->with('department')
            ->where('telegram_chat_id', $chatId)
            ->first();
    }

    protected function notificationsEnabled(User $user): bool
    {
        return $user->telegram_notifications_enabled !== false;
    }

    protected function telegramSchemaReady(): bool
    {
        foreach ([
            'telegram_chat_id',
            'telegram_username',
            'telegram_link_token',
            'telegram_linked_at',
            'telegram_notifications_enabled',
        ] as $column) {
            if (! Schema::hasColumn('users', $column)) {
                return false;
            }
        }

        return true;
    }
}

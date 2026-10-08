<?php

namespace App\TelegramBot;

use Illuminate\Notifications\Notification;

class TelegramNotificationChannel
{
    public function __construct(
        protected TelegramSdkBot $bot,
    ) {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toTelegram')) {
            return;
        }

        // Admin bir nechta Telegram akkaunt ulagan bo'lishi mumkin — hammasiga yuboriladi
        $chatIds = method_exists($notifiable, 'telegramChatIds')
            ? $notifiable->telegramChatIds()
            : array_filter([$notifiable->routeNotificationFor('telegram', $notification)]);

        if ($chatIds === []) {
            return;
        }

        $message = $notification->toTelegram($notifiable);

        if (! $message instanceof TelegramMessage) {
            return;
        }

        foreach ($chatIds as $chatId) {
            $this->bot->sendMessage((string) $chatId, $message);
        }
    }
}

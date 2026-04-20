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

        $chatId = $notifiable->routeNotificationFor('telegram', $notification);

        if (! $chatId) {
            return;
        }

        $message = $notification->toTelegram($notifiable);

        if (! $message instanceof TelegramMessage) {
            return;
        }

        $this->bot->sendMessage((string) $chatId, $message);
    }
}

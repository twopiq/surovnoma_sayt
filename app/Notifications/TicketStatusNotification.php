<?php

namespace App\Notifications;

use App\TelegramBot\TelegramMessage;
use App\TelegramBot\TelegramNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $title,
        protected string $body,
        protected ?string $url = null,
        protected array $meta = [],
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $token = config('services.telegram_bot.token');

        if (
            is_string($token)
            && $token !== ''
            && method_exists($notifiable, 'routeNotificationForTelegram')
            && $notifiable->routeNotificationForTelegram()
            && $notifiable->telegram_notifications_enabled !== false
        ) {
            $channels[] = TelegramNotificationChannel::class;
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            ...$this->meta,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->body);
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        return new TelegramMessage(
            $this->title,
            $this->body,
            $this->url,
        );
    }
}

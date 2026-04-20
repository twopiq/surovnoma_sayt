<?php

namespace App\Console\Commands;

use App\TelegramBot\TelegramSdkBot;
use Illuminate\Console\Command;

class TelegramSetWebhookCommand extends Command
{
    protected $signature = 'telegram:set-webhook {url? : Webhook URL} {--delete : Delete current webhook}';

    protected $description = 'Configure Telegram bot webhook for this application';

    public function handle(TelegramSdkBot $bot): int
    {
        if ($this->option('delete')) {
            if ($bot->deleteWebhook()) {
                $this->info('Telegram webhook o\'chirildi.');

                return self::SUCCESS;
            }

            $this->error('Telegram webhook o\'chirilmadi. TELEGRAM_BOT_TOKEN ni tekshiring.');

            return self::FAILURE;
        }

        $url = $this->argument('url') ?: config('services.telegram_bot.webhook_url') ?: route('telegram.webhook');
        $secret = config('services.telegram_bot.webhook_secret');

        if ($bot->setWebhook($url, is_string($secret) ? $secret : null)) {
            $this->info("Telegram webhook o'rnatildi: {$url}");

            return self::SUCCESS;
        }

        $this->error('Telegram webhook o\'rnatilmadi. Token, internet va URL ni tekshiring.');

        return self::FAILURE;
    }
}

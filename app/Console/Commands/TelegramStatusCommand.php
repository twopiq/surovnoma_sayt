<?php

namespace App\Console\Commands;

use App\Models\User;
use App\TelegramBot\TelegramSdkBot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class TelegramStatusCommand extends Command
{
    protected $signature = 'telegram:status';

    protected $description = 'Check Telegram bot configuration and database readiness';

    public function handle(TelegramSdkBot $bot): int
    {
        $this->components->info('Telegram bot diagnostikasi');

        $tokenConfigured = (bool) config('services.telegram_bot.token');
        $this->line('Token: '.($tokenConfigured ? 'bor' : 'yo\'q'));
        $this->line('SSL tekshiruvi: '.(config('services.telegram_bot.verify_ssl', true) ? 'yoqilgan' : "o'chirilgan"));

        if (! $tokenConfigured) {
            $this->error('TELEGRAM_BOT_TOKEN .env faylida sozlanmagan.');

            return self::FAILURE;
        }

        $schemaReady = $this->telegramSchemaReady();
        $this->line('Database ustunlari: '.($schemaReady ? 'tayyor' : 'tayyor emas'));

        if (! $schemaReady) {
            $this->warn('Avval migratsiyani bajaring: php artisan migrate');
        }

        $username = $bot->getMeUsername();
        $ok = $username !== null;

        $this->line('Telegram API: '.($ok ? 'ulandi' : 'ulanmadi'));
        $this->line('Bot username: '.($username ? '@'.$username : '-'));

        $configuredUsername = config('services.telegram_bot.username');

        if (
            $username
            && is_string($configuredUsername)
            && $configuredUsername !== ''
            && ltrim($configuredUsername, '@') !== $username
        ) {
            $this->warn("TELEGRAM_BOT_USERNAME token botiga mos emas: {$configuredUsername}. To'g'ri qiymat: @{$username}");
        }

        if (! $ok) {
            $this->warn('Telegram API xabari: storage/logs/laravel.log faylini tekshiring.');
        }

        $webhook = $bot->getWebhookInfo();
        $webhookUrl = $webhook['url'] ?? '';
        $this->line('Webhook: '.($webhookUrl ? $webhookUrl : 'yo\'q'));
        $this->line('Webhook pending updates: '.($webhook['pending_update_count'] ?? 0));

        if (! empty($webhook['last_error_message'])) {
            $this->warn('Webhook oxirgi xato: '.$webhook['last_error_message']);
        }

        if (! $webhookUrl) {
            $this->warn('Webhook o\'rnatilmagan. php artisan telegram:set-webhook https://... buyrug\'ini ishlating.');
        }

        if ($schemaReady) {
            $linkedUsers = User::query()
                ->whereNotNull('telegram_chat_id')
                ->count();

            $enabledUsers = User::query()
                ->whereNotNull('telegram_chat_id')
                ->where('telegram_notifications_enabled', true)
                ->count();

            $this->line("Telegram ulangan foydalanuvchilar: {$linkedUsers}");
            $this->line("Telegram xabarnomasi yoqilganlar: {$enabledUsers}");
        }

        if (! $ok || ! $schemaReady) {
            return self::FAILURE;
        }

        $this->components->info('Telegram bot ishlashga tayyor.');

        return self::SUCCESS;
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

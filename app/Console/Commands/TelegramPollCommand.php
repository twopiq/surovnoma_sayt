<?php

namespace App\Console\Commands;

use App\TelegramBot\TelegramSdkBot;
use App\TelegramBot\TelegramUpdateHandler;
use Illuminate\Console\Command;
use Throwable;

/**
 * Lokal sinov uchun: webhook o'rniga Telegram yangilanishlarini o'zi so'rab oladi (long polling).
 * Serverdagi bot tokeni bilan ishlatmang — u webhook'ni buzadi. Lokal uchun alohida test bot oching.
 */
class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll {--once : Bitta so\'rovdan keyin to\'xtash}';

    protected $description = "Lokal sinov: Telegram xabarlarini webhook'siz qabul qilish (long polling)";

    public function handle(TelegramSdkBot $bot, TelegramUpdateHandler $handler): int
    {
        $webhook = $bot->getWebhookInfo();

        if (! empty($webhook['url'])) {
            $this->error("Bu bot uchun webhook o'rnatilgan: {$webhook['url']}");
            $this->line("Polling webhook bilan birga ishlamaydi. Lokal sinov uchun @BotFather'da alohida test bot oching");
            $this->line("va uning tokenini lokal .env dagi TELEGRAM_BOT_TOKEN / TELEGRAM_BOT_USERNAME ga yozing.");
            $this->line("Serverdagi bot webhook'ini o'chirmang — aks holda saytdagi bot ishlamay qoladi.");

            return self::FAILURE;
        }

        $this->info('Telegram polling ishga tushdi. To\'xtatish: Ctrl+C');
        $offset = 0;

        do {
            $updates = $bot->getUpdates($offset);

            if ($updates === [] && $bot->lastError()) {
                $this->warn('Telegram xatosi: '.$bot->lastError());
                sleep(3);
            }

            foreach ($updates as $update) {
                $offset = max($offset, (int) ($update['update_id'] ?? 0) + 1);

                try {
                    $handler->handle($update);
                    $this->line('['.now()->format('H:i:s').'] yangilanish #'.($update['update_id'] ?? '?').' qayta ishlandi');
                } catch (Throwable $e) {
                    report($e);
                    $this->warn('Xato: '.$e->getMessage());
                }
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }
}

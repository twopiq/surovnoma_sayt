<?php

namespace App\Http\Controllers;

use App\TelegramBot\TelegramSdkBot;
use App\TelegramBot\TelegramUpdateHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramSdkBot $bot, TelegramUpdateHandler $handler, ?string $secret = null): Response
    {
        $configuredSecret = config('services.telegram_bot.webhook_secret');

        if (is_string($configuredSecret) && $configuredSecret !== '') {
            $headerSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token');
            $pathSecret = (string) $secret;

            abort_unless(
                hash_equals($configuredSecret, $headerSecret) || hash_equals($configuredSecret, $pathSecret),
                403,
            );
        }

        $handler->handle($bot->webhookUpdate($request));

        return response()->noContent();
    }
}

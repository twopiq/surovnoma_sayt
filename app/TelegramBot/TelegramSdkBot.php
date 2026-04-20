<?php

namespace App\TelegramBot;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\HttpClients\GuzzleHttpClient;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\Update;

class TelegramSdkBot
{
    protected ?Api $api = null;

    public function sendMessage(string $chatId, TelegramMessage $message): bool
    {
        return $this->call(function (Api $api) use ($chatId, $message): void {
            $api->sendMessage($message->toPayload($chatId));
        });
    }

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): bool
    {
        return $this->call(function (Api $api) use ($callbackQueryId, $text): void {
            $payload = ['callback_query_id' => $callbackQueryId];

            if ($text) {
                $payload['text'] = $text;
            }

            $api->answerCallbackQuery($payload);
        });
    }

    public function setWebhook(string $url, ?string $secret = null): bool
    {
        return $this->call(function (Api $api) use ($url, $secret): void {
            $payload = [
                'url' => $url,
                'allowed_updates' => [
                    'message',
                    'edited_message',
                    'callback_query',
                ],
            ];

            if ($secret) {
                $payload['secret_token'] = $secret;
            }

            $api->setWebhook($payload);
        });
    }

    public function deleteWebhook(): bool
    {
        return $this->call(function (Api $api): void {
            $api->deleteWebhook();
        });
    }

    public function getMeUsername(): ?string
    {
        $me = $this->callAndReturn(fn (Api $api) => $api->getMe());
        $username = $me?->get('username');

        return is_string($username) && $username !== '' ? $username : null;
    }

    public function getWebhookInfo(): array
    {
        $webhookInfo = $this->callAndReturn(fn (Api $api) => $api->getWebhookInfo());

        return $webhookInfo?->toArray() ?? [];
    }

    public function webhookUpdate(Request $request): array
    {
        return (new Update($request->all()))->toArray();
    }

    public function inlineKeyboard(array $rows): Keyboard
    {
        $keyboard = Keyboard::make()->inline();

        foreach ($rows as $row) {
            $keyboard->row(array_map(
                fn (array $button) => Keyboard::inlineButton($button),
                $row,
            ));
        }

        return $keyboard;
    }

    protected function api(): Api
    {
        if ($this->api instanceof Api) {
            return $this->api;
        }

        $token = config('services.telegram_bot.token');

        if (! is_string($token) || $token === '') {
            throw new TelegramSDKException('TELEGRAM_BOT_TOKEN is not configured.');
        }

        $httpClient = new GuzzleHttpClient(new Client([
            'verify' => (bool) config('services.telegram_bot.verify_ssl', true),
        ]));

        return $this->api = new Api($token, false, $httpClient);
    }

    protected function call(callable $callback): bool
    {
        try {
            $callback($this->api());

            return true;
        } catch (\Throwable $exception) {
            $this->logException($exception);

            return false;
        }
    }

    protected function callAndReturn(callable $callback): mixed
    {
        try {
            return $callback($this->api());
        } catch (\Throwable $exception) {
            $this->logException($exception);

            return null;
        }
    }

    protected function logException(\Throwable $exception): void
    {
        Log::warning('Telegram SDK request failed.', [
            'message' => $this->sanitizeToken($exception->getMessage()),
        ]);
    }

    protected function sanitizeToken(string $message): string
    {
        $token = config('services.telegram_bot.token');

        if (! is_string($token) || $token === '') {
            return $message;
        }

        return str_replace($token, '[telegram-token]', $message);
    }
}

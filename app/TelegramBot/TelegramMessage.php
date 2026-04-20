<?php

namespace App\TelegramBot;

use Illuminate\Support\Str;

class TelegramMessage
{
    public function __construct(
        public readonly string $title,
        public readonly string $body = '',
        public readonly ?string $url = null,
        public readonly array $buttons = [],
    ) {
    }

    public function toPayload(string $chatId): array
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $this->text(),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        $buttons = $this->buttons;

        if ($this->url) {
            $buttons[] = [
                [
                    'text' => 'Ochish',
                    'url' => $this->absoluteUrl(),
                ],
            ];
        }

        if ($buttons !== []) {
            $payload['reply_markup'] = app(TelegramSdkBot::class)->inlineKeyboard($buttons);
        }

        return $payload;
    }

    protected function text(): string
    {
        $lines = ['<b>'.$this->escape($this->title).'</b>'];

        if ($this->body !== '') {
            $lines[] = $this->escape($this->body);
        }

        return implode("\n", $lines);
    }

    protected function absoluteUrl(): string
    {
        if (Str::startsWith($this->url, ['http://', 'https://'])) {
            return $this->url;
        }

        return url($this->url);
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

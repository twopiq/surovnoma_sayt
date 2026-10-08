<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

/**
 * storage/logs dagi Laravel log fayllarini o'qiydi: oxirgi qismidan yozuvlarni ajratadi, maxfiy qiymatlarni yashiradi.
 */
class LogReader
{
    public const LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    /** Fayl oxiridan o'qiladigan hajm (katta loglarda sahifa sekinlashmasligi uchun). */
    private const TAIL_BYTES = 2 * 1024 * 1024;

    private const ENTRY_START = '/^\[(\d{4}-\d{2}-\d{2}[ T][0-9:.+\-]+)\] ([\w-]+)\.(\w+): /';

    public function directory(): string
    {
        return storage_path('logs');
    }

    /** @return Collection<int, array{name: string, size: int, modified_at: Carbon}> */
    public function files(): Collection
    {
        if (! File::isDirectory($this->directory())) {
            return collect();
        }

        return collect(File::files($this->directory()))
            ->filter(fn ($file) => $file->getExtension() === 'log')
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values()
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'modified_at' => Carbon::createFromTimestamp($file->getMTime()),
            ]);
    }

    public function path(string $name): ?string
    {
        $path = $this->directory().DIRECTORY_SEPARATOR.basename($name);

        return str_ends_with($path, '.log') && File::exists($path) ? $path : null;
    }

    /**
     * @return Collection<int, array{time: ?Carbon, env: string, level: string, message: string, context: string}>
     */
    public function entries(string $name, ?string $level = null, ?string $search = null, int $limit = 200): Collection
    {
        $path = $this->path($name);

        if (! $path) {
            return collect();
        }

        $entries = [];
        $current = null;

        foreach (preg_split('/\R/', $this->tail($path)) as $line) {
            if (preg_match(self::ENTRY_START, $line, $match)) {
                if ($current) {
                    $entries[] = $current;
                }

                $current = [
                    'time' => $this->parseTime($match[1]),
                    'env' => $match[2],
                    'level' => strtolower($match[3]),
                    'message' => substr($line, strlen($match[0])),
                    'context' => '',
                ];
            } elseif ($current) {
                $current['context'] .= $line."\n";
            }
        }

        if ($current) {
            $entries[] = $current;
        }

        return collect(array_reverse($entries))
            ->when($level, fn (Collection $items) => $items->where('level', $level))
            ->when($search, fn (Collection $items) => $items->filter(
                fn (array $entry) => stripos($entry['message'].' '.$entry['context'], (string) $search) !== false
            ))
            ->take($limit)
            ->map(fn (array $entry) => [
                ...$entry,
                'message' => $this->mask($entry['message']),
                'context' => $this->mask(rtrim($entry['context'])),
            ])
            ->values();
    }

    /** @return array<string, int> oxirgi N soatdagi yozuvlar soni daraja bo'yicha (eng yangi fayl) */
    public function recentCounts(int $hours = 24): array
    {
        $latest = $this->files()->first();

        if (! $latest) {
            return [];
        }

        $since = now()->subHours($hours);

        return $this->entries($latest['name'], limit: PHP_INT_MAX)
            ->filter(fn (array $entry) => $entry['time'] && $entry['time']->gte($since))
            ->countBy('level')
            ->all();
    }

    /** Tokenlar, parollar va kalitlarni yashiradi. */
    public function mask(string $text): string
    {
        return preg_replace([
            '/\b(Bearer)\s+[A-Za-z0-9._\-]{16,}/i',                                                 // avval — "Authorization: Bearer x" to'liq yashirilsin
            '/\b\d{6,12}:[A-Za-z0-9_-]{30,}\b/',                                                  // Telegram bot token
            '/\bbase64:[A-Za-z0-9+\/=]{20,}/',                                                      // APP_KEY
            '/("?(?:password|passwd|secret|token|api[_-]?key)"?\s*[:=]\s*"?)[^"\s,}]+/i',
        ], [
            '$1 [YASHIRILDI]',
            '[TOKEN]',
            '[KEY]',
            '$1[YASHIRILDI]',
        ], $text) ?? $text;
    }

    private function tail(string $path): string
    {
        $size = filesize($path) ?: 0;
        $handle = fopen($path, 'rb');

        if (! $handle) {
            return '';
        }

        if ($size > self::TAIL_BYTES) {
            fseek($handle, -self::TAIL_BYTES, SEEK_END);
            fgets($handle); // yarim qatorni tashlab yuborish
        }

        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $content;
    }

    private function parseTime(string $value): ?Carbon
    {
        try {
            return Carbon::parse($value)->setTimezone(config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TailLaravelLogCommand extends Command
{
    protected $signature = 'logs:tail {--lines=80 : Number of recent lines to show first}';

    protected $description = 'Tail the Laravel log file without requiring the pcntl extension';

    public function handle(): int
    {
        $path = storage_path('logs/laravel.log');

        if (! File::exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, '');
        }

        $this->line("<info>Log kuzatuv boshlandi:</info> {$path}");
        $this->line('<comment>To\'xtatish uchun Ctrl+C bosing.</comment>');

        $this->printRecentLines($path, max(0, (int) $this->option('lines')));

        $position = filesize($path) ?: 0;

        while (true) {
            clearstatcache(true, $path);
            $size = filesize($path) ?: 0;

            if ($size < $position) {
                $position = 0;
            }

            if ($size > $position) {
                $handle = fopen($path, 'rb');

                if ($handle !== false) {
                    fseek($handle, $position);
                    echo stream_get_contents($handle);
                    $position = ftell($handle) ?: $size;
                    fclose($handle);
                }
            }

            sleep(1);
        }
    }

    protected function printRecentLines(string $path, int $lines): void
    {
        if ($lines === 0) {
            return;
        }

        $content = File::get($path);

        if ($content === '') {
            return;
        }

        $recentLines = array_slice(preg_split('/\R/', rtrim($content)) ?: [], -$lines);

        if ($recentLines !== []) {
            $this->line(implode(PHP_EOL, $recentLines));
        }
    }
}

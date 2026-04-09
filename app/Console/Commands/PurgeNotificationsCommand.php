<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeNotificationsCommand extends Command
{
    protected $signature = 'notifications:purge-expired';

    protected $description = 'O‘tgan kundagi eski notificationlarni avtomatik tozalaydi';

    public function handle(): int
    {
        $deleted = DB::table('notifications')
            ->where('created_at', '<', now()->startOfDay())
            ->delete();

        $this->info("Tozalangan notificationlar soni: {$deleted}");

        return self::SUCCESS;
    }
}

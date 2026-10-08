<?php

namespace App\Console\Commands;

use App\Models\TicketAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MakeAttachmentsPrivateCommand extends Command
{
    protected $signature = 'attachments:make-private';

    protected $description = "Ochiq (public) diskdagi murojaat fayllarini yopiq (local) diskka ko'chiradi";

    public function handle(): int
    {
        $public = Storage::disk('public');
        $local = Storage::disk('local');
        $moved = 0;
        $missing = 0;

        TicketAttachment::query()->where('disk', 'public')->lazyById()->each(function (TicketAttachment $attachment) use ($public, $local, &$moved, &$missing) {
            if (! $public->exists($attachment->path)) {
                $missing++;
                $this->warn("Topilmadi: #{$attachment->id} {$attachment->path}");

                return;
            }

            $local->writeStream($attachment->path, $public->readStream($attachment->path));
            $attachment->update(['disk' => 'local']);
            $public->delete($attachment->path);
            $moved++;
        });

        $this->info("Ko'chirildi: {$moved}, topilmadi: {$missing}.");

        return self::SUCCESS;
    }
}

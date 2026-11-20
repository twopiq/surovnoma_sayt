<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Pochta sozlamalarini tekshirish: berilgan manzilga sinov xati yuboradi. */
class MailTestCommand extends Command
{
    protected $signature = 'mail:test {to : Sinov xati yuboriladigan email}';

    protected $description = 'Pochta (SMTP) sozlamalarini tekshirish uchun sinov xati yuboradi';

    public function handle(): int
    {
        $to = (string) $this->argument('to');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error("Email manzil noto'g'ri: {$to}");

            return self::FAILURE;
        }

        $this->line('Pochta turi: '.config('mail.default').' | Yuboruvchi: '.config('mail.from.address').' ('.config('mail.from.name').')');

        try {
            Mail::raw("Bu sinov xati. Agar uni olgan bo'lsangiz, saytning pochta sozlamalari ishlayapti.", function ($message) use ($to) {
                $message->to($to)->subject(config('app.name').': sinov xati');
            });
        } catch (Throwable $e) {
            $this->error('Xat yuborilmadi: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(config('mail.default') === 'log'
            ? "Xat yuborilmadi, faqat jurnalga yozildi: .env da MAIL_MAILER=log turibdi. Institut pochtasi sozlamalarini kiriting."
            : "Xat yuborildi: {$to}. Pochta qutisini (va spam papkasini) tekshiring.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Parolni tiklash')
            ->greeting('Assalomu alaykum!')
            ->line('Siz ushbu xabarni akkauntingiz uchun parolni tiklash so\'rovi yuborilgani sababli olmoqdasiz.')
            ->action('Parolni tiklash', $url)
            ->line('Ushbu parolni tiklash havolasi ' . config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60) . ' daqiqadan keyin muddati tugaydi.')
            ->line('Agar siz parolni tiklashni so\'ramagan bo\'lsangiz, hech qanday harakat talab etilmaydi.')
            ->salutation('Hurmat bilan, ' . config('app.name'));
    }
}

<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject(__('Parolni tiklash'))
            ->greeting(__('Assalomu alaykum!'))
            ->line(__("Siz ushbu xabarni akkauntingiz uchun parolni tiklash so'rovi yuborilgani sababli olmoqdasiz."))
            ->action(__('Parolni tiklash'), $url)
            ->line(__('Ushbu parolni tiklash havolasi :n daqiqadan keyin muddati tugaydi.', ['n' => $minutes]))
            ->line(__("Agar siz parolni tiklashni so'ramagan bo'lsangiz, hech qanday harakat talab etilmaydi."))
            ->salutation(__('Hurmat bilan, :name', ['name' => __(config('app.name'))]));
    }
}

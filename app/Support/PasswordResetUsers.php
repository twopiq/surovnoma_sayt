<?php

namespace App\Support;

use App\Models\User;

/** Parolni tiklash: foydalanuvchini email yoki login bo'yicha topish (kirish sahifasidagi bilan bir xil qoida). */
class PasswordResetUsers
{
    public static function find(string $identifier): ?User
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'login';

        return User::query()->where($field, $identifier)->first();
    }

    /**
     * Tiklash kodi yuboriladigan Telegram chatlari. Bildirishnomalar o'chirilgan bo'lsa ham kod yuboriladi:
     * bu xavfsizlik xabari, foydalanuvchi o'zi so'ragan.
     *
     * @return list<string>
     */
    public static function telegramChats(User $user): array
    {
        $extra = $user->canLinkMultipleTelegram() ? $user->telegramAccounts->pluck('chat_id')->all() : [];

        return array_values(array_unique(array_filter(array_map('strval', [$user->telegram_chat_id, ...$extra]))));
    }
}

<?php

namespace App\Enums;

enum UserRole: string
{
    case Requester = 'requester';
    case Operator = 'operator';
    case Admin = 'admin';
    case Executor = 'executor';
    case Manager = 'manager';

    public function label(?string $locale = null): string
    {
        return match ($this) {
            self::Requester => __('Murojaatchi', [], $locale),
            self::Operator => __('Operator', [], $locale),
            self::Admin => __('Admin', [], $locale),
            self::Executor => __('Ijrochi', [], $locale),
            self::Manager => __('Rahbar', [], $locale),
        };
    }

    public static function values(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }
}

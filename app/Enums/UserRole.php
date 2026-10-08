<?php

namespace App\Enums;

enum UserRole: string
{
    case Requester = 'requester';
    case Operator = 'operator';
    case Admin = 'admin';
    case Executor = 'executor';
    case Manager = 'manager';

    public function label(): string
    {
        return match ($this) {
            self::Requester => __('Murojaatchi'),
            self::Operator => __('Operator'),
            self::Admin => __('Admin'),
            self::Executor => __('Ijrochi'),
            self::Manager => __('Rahbar'),
        };
    }

    public static function values(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }
}

<?php

namespace App\Enums;

enum ExternalStatus: string
{
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case Overdue = 'overdue';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(?string $locale = null): string
    {
        return match ($this) {
            self::Accepted => __('Qabul qilindi', [], $locale),
            self::InProgress => __('Jarayonda', [], $locale),
            self::Overdue => __('Kechikkan', [], $locale),
            self::Closed => __('Yopildi', [], $locale),
            self::Rejected => __('Rad etildi', [], $locale),
            self::Cancelled => __('Bekor qilindi', [], $locale),
        };
    }
}

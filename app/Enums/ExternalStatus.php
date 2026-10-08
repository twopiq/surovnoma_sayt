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

    public function label(): string
    {
        return match ($this) {
            self::Accepted => __('Qabul qilindi'),
            self::InProgress => __('Jarayonda'),
            self::Overdue => __('Kechikkan'),
            self::Closed => __('Yopildi'),
            self::Rejected => __('Rad etildi'),
            self::Cancelled => __('Bekor qilindi'),
        };
    }
}

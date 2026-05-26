<?php

namespace App\Enums;

enum ExternalStatus: string
{
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case Overdue = 'overdue';
    case Closed = 'closed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Accepted => 'Qabul qilindi',
            self::InProgress => 'Jarayonda',
            self::Overdue => 'Kechikkan',
            self::Closed => 'Yopildi',
            self::Rejected => 'Rad etildi',
        };
    }
}

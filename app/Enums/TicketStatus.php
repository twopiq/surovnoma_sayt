<?php

namespace App\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Returned = 'returned';
    case Completed = 'completed';
    case Closed = 'closed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Yangi',
            self::Assigned => 'Taqsimlandi',
            self::InProgress => 'Jarayonda',
            self::Returned => 'Qaytarildi',
            self::Completed => 'Bajarildi',
            self::Closed => 'Yopildi',
            self::Rejected => 'Rad etildi',
        };
    }
}

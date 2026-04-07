<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Past',
            self::Medium => 'O‘rta',
            self::High => 'Yuqori',
            self::Urgent => 'Shoshilinch',
        };
    }
}

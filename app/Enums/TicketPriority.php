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
            self::Medium => "O'rta",
            self::High => 'Yuqori',
            self::Urgent => 'Shoshilinch',
        };
    }

    public function workloadUnits(): int
    {
        return match ($this) {
            self::Low => 6,
            self::Medium => 10,
            self::High => 15,
            self::Urgent => 24,
        };
    }
}

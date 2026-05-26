<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';
    case Unassigned = 'unassigned';

    /**
     * @return array<int, self>
     */
    public static function assignableCases(): array
    {
        return [
            self::Low,
            self::Medium,
            self::High,
            self::Urgent,
        ];
    }

    /**
     * @return array<int, self>
     */
    public static function dashboardCases(): array
    {
        return [
            self::Urgent,
            self::High,
            self::Medium,
            self::Low,
            self::Unassigned,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Past',
            self::Medium => "O'rta",
            self::High => 'Yuqori',
            self::Urgent => 'Shoshilinch',
            self::Unassigned => 'Belgilanmagan',
        };
    }

    public function workloadUnits(): int
    {
        return match ($this) {
            self::Low => 6,
            self::Medium => 10,
            self::High => 15,
            self::Urgent => 24,
            self::Unassigned => 0,
        };
    }
}

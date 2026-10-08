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

    /** Dizayndagi 4 ustunli chiziq belgisi uchun daraja (0–4). */
    public function level(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Urgent => 4,
            self::Unassigned => 0,
        };
    }

    /** Dizayndagi holat rangi (Deadline kartalari badge'i uchun). */
    public function tone(): string
    {
        return match ($this) {
            self::Urgent => 'new',
            self::High => 'in-progress',
            self::Medium => 'assigned',
            self::Low => 'completed',
            self::Unassigned => 'closed',
        };
    }
}

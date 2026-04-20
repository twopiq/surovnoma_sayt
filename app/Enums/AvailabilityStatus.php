<?php

namespace App\Enums;

enum AvailabilityStatus: string
{
    case Active = 'active';
    case Busy = 'busy';
    case Offline = 'offline';
    case Vacation = 'vacation';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Faol',
            self::Busy => 'Band',
            self::Offline => 'Ishda emas',
            self::Vacation => "Ta'til",
        };
    }
}

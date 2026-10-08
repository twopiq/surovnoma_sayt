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
            self::Active => __('Faol'),
            self::Busy => __('Band'),
            self::Offline => __('Ishda emas'),
            self::Vacation => __("Ta'til"),
        };
    }
}

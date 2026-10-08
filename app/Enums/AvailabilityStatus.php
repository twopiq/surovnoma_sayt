<?php

namespace App\Enums;

enum AvailabilityStatus: string
{
    case Active = 'active';
    case Busy = 'busy';
    case Offline = 'offline';
    case Vacation = 'vacation';

    public function label(?string $locale = null): string
    {
        return match ($this) {
            self::Active => __('Faol', [], $locale),
            self::Busy => __('Band', [], $locale),
            self::Offline => __('Ishda emas', [], $locale),
            self::Vacation => __("Ta'til", [], $locale),
        };
    }
}

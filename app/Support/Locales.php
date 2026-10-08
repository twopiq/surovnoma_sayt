<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Sayt tillari. Matnlar o'zbekcha yoziladi va `__('...')` orqali `lang/ru.json`, `lang/en.json` dan tarjima olinadi.
 */
class Locales
{
    /** kod => [o'z tilidagi nomi, qisqa belgi, Carbon lokali] */
    public const AVAILABLE = [
        'uz' => ["O'zbekcha", 'UZ', 'uz_Latn'],
        'ru' => ['Русский', 'RU', 'ru'],
        'en' => ['English', 'EN', 'en'],
    ];

    public const SESSION_KEY = 'locale';

    public static function isSupported(?string $locale): bool
    {
        return is_string($locale) && array_key_exists($locale, self::AVAILABLE);
    }

    public static function apply(string $locale): void
    {
        $locale = self::isSupported($locale) ? $locale : 'uz';

        app()->setLocale($locale);
        Carbon::setLocale(self::AVAILABLE[$locale][2]);
    }
}

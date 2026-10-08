<?php

namespace App\Support;

/**
 * Bazada o'zbekcha saqlangan matnlarni (audit, holat tarixi izohi, bildirishnoma, blok tafsiloti) ko'rsatishda
 * joriy tilga o'giradi. Statik matn — to'g'ridan-to'g'ri kalit; o'zgaruvchili matn — lang/{locale}.json dagi
 * ":param" li kalit shabloniga moslab, qiymatlari ajratib olinadi va tarjimaga qo'yiladi.
 * Yangi saqlanadigan matn shunday yozilsin: __('Kalit :param', [...], 'uz') — shunda u ham o'giriladi.
 */
class StoredText
{
    /** @var array<string, array<int, array{0: string, 1: string, 2: array<int, string>}>> */
    private static array $patterns = [];

    public static function translate(?string $text, ?string $locale = null): string
    {
        $text = (string) $text;
        $locale ??= app()->getLocale();

        if ($text === '' || $locale === 'uz') {
            return $text;
        }

        $direct = __($text, [], $locale);

        if ($direct !== $text) {
            return $direct;
        }

        foreach (self::patterns($locale) as [$key, $regex, $names]) {
            if (! preg_match($regex, $text, $m)) {
                continue;
            }

            $params = [];

            foreach ($names as $i => $name) {
                $value = $m[$i + 1];
                // Qiymat o'zi ham tarjima kaliti bo'lishi mumkin (holat nomi, sabab va h.k.)
                $params[$name] = $value === '' ? '' : self::translate($value, $locale);
            }

            return __($key, $params, $locale);
        }

        // "RTT-...: Matn" / "Sabab: matn" — ikki qismga bo'lib, har birini alohida o'girish
        if (preg_match('/^(.+?): (.+)$/su', $text, $m)) {
            $head = self::translate($m[1], $locale);
            $tail = self::translate($m[2], $locale);

            if ($head !== $m[1] || $tail !== $m[2]) {
                return "{$head}: {$tail}";
            }
        }

        return $text;
    }

    /** Ko'p qatorli matn (masalan Telegram ogohlantirishi) — har qatorni alohida o'giradi. */
    public static function translateLines(?string $text, ?string $locale = null): string
    {
        return collect(preg_split('/\R/', (string) $text))
            ->map(fn (string $line) => self::translate($line, $locale))
            ->implode("\n");
    }

    private static function patterns(string $locale): array
    {
        if (isset(self::$patterns[$locale])) {
            return self::$patterns[$locale];
        }

        $path = lang_path("{$locale}.json");
        $keys = is_file($path) ? array_keys(json_decode((string) file_get_contents($path), true) ?: []) : [];
        $list = [];

        foreach ($keys as $key) {
            if (! preg_match_all('/:([a-z_]+)/i', $key, $found) || ! preg_match('/[A-Za-z]{3,}/', preg_replace('/:[a-z_]+/i', '', $key))) {
                continue;
            }

            $parts = preg_split('/:[a-z_]+/i', $key);
            $regex = '/^'.implode('(.*?)', array_map(fn ($part) => preg_quote($part, '/'), $parts)).'$/su';
            $list[] = [$key, $regex, $found[1], strlen(implode('', $parts))];
        }

        // Uzunroq statik qismli shablon birinchi — aniqroq moslik
        usort($list, fn ($a, $b) => $b[3] <=> $a[3]);

        return self::$patterns[$locale] = array_map(fn ($row) => array_slice($row, 0, 3), $list);
    }
}

<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * F.I.Sh. dan login takliflari: faqat Familiya va Ism olinadi (otasining ismi, "o'g'li/qizi" — yo'q),
 * apostrof va qo'shtirnoqlar tashlanadi, uzunlik 20 belgidan oshmaydi.
 */
class LoginSuggester
{
    public const MAX_LENGTH = 20;

    public const MIN_LENGTH = 3;

    /** Login formati: kichik lotin harflari, raqamlar, nuqta yoki pastki chiziq bilan ajratilgan. */
    public const PATTERN = '/^[a-z0-9]+(?:[._][a-z0-9]+)*$/';

    private const QUOTES = ["'", '`', '"', 'ʻ', 'ʼ', '‘', '’', '‛', '“', '”', '„', '´', 'ʹ', 'ˈ', '′', '″', '«', '»'];

    /**
     * @return list<string> band bo'lmagan takliflar (birinchisi — eng tavsiya etilgani)
     */
    public static function suggest(string $fullName, int $limit = 5, ?int $ignoreUserId = null): array
    {
        [$surname, $name] = self::parts($fullName);

        $patterns = array_filter([
            $name && $surname ? "{$name}.{$surname}" : null,
            $name && $surname ? $name[0].'.'.$surname : null,
            $name && $surname ? "{$surname}.{$name}" : null,
            $name && $surname ? $surname.'.'.$name[0] : null,
            $name && $surname ? $name.$surname : null,
            $surname ?: $name,
        ]);

        // So'zni o'rtasidan kesmaslik uchun 20 belgiga sig'maganlar tashlanadi; hech biri sig'masa — kesilgan variant
        $fitting = array_values(array_filter($patterns, fn (string $pattern) => strlen($pattern) <= self::MAX_LENGTH));
        $patterns = $fitting !== [] ? $fitting : array_map(fn (string $pattern) => self::fit($pattern), $patterns);

        $suggestions = [];

        foreach ($patterns as $pattern) {
            $candidate = self::unique(self::fit($pattern), $ignoreUserId);

            if ($candidate !== null && ! in_array($candidate, $suggestions, true)) {
                $suggestions[] = $candidate;
            }

            if (count($suggestions) >= $limit) {
                break;
            }
        }

        return $suggestions !== [] ? $suggestions : [self::unique('user', $ignoreUserId) ?? 'user'.random_int(1000, 9999)];
    }

    /** F.I.Sh. → [familiya, ism] (ASCII, kichik harf, tinish belgilarisiz). */
    public static function parts(string $fullName): array
    {
        $words = collect(preg_split('/\s+/u', trim(str_replace(self::QUOTES, '', $fullName))))
            ->map(fn (string $word) => Str::of($word)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '')->value())
            ->filter()
            ->values();

        return [$words[0] ?? '', $words[1] ?? ''];
    }

    /** Formadagi login maydoni qoidalari (bo'sh qolsa — avtomatik birinchi taklif olinadi). */
    public static function rules(): array
    {
        return ['nullable', 'string', 'min:'.self::MIN_LENGTH, 'max:'.self::MAX_LENGTH, 'regex:'.self::PATTERN, 'unique:users,login'];
    }

    public static function messages(): array
    {
        return [
            'login.min' => 'Login kamida '.self::MIN_LENGTH.' belgidan iborat bo\'lsin.',
            'login.max' => 'Login '.self::MAX_LENGTH.' belgidan oshmasin.',
            'login.regex' => "Loginda faqat kichik lotin harflari, raqamlar, nuqta yoki pastki chiziq bo'lsin (masalan: behzod.qurbonov).",
            'login.unique' => 'Bu login band. Takliflardan birini tanlang.',
        ];
    }

    public static function isValid(string $login): bool
    {
        return strlen($login) >= self::MIN_LENGTH
            && strlen($login) <= self::MAX_LENGTH
            && preg_match(self::PATTERN, $login) === 1;
    }

    /** 20 belgiga sig'diradi, oxirida nuqta qolmaydi. */
    private static function fit(string $login, int $max = self::MAX_LENGTH): string
    {
        return rtrim(substr($login, 0, $max), '._');
    }

    /** Band bo'lsa raqam qo'shadi (uzunlik baribir 20 dan oshmaydi). */
    private static function unique(string $base, ?int $ignoreUserId): ?string
    {
        if (strlen($base) < self::MIN_LENGTH) {
            return null;
        }

        $taken = fn (string $login) => User::query()
            ->when($ignoreUserId, fn ($query) => $query->whereKeyNot($ignoreUserId))
            ->where('login', $login)
            ->exists();

        if (! $taken($base)) {
            return $base;
        }

        for ($i = 1; $i < 1000; $i++) {
            $candidate = self::fit($base, self::MAX_LENGTH - strlen((string) $i)).$i;

            if (! $taken($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}

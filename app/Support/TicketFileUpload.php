<?php

namespace App\Support;

class TicketFileUpload
{
    public const MAX_FILES = 5;

    public const MAX_FILE_SIZE_KB = 5120;

    public const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'pdf',
        'doc',
        'docx',
    ];

    public static function optionalRules(string $field, ?int $maxFiles = null, ?int $maxKb = null): array
    {
        return [
            $field => ['nullable', 'array', 'max:'.($maxFiles ?? self::MAX_FILES)],
            $field.'.*' => ['nullable', 'file', 'max:'.($maxKb ?? self::MAX_FILE_SIZE_KB), 'mimes:'.self::allowedExtensions()],
        ];
    }

    public static function requiredRules(string $field): array
    {
        return [
            $field => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES],
            $field.'.*' => ['file', 'max:'.self::MAX_FILE_SIZE_KB, 'mimes:'.self::allowedExtensions()],
        ];
    }

    public static function messages(string $field, ?int $maxFiles = null, ?int $maxKb = null): array
    {
        return [
            $field.'.required' => __('Kamida bitta tasdiqlovchi fayl yuklash kerak.'),
            $field.'.min' => __('Kamida bitta tasdiqlovchi fayl yuklash kerak.'),
            $field.'.array' => __("Fayllar noto'g'ri yuborildi."),
            $field.'.max' => self::tooManyFilesMessage($maxFiles),
            $field.'.*.file' => __("Yuklangan fayl noto'g'ri."),
            $field.'.*.mimes' => self::invalidFormatMessage(),
            $field.'.*.max' => self::fileTooLargeMessage($maxKb),
        ];
    }

    public static function allowedExtensions(): string
    {
        return implode(',', self::ALLOWED_EXTENSIONS);
    }

    public static function acceptAttribute(): string
    {
        return collect(self::ALLOWED_EXTENSIONS)
            ->map(fn (string $extension): string => '.'.$extension)
            ->implode(',');
    }

    public static function allowedFormatsLabel(): string
    {
        return collect(self::ALLOWED_EXTENSIONS)
            ->map(fn (string $extension): string => strtoupper($extension))
            ->implode('/');
    }

    public static function maxFileSizeMb(?int $maxKb = null): int|float
    {
        $mb = ($maxKb ?? self::MAX_FILE_SIZE_KB) / 1024;

        return $mb == (int) $mb ? (int) $mb : round($mb, 1);
    }

    public static function maxFileSizeLabel(?int $maxKb = null): string
    {
        return self::maxFileSizeMb($maxKb).' MB';
    }

    public static function maxTotalSizeLabel(): string
    {
        return (self::MAX_FILES * self::maxFileSizeMb()).' MB';
    }

    public static function tooManyFilesMessage(?int $maxFiles = null): string
    {
        return __("Ko'pi bilan :n ta fayl yuklash mumkin.", ['n' => $maxFiles ?? self::MAX_FILES]);
    }

    public static function invalidFormatMessage(): string
    {
        return __("Fayl formati noto'g'ri. Faqat JPG, JPEG, PNG, PDF, DOC va DOCX formatlariga ruxsat beriladi.");
    }

    public static function fileTooLargeMessage(?int $maxKb = null): string
    {
        return __('Har bir fayl hajmi :size dan oshmasligi kerak.', ['size' => self::maxFileSizeLabel($maxKb)]);
    }
}

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

    public static function optionalRules(string $field): array
    {
        return [
            $field => ['nullable', 'array', 'max:'.self::MAX_FILES],
            $field.'.*' => ['nullable', 'file', 'max:'.self::MAX_FILE_SIZE_KB, 'mimes:'.self::allowedExtensions()],
        ];
    }

    public static function requiredRules(string $field): array
    {
        return [
            $field => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES],
            $field.'.*' => ['file', 'max:'.self::MAX_FILE_SIZE_KB, 'mimes:'.self::allowedExtensions()],
        ];
    }

    public static function messages(string $field): array
    {
        return [
            $field.'.required' => 'Kamida bitta tasdiqlovchi fayl yuklash kerak.',
            $field.'.min' => 'Kamida bitta tasdiqlovchi fayl yuklash kerak.',
            $field.'.array' => "Fayllar noto'g'ri yuborildi.",
            $field.'.max' => self::tooManyFilesMessage(),
            $field.'.*.file' => "Yuklangan fayl noto'g'ri.",
            $field.'.*.mimes' => self::invalidFormatMessage(),
            $field.'.*.max' => self::fileTooLargeMessage(),
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

    public static function maxFileSizeMb(): int
    {
        return (int) (self::MAX_FILE_SIZE_KB / 1024);
    }

    public static function maxFileSizeLabel(): string
    {
        return self::maxFileSizeMb().' MB';
    }

    public static function maxTotalSizeLabel(): string
    {
        return (self::MAX_FILES * self::maxFileSizeMb()).' MB';
    }

    public static function tooManyFilesMessage(): string
    {
        return "Ko'pi bilan ".self::MAX_FILES.' ta fayl yuklash mumkin.';
    }

    public static function invalidFormatMessage(): string
    {
        return "Fayl formati noto'g'ri. Faqat JPG, JPEG, PNG, PDF, DOC va DOCX formatlariga ruxsat beriladi.";
    }

    public static function fileTooLargeMessage(): string
    {
        return 'Har bir fayl hajmi '.self::maxFileSizeLabel().' dan oshmasligi kerak.';
    }
}

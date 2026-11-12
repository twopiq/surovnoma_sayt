<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** "Ingliz tili" bo'limidagi kartochkalar to'plami; har bir to'plam bitta foydalanuvchiga tegishli. */
class EnglishSet extends Model
{
    public const KIND_WORDS = 'words';

    /** Fe'llar to'plami: har kartada uch shakl — term (V1), v2, v3. */
    public const KIND_VERBS = 'verbs';

    protected $fillable = ['title', 'description', 'kind', 'cards', 'best_time'];

    protected $attributes = ['kind' => self::KIND_WORDS];

    protected function casts(): array
    {
        return [
            'cards' => 'array',
            'best_time' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Brauzerdagi ilova kutadigan ko'rinish. */
    public function toClient(): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'desc' => (string) $this->description,
            'kind' => $this->kind,
            'cards' => $this->cards,
            'best' => $this->best_time,
        ];
    }
}

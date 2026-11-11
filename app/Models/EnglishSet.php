<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** "Ingliz tili" bo'limidagi kartochkalar to'plami; har bir to'plam bitta foydalanuvchiga tegishli. */
class EnglishSet extends Model
{
    protected $fillable = ['title', 'description', 'cards', 'best_time'];

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
            'cards' => $this->cards,
            'best' => $this->best_time,
        ];
    }
}

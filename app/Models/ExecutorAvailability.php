<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutorAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'set_by',
        'status',
        'reason',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AvailabilityStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

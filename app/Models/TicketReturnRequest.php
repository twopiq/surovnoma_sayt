<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class TicketReturnRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'executor_id',
        'reason',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    public function isPending(): bool
    {
        return $this->resolved_at === null;
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executor_id');
    }
}

<?php

namespace App\Models;

use App\Enums\TicketPriority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlaProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'priority',
        'duration_minutes',
        'warning_minutes',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'is_active' => 'boolean',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}

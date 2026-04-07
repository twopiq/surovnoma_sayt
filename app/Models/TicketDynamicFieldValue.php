<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketDynamicFieldValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'dynamic_field_id',
        'value',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function dynamicField(): BelongsTo
    {
        return $this->belongsTo(DynamicField::class);
    }
}

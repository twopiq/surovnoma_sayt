<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HolidayException extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'name',
        'is_working_override',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_working_override' => 'boolean',
        ];
    }
}

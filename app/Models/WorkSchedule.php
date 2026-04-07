<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'weekday',
        'starts_at',
        'ends_at',
        'is_working_day',
    ];

    protected function casts(): array
    {
        return [
            'is_working_day' => 'boolean',
        ];
    }
}

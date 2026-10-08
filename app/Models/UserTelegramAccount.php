<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Adminning qo'shimcha Telegram akkaunti. Asosiy akkaunt users.telegram_chat_id da saqlanadi.
 */
class UserTelegramAccount extends Model
{
    protected $fillable = ['user_id', 'chat_id', 'username', 'linked_at'];

    protected function casts(): array
    {
        return ['linked_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

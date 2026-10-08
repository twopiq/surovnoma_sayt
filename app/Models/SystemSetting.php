<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Admin paneldan o'zgartiriladigan umumiy tizim sozlamalari (kalit => qiymat).
 */
class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            if (! Schema::hasTable('system_settings')) {
                return $default;
            }

            $value = static::query()->where('key', $key)->value('value');
        } catch (\Throwable) {
            return $default;
        }

        return $value === null ? $default : $value;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => is_bool($value) ? (int) $value : $value]);
    }
}

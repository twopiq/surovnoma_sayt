<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestBlock extends Model
{
    public const SCOPE_DEVICE = 'device';
    public const SCOPE_IP = 'ip';
    public const SCOPE_CONTACT = 'contact';

    public const REASONS = [
        'device_hourly' => 'Qurilma: soatlik limit',
        'device_daily' => 'Qurilma: kunlik limit',
        'ip_hourly' => 'IP: soatlik limit',
        'ip_daily' => 'IP: kunlik limit',
        'contact_daily' => 'Email/telefon: kunlik limit',
        'bot' => 'Bot aniqlandi',
        'manual' => 'Admin tomonidan bloklangan',
    ];

    protected $fillable = [
        'scope',
        'reason',
        'description',
        'ip',
        'device_id',
        'user_agent',
        'email',
        'phone',
        'attempts_count',
        'blocked_at',
        'last_attempt_at',
        'unblocked_at',
        'unblocked_by',
    ];

    protected function casts(): array
    {
        return [
            'blocked_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'unblocked_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('unblocked_at');
    }

    public function unblocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unblocked_by');
    }

    public function isActive(): bool
    {
        return $this->unblocked_at === null;
    }

    public function registerAttempt(): void
    {
        $this->increment('attempts_count', 1, ['last_attempt_at' => now()]);
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    public function scopeLabel(): string
    {
        return match ($this->scope) {
            self::SCOPE_DEVICE => 'Qurilma',
            self::SCOPE_IP => 'IP manzil',
            self::SCOPE_CONTACT => 'Email/telefon',
            default => $this->scope,
        };
    }

    public function deviceLabel(): string
    {
        $ua = (string) $this->user_agent;

        if ($ua === '') {
            return "Noma'lum";
        }

        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'YaBrowser') => 'Yandex',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            str_contains(strtolower($ua), 'curl') || str_contains(strtolower($ua), 'python') || str_contains(strtolower($ua), 'bot') => 'Skript/bot',
            default => 'Boshqa',
        };

        $os = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => null,
        };

        return $os ? "{$browser} · {$os}" : $browser;
    }
}

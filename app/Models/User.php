<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasRoles;
    use Notifiable;

    public const EXECUTOR_MAX_WORKLOAD_UNITS = 30;

    protected string $guard_name = 'web';

    protected $fillable = [
        'name',
        'login',
        'email',
        'phone',
        'job_title',
        'department_id',
        'availability_status',
        'telegram_chat_id',
        'telegram_username',
        'telegram_link_token',
        'telegram_linked_at',
        'telegram_notifications_enabled',
        'is_active',
        'approved_at',
        'can_access_app_dashboard',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'telegram_linked_at' => 'datetime',
            'telegram_notifications_enabled' => 'boolean',
            'is_active' => 'boolean',
            'can_access_app_dashboard' => 'boolean',
            'availability_status' => AvailabilityStatus::class,
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (! $user->login) {
                $user->login = static::generateUniqueLogin($user->name);
            }
        });

        static::updating(function (User $user): void {
            if ($user->isDirty('login')) {
                $user->login = $user->getOriginal('login');
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requestedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function operatedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'operator_id');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_executor_id');
    }

    public function activeExecutorTickets(): HasMany
    {
        return $this->assignedTickets()->whereIn('status', [
            TicketStatus::Assigned->value,
            TicketStatus::InProgress->value,
            TicketStatus::Returned->value,
        ]);
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null && $this->is_active;
    }

    public function hasSystemRole(UserRole|string $role): bool
    {
        $roleValue = $role instanceof UserRole ? $role->value : $role;

        return $this->hasRole($roleValue);
    }

    public function canAccessAppDashboard(): bool
    {
        if ($this->hasSystemRole(UserRole::Admin)) {
            return true;
        }

        if ($this->hasSystemRole(UserRole::Manager)) {
            return (bool) $this->can_access_app_dashboard;
        }

        return false;
    }

    public function currentExecutorWorkloadUnits(?int $exceptTicketId = null): int
    {
        return $this->activeExecutorTickets()
            ->when($exceptTicketId, fn ($query) => $query->whereKeyNot($exceptTicketId))
            ->get(['priority'])
            ->sum(fn (Ticket $ticket) => $ticket->priority->workloadUnits());
    }

    public function remainingExecutorWorkloadUnits(?int $exceptTicketId = null): int
    {
        return max(0, self::EXECUTOR_MAX_WORKLOAD_UNITS - $this->currentExecutorWorkloadUnits($exceptTicketId));
    }

    public function executorWorkloadSummary(?int $exceptTicketId = null): array
    {
        $tickets = $this->activeExecutorTickets()
            ->when($exceptTicketId, fn ($query) => $query->whereKeyNot($exceptTicketId))
            ->get(['priority']);
        $usedUnits = $tickets->sum(fn (Ticket $ticket) => $ticket->priority->workloadUnits());

        return [
            'used_units' => $usedUnits,
            'max_units' => self::EXECUTOR_MAX_WORKLOAD_UNITS,
            'remaining_units' => max(0, self::EXECUTOR_MAX_WORKLOAD_UNITS - $usedUnits),
            'overload_units' => max(0, $usedUnits - self::EXECUTOR_MAX_WORKLOAD_UNITS),
            'counts' => $tickets->countBy(fn (Ticket $ticket) => $ticket->priority->value)->all(),
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function routeNotificationForTelegram(mixed $notification = null): ?string
    {
        return $this->telegramChatIds()[0] ?? null;
    }

    /** Admin uchun qo'shimcha Telegram akkauntlari (asosiysi telegram_chat_id). */
    public function telegramAccounts(): HasMany
    {
        return $this->hasMany(UserTelegramAccount::class)->orderBy('linked_at');
    }

    public function canLinkMultipleTelegram(): bool
    {
        return $this->hasRole(UserRole::Admin->value);
    }

    /**
     * Xabar yuboriladigan barcha chatlar: asosiy + (admin uchun) qo'shimchalar.
     *
     * @return list<string>
     */
    public function telegramChatIds(): array
    {
        if ($this->telegram_notifications_enabled === false) {
            return [];
        }

        $extra = $this->canLinkMultipleTelegram() && \Illuminate\Support\Facades\Schema::hasTable('user_telegram_accounts')
            ? $this->telegramAccounts->pluck('chat_id')->all()
            : [];

        return array_values(array_unique(array_filter([(string) $this->telegram_chat_id, ...array_map('strval', $extra)])));
    }

    /**
     * Telegram chatni akkauntga bog'laydi. Admin allaqachon ulangan bo'lsa — qo'shimcha akkaunt qo'shiladi,
     * boshqa rollarda asosiy akkaunt almashtiriladi.
     *
     * @return 'linked'|'added'|'already'
     */
    public function linkTelegramChat(string $chatId, ?string $username): string
    {
        $base = [
            'telegram_link_token' => Str::random(48),
            'telegram_notifications_enabled' => true,
        ];

        $multiReady = \Illuminate\Support\Facades\Schema::hasTable('user_telegram_accounts');

        if ((string) $this->telegram_chat_id === $chatId || ($multiReady && $this->telegramAccounts()->where('chat_id', $chatId)->exists())) {
            $this->forceFill($base)->save();

            return 'already';
        }

        if ($multiReady && $this->telegram_chat_id && $this->canLinkMultipleTelegram()) {
            $this->telegramAccounts()->create(['chat_id' => $chatId, 'username' => $username, 'linked_at' => now()]);
            $this->forceFill($base)->save();

            return 'added';
        }

        $this->forceFill([
            ...$base,
            'telegram_chat_id' => $chatId,
            'telegram_username' => $username,
            'telegram_linked_at' => now(),
        ])->save();

        return 'linked';
    }

    /**
     * Bitta chatni uzadi. Asosiy chat uzilsa, birinchi qo'shimcha akkaunt asosiyga ko'tariladi.
     */
    public function unlinkTelegramChat(string $chatId): void
    {
        $multiReady = \Illuminate\Support\Facades\Schema::hasTable('user_telegram_accounts');

        if ((string) $this->telegram_chat_id !== $chatId) {
            if ($multiReady) {
                $this->telegramAccounts()->where('chat_id', $chatId)->delete();
            }

            return;
        }

        $next = $multiReady && $this->canLinkMultipleTelegram() ? $this->telegramAccounts()->first() : null;

        $this->forceFill([
            'telegram_chat_id' => $next?->chat_id,
            'telegram_username' => $next?->username,
            'telegram_linked_at' => $next?->linked_at,
            'telegram_link_token' => Str::random(48),
        ])->save();

        $next?->delete();
    }

    /** Hamma Telegram ulanishlarini uzadi. */
    public function unlinkAllTelegram(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('user_telegram_accounts')) {
            $this->telegramAccounts()->delete();
        }

        $this->forceFill([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_link_token' => Str::random(48),
            'telegram_notifications_enabled' => true,
            'telegram_linked_at' => null,
        ])->save();
    }

    /** Login tanlanmagan bo'lsa — F.I. dan eng birinchi taklif (20 belgigacha). */
    public static function generateUniqueLogin(string $name, ?int $ignoreUserId = null): string
    {
        return \App\Support\LoginSuggester::suggest($name, 1, $ignoreUserId)[0];
    }

    protected function displayRole(): Attribute
    {
        return Attribute::get(function (): string {
            $firstRole = $this->getRoleNames()->first();

            if (! $firstRole) {
                return 'Tasdiqlanmagan';
            }

            return UserRole::from($firstRole)->label();
        });
    }

    /**
     * Dizayn tizimidagi rol aksenti (<body data-role="...">): bir nechta rol bo'lsa eng yuqorisi.
     */
    public function themeRole(): string
    {
        return match (true) {
            $this->hasRole(UserRole::Admin->value) => 'admin',
            $this->hasRole(UserRole::Manager->value) => 'rahbar',
            $this->hasRole(UserRole::Operator->value) => 'operator',
            $this->hasRole(UserRole::Executor->value) => 'ijrochi',
            default => 'murojaatchi',
        };
    }
}

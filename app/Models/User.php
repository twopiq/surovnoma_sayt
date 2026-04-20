<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
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
        return $this->assignedTickets()->where('status', TicketStatus::InProgress->value);
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

    public function currentExecutorWorkloadUnits(): int
    {
        return $this->activeExecutorTickets()
            ->get(['priority'])
            ->sum(fn (Ticket $ticket) => $ticket->priority->workloadUnits());
    }

    public function remainingExecutorWorkloadUnits(): int
    {
        return max(0, self::EXECUTOR_MAX_WORKLOAD_UNITS - $this->currentExecutorWorkloadUnits());
    }

    public function executorWorkloadSummary(): array
    {
        $tickets = $this->activeExecutorTickets()->get(['priority']);

        return [
            'used_units' => $tickets->sum(fn (Ticket $ticket) => $ticket->priority->workloadUnits()),
            'max_units' => self::EXECUTOR_MAX_WORKLOAD_UNITS,
            'remaining_units' => max(0, self::EXECUTOR_MAX_WORKLOAD_UNITS - $tickets->sum(fn (Ticket $ticket) => $ticket->priority->workloadUnits())),
            'counts' => $tickets->countBy(fn (Ticket $ticket) => $ticket->priority->value)->all(),
        ];
    }

    public function routeNotificationForTelegram(mixed $notification = null): ?string
    {
        if ($this->telegram_notifications_enabled === false) {
            return null;
        }

        return $this->telegram_chat_id;
    }

    public static function generateUniqueLogin(string $name, ?int $ignoreUserId = null): string
    {
        $base = Str::of($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->value();

        $base = $base !== '' ? $base : 'user';
        $candidate = $base;
        $suffix = 1;

        while (static::query()
            ->when($ignoreUserId, fn ($query) => $query->whereKeyNot($ignoreUserId))
            ->where('login', $candidate)
            ->exists()) {
            $candidate = "{$base}{$suffix}";
            $suffix++;
        }

        return $candidate;
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
}

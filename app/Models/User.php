<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected string $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'job_title',
        'department_id',
        'availability_status',
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
            'is_active' => 'boolean',
            'availability_status' => AvailabilityStatus::class,
            'password' => 'hashed',
        ];
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

    public function isApproved(): bool
    {
        return $this->approved_at !== null && $this->is_active;
    }

    public function hasSystemRole(UserRole|string $role): bool
    {
        $roleValue = $role instanceof UserRole ? $role->value : $role;

        return $this->hasRole($roleValue);
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

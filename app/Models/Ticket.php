<?php

namespace App\Models;

use App\Enums\ExternalStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'channel',
        'requester_id',
        'operator_id',
        'assigned_department_id',
        'assigned_executor_id',
        'category_id',
        'sla_profile_id',
        'requester_name',
        'requester_email',
        'requester_phone',
        'requester_department',
        'requester_job_title',
        'title',
        'description',
        'priority',
        'status',
        'external_status',
        'tracking_code_hash',
        'tracking_code_last_four',
        'deadline_at',
        'completed_at',
        'closed_at',
        'rejected_at',
        'rejection_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'external_status' => ExternalStatus::class,
            'deadline_at' => 'datetime',
            'completed_at' => 'datetime',
            'closed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function assignedDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'assigned_department_id');
    }

    public function assignedExecutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_executor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function slaProfile(): BelongsTo
    {
        return $this->belongsTo(SlaProfile::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(TicketReturnRequest::class);
    }

    public function hasPendingReturnRequest(): bool
    {
        if ($this->relationLoaded('returnRequests')) {
            return $this->returnRequests->contains(fn (TicketReturnRequest $request) => $request->isPending());
        }

        return $this->returnRequests()->pending()->exists();
    }

    public function dynamicFieldValues(): HasMany
    {
        return $this->hasMany(TicketDynamicFieldValue::class);
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value])) {
            return $query;
        }

        if ($user->hasSystemRole(UserRole::Executor)) {
            return $query->where(function ($innerQuery) use ($user) {
                $innerQuery
                    ->where('assigned_executor_id', $user->id)
                    ->orWhere(function ($availableQuery) {
                        $availableQuery
                            ->whereNull('assigned_executor_id')
                            ->whereIn('status', [TicketStatus::New->value, TicketStatus::Assigned->value, TicketStatus::Returned->value]);
                    });
            });
        }

        if ($user->hasSystemRole(UserRole::Operator)) {
            return $query->where('operator_id', $user->id);
        }

        return $query->where('requester_id', $user->id);
    }

    public function isOverdue(): bool
    {
        return $this->deadline_at !== null
            && $this->deadline_at->isPast()
            && ! in_array($this->status, [TicketStatus::Closed, TicketStatus::Rejected], true);
    }

    public function canExecutorClaim(): bool
    {
        return in_array($this->status, [TicketStatus::New, TicketStatus::Assigned, TicketStatus::Returned], true);
    }

    public function canExecutorAccess(User $user): bool
    {
        if ($this->assigned_executor_id === $user->id) {
            return true;
        }

        return $this->assigned_executor_id === null
            && in_array($this->status, [TicketStatus::New, TicketStatus::Assigned, TicketStatus::Returned], true);
    }

    public function canExecutorClaimBy(User $user): bool
    {
        if (! $this->canExecutorClaim()) {
            return false;
        }

        return $this->assigned_executor_id === null || $this->assigned_executor_id === $user->id;
    }

    public function canExecutorCompleteBy(User $user): bool
    {
        return $this->assigned_executor_id === $user->id
            && $this->status === TicketStatus::InProgress;
    }

    public function executorClaimLabel(): string
    {
        return match ($this->status) {
            TicketStatus::New => 'Bajarishga olish',
            TicketStatus::Assigned => $this->assigned_executor_id === null ? 'Bajarishga olish' : 'Qabul qilish',
            TicketStatus::Returned => 'Qayta qabul qilish',
            TicketStatus::InProgress => 'Qabul qilindi',
            TicketStatus::Completed => 'Bajarilgan',
            TicketStatus::Closed => 'Yopilgan',
            TicketStatus::Rejected => 'Rad etilgan',
            default => 'Mavjud emas',
        };
    }
}

<?php

namespace App\Models;

use App\Enums\ExternalStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Services\SlaCalculator;
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
                            ->whereIn('status', [TicketStatus::New->value, TicketStatus::Assigned->value, TicketStatus::Returned->value, TicketStatus::Overdue->value]);
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
            && ! in_array($this->status, [TicketStatus::Completed, TicketStatus::Closed, TicketStatus::Rejected, TicketStatus::Cancelled], true);
    }

    /** Arxiv uchun: murojaat yakunlangan vaqt (bajarilgan, rad etilgan yoki oxirgi o'zgarish). */
    public function finishedAt(): ?\Illuminate\Support\Carbon
    {
        return $this->completed_at ?? $this->rejected_at ?? $this->closed_at ?? $this->updated_at;
    }

    /** Yechim davomiyligi: "3 s 40 daq" / "2 kun 4 s". */
    public function resolutionLabel(): string
    {
        $finished = $this->finishedAt();

        if (! $this->created_at || ! $finished) {
            return '—';
        }

        $minutes = (int) abs($this->created_at->diffInMinutes($finished));

        return match (true) {
            $minutes < 60 => __(':m daq', ['m' => $minutes]),
            $minutes < 24 * 60 => __(':h s :m daq', ['h' => intdiv($minutes, 60), 'm' => $minutes % 60]),
            default => __(':d kun :h s', ['d' => intdiv($minutes, 24 * 60), 'h' => intdiv($minutes % (24 * 60), 60)]),
        };
    }

    /** Arxiv natijasi: [yorliq, ton] — Muddatida / Kechikib / Rad etilgan. */
    public function resultLabel(): array
    {
        return match (true) {
            $this->status === TicketStatus::Rejected => [__('Rad etilgan'), 'closed'],
            $this->status === TicketStatus::Cancelled => [__('Bekor qilingan'), 'closed'],
            ! $this->deadline_at || ! $this->completed_at => [__('Muddatsiz'), 'closed'],
            $this->completed_at->lte($this->deadline_at) => [__('Muddatida'), 'completed'],
            default => [__('Kechikib'), 'new'],
        };
    }

    /** Muddatning qancha qismi o'tgani (0–100) — SLA chizig'i uchun; muddat yo'q bo'lsa null. */
    public function slaElapsedPercent(): ?int
    {
        if (! $this->deadline_at || ! $this->created_at) {
            return null;
        }

        $total = max(1, $this->created_at->diffInSeconds($this->deadline_at, true));
        $elapsed = $this->created_at->diffInSeconds(now(), false);

        return (int) max(0, min(100, round($elapsed / $total * 100)));
    }

    public function receivedAtLabel(): string
    {
        return $this->created_at?->format('d.m.Y H:i') ?? '-';
    }

    public function slaDurationLabel(): string
    {
        $minutes = $this->slaProfile?->duration_minutes;

        if (! $minutes) {
            return __('Belgilanmagan');
        }

        $workdays = app(SlaCalculator::class)->workdaysFromMinutes($minutes);
        $label = rtrim(rtrim(number_format($workdays, 2, '.', ''), '0'), '.');

        return __(':n ish kuni', ['n' => $label]);
    }

    public function deadlineLabel(): string
    {
        return $this->deadline_at?->format('d.m.Y H:i') ?? __('Belgilanmagan');
    }

    /** Murojaatchi faqat o'z, hali yakunlanmagan murojaatini bekor qila oladi. */
    public function canBeCancelledBy(User $user): bool
    {
        return $this->requester_id !== null
            && $this->requester_id === $user->id
            && in_array($this->status, [TicketStatus::New, TicketStatus::Assigned, TicketStatus::InProgress, TicketStatus::Returned, TicketStatus::Overdue], true);
    }

    public function canExecutorClaim(): bool
    {
        return in_array($this->status, [TicketStatus::New, TicketStatus::Assigned, TicketStatus::Returned, TicketStatus::Overdue], true);
    }

    public function canExecutorAccess(User $user): bool
    {
        if ($this->assigned_executor_id === $user->id) {
            return true;
        }

        return $this->assigned_executor_id === null
            && in_array($this->status, [TicketStatus::New, TicketStatus::Assigned, TicketStatus::Returned, TicketStatus::Overdue], true);
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
            TicketStatus::New => __('Bajarishga olish'),
            TicketStatus::Assigned => $this->assigned_executor_id === null ? __('Bajarishga olish') : __('Qabul qilish'),
            TicketStatus::Returned => __('Qayta qabul qilish'),
            TicketStatus::Overdue => __('Kechikkan murojaatni olish'),
            TicketStatus::InProgress => __('Qabul qilindi'),
            TicketStatus::Completed => __('Bajarilgan'),
            TicketStatus::Closed => __('Yopilgan'),
            TicketStatus::Rejected => __('Rad etilgan'),
            TicketStatus::Cancelled => __('Bekor qilingan'),
            default => __('Mavjud emas'),
        };
    }
}

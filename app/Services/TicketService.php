<?php

namespace App\Services;

use App\Enums\ExternalStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\SlaProfile;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\TicketReturnRequest;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Notifications\TicketStatusNotification;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TicketService
{
    public function __construct(
        protected AuditService $auditService,
        protected SlaCalculator $slaCalculator,
    ) {
    }

    public function create(array $attributes, ?User $actor = null, array $attachments = []): array
    {
        return DB::transaction(function () use ($attributes, $actor, $attachments): array {
            $category = isset($attributes['category_id'])
                ? Category::query()->find($attributes['category_id'])
                : null;

            $ticket = Ticket::create([
                'reference' => $this->nextReference(),
                'channel' => $attributes['channel'],
                'requester_id' => $attributes['requester_id'] ?? null,
                'operator_id' => $attributes['operator_id'] ?? null,
                'category_id' => $category?->id,
                'requester_name' => $attributes['requester_name'],
                'requester_email' => $attributes['requester_email'] ?? null,
                'requester_phone' => $attributes['requester_phone'] ?? null,
                'requester_department' => $attributes['requester_department'] ?? null,
                'requester_job_title' => $attributes['requester_job_title'] ?? null,
                'title' => $attributes['title'] ?? null,
                'description' => $attributes['description'],
                'priority' => $attributes['priority'] ?? TicketPriority::Unassigned,
                'status' => TicketStatus::New,
                'external_status' => ExternalStatus::Accepted,
            ]);

            $trackingCode = null;

            if (($attributes['channel'] ?? null) === 'guest') {
                $trackingCode = strtoupper(Str::random(10));
                $ticket->forceFill([
                    'tracking_code_hash' => Hash::make($trackingCode),
                    'tracking_code_last_four' => Str::substr($trackingCode, -4),
                ])->save();
            }

            foreach ($attachments as $attachment) {
                $this->storeAttachment($ticket, $attachment, $actor, 'request');
            }

            $this->recordHistory($ticket, $actor, null, TicketStatus::New, null, ExternalStatus::Accepted, __('Murojaat yaratildi', [], 'uz'));
            $this->auditService->log($actor?->id, 'ticket.created', __('Murojaat yaratildi', [], 'uz'), $ticket, [
                'channel' => $ticket->channel,
            ]);
            $this->notifyAdminsAboutNewTicket($ticket);

            return [$ticket->fresh(), $trackingCode];
        });
    }

    public function assign(
        Ticket $ticket,
        User $admin,
        ?int $departmentId,
        ?int $executorId,
        TicketPriority $priority,
        ?int $categoryId,
        ?string $note = null,
    ): Ticket {
        return DB::transaction(function () use ($ticket, $admin, $departmentId, $executorId, $priority, $categoryId, $note): Ticket {
            $ticket->loadMissing('requester');

            $category = $categoryId ? Category::find($categoryId) : null;
            $slaProfile = SlaProfile::query()->where('priority', $priority->value)->first();

            $deadline = $slaProfile
                ? $this->slaCalculator->calculateDeadline(now(), $slaProfile->duration_minutes)
                : null;

            $fromStatus = $ticket->status;
            $fromExternalStatus = $ticket->external_status;

            $toStatus = $executorId ? TicketStatus::Assigned : TicketStatus::New;
            $toExternalStatus = $executorId ? ExternalStatus::InProgress : ExternalStatus::Accepted;

            $ticket->forceFill([
                'assigned_department_id' => $departmentId,
                'assigned_executor_id' => $executorId,
                'category_id' => $category?->id,
                'sla_profile_id' => $slaProfile?->id,
                'priority' => $priority,
                'status' => $toStatus,
                'external_status' => $toExternalStatus,
                'deadline_at' => $deadline,
                'metadata' => array_merge($ticket->metadata ?? [], ['deadline_notifications' => []]),
            ])->save();

            TicketAssignment::create([
                'ticket_id' => $ticket->id,
                'assigned_by' => $admin->id,
                'department_id' => $departmentId,
                'executor_id' => $executorId,
                'priority' => $priority,
                'deadline_at' => $deadline,
                'note' => $note,
            ]);

            $this->resolvePendingReturnRequests($ticket, $admin);

            $this->recordHistory($ticket, $admin, $fromStatus, $toStatus, $fromExternalStatus, $toExternalStatus, $note);
            $this->auditService->log($admin->id, 'ticket.assigned', __('Murojaat taqsimlandi', [], 'uz'), $ticket, [
                'executor_id' => $executorId,
                'department_id' => $departmentId,
                'priority' => $priority->value,
            ]);

            if ($ticket->assignedExecutor) {
                $ticket->assignedExecutor->notify(new TicketStatusNotification(
                    __('Yangi murojaat biriktirildi', [], 'uz'),
                    __(':ref sizga biriktirildi.', ['ref' => $ticket->reference], 'uz'),
                    route('executor.tickets.show', $ticket),
                ));
            }

            return $ticket->fresh();
        });
    }

    public function markInProgress(Ticket $ticket, User $executor, ?string $note = null): Ticket
    {
        return $this->transition($ticket, $executor, TicketStatus::InProgress, ExternalStatus::InProgress, $note, 'ticket.in_progress', __('Ijrochi ishni boshladi', [], 'uz'));
    }

    /**
     * @return array{allowed: bool, message: ?string}
     */
    public function evaluateExecutorClaim(Ticket $ticket, User $executor): array
    {
        if (! $ticket->canExecutorClaimBy($executor)) {
            return [
                'allowed' => false,
                'message' => __("Bu murojaatni hozir qabul qilib bo'lmaydi."),
            ];
        }

        $claimPriority = $this->priorityForDeadline($ticket);

        if (
            $ticket->assigned_executor_id !== $executor->id
            && $claimPriority->workloadUnits() > $executor->remainingExecutorWorkloadUnits($ticket->id)
        ) {
            return [
                'allowed' => false,
                'message' => __("Joriy yuklama limiti oshib ketadi. Bir vaqtning o'zida ko'pi bilan 1 ta shoshilinch va 1 ta past, yoki 2 ta yuqori, yoki 3 ta o'rta, yoki 5 ta past topshiriqni olish mumkin."),
            ];
        }

        return [
            'allowed' => true,
            'message' => null,
        ];
    }

    public function claimForExecutor(Ticket $ticket, User $executor, ?string $note = null): Ticket
    {
        $evaluation = $this->evaluateExecutorClaim($ticket->fresh(), $executor);

        if (! $evaluation['allowed']) {
            throw ValidationException::withMessages([
                'claim' => $evaluation['message'],
            ]);
        }

        return DB::transaction(function () use ($ticket, $executor, $note): Ticket {
            $ticket->refresh()->loadMissing('category');
            $priority = $this->priorityForDeadline($ticket);
            $slaProfile = SlaProfile::query()->where('priority', $priority->value)->first();

            if (! $slaProfile) {
                throw ValidationException::withMessages([
                    'claim' => __(':priority muhimlik darajasi uchun deadline sozlamasi topilmadi.', ['priority' => $priority->label()]),
                ]);
            }

            $deadline = (! $ticket->deadline_at || $ticket->status === TicketStatus::Overdue)
                ? $this->slaCalculator->calculateDeadline(now(), $slaProfile->duration_minutes)
                : $ticket->deadline_at;

            $ticket->forceFill([
                'assigned_executor_id' => $executor->id,
                'assigned_department_id' => $ticket->assigned_department_id ?: $ticket->category?->department_id,
                'priority' => $priority,
                'sla_profile_id' => $slaProfile->id,
                'deadline_at' => $deadline,
                'metadata' => array_merge($ticket->metadata ?? [], ['deadline_notifications' => []]),
            ])->save();

            if (! $ticket->assignments()->where('executor_id', $executor->id)->exists()) {
                TicketAssignment::create([
                    'ticket_id' => $ticket->id,
                    'assigned_by' => $executor->id,
                    'department_id' => $ticket->assigned_department_id,
                    'executor_id' => $executor->id,
                    'priority' => $priority,
                    'deadline_at' => $deadline,
                    'note' => $note,
                ]);
            }

            return $this->markInProgress($ticket->fresh(), $executor, $note);
        });
    }

    public function complete(Ticket $ticket, User $executor, array $proofs, ?string $note = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $executor, $proofs, $note): Ticket {
            foreach ($proofs as $proof) {
                $this->storeAttachment($ticket, $proof, $executor, 'proof');
            }

            $updated = $this->transition(
                $ticket,
                $executor,
                TicketStatus::Completed,
                ExternalStatus::InProgress,
                $note,
                'ticket.completed',
                __('Ijrochi murojaatni bajardi', [], 'uz'),
            );

            $updated->forceFill([
                'completed_at' => now(),
            ])->save();

            $this->notifyAdmins(
                __('Murojaat bajarildi', [], 'uz'),
                __(':ref ijrochi tomonidan bajarildi.', ['ref' => $updated->reference], 'uz'),
                route('admin.dispatch.show', $updated),
                [
                    'kind' => 'ticket_completed',
                    'ticket_id' => $updated->id,
                    'ticket_reference' => $updated->reference,
                    'executor_id' => $executor->id,
                ],
            );

            return $updated->fresh();
        });
    }

    public function close(Ticket $ticket, User $admin, ?string $note = null): Ticket
    {
        $updated = $this->transition($ticket, $admin, TicketStatus::Closed, ExternalStatus::Closed, $note, 'ticket.closed', __('Murojaat yopildi', [], 'uz'));
        $updated->forceFill(['closed_at' => now()])->save();
        $this->resolvePendingReturnRequests($updated, $admin);

        return $updated->fresh();
    }

    public function reject(Ticket $ticket, User $admin, string $reason): Ticket
    {
        $updated = $this->transition($ticket, $admin, TicketStatus::Rejected, ExternalStatus::Rejected, $reason, 'ticket.rejected', __('Murojaat rad etildi', [], 'uz'));
        $updated->forceFill([
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ])->save();
        $this->resolvePendingReturnRequests($updated, $admin);

        return $updated->fresh();
    }

    /**
     * Ijrochi murojaatni qaytaradi: admin tasdig'isiz murojaat ijrochidan bo'shatiladi va qabul qilinmagan
     * holatiga (umumiy navbat, "Yangi") qaytadi. Bo'lim, kategoriya, muhimlik va muddat saqlanadi.
     */
    public function requestReturn(Ticket $ticket, User $executor, string $reason): Ticket
    {
        return DB::transaction(function () use ($ticket, $executor, $reason): Ticket {
            $this->resolvePendingReturnRequests($ticket, $executor);

            TicketReturnRequest::create([
                'ticket_id' => $ticket->id,
                'executor_id' => $executor->id,
                'reason' => $reason,
                'resolved_at' => now(),
                'resolved_by' => $executor->id,
            ]);

            $ticket->forceFill([
                'assigned_executor_id' => null,
                'metadata' => array_merge($ticket->metadata ?? [], ['deadline_notifications' => []]),
            ])->save();

            $updated = $this->transition($ticket, $executor, TicketStatus::New, ExternalStatus::Accepted, $reason, 'ticket.returned', __('Ijrochi murojaatni umumiy navbatga qaytardi', [], 'uz'));

            $this->notifyAdmins(
                __('Murojaat navbatga qaytdi', [], 'uz'),
                __(':ref: :name murojaatni qaytardi. Sabab: :reason', ['ref' => $updated->reference, 'name' => $executor->name, 'reason' => $reason], 'uz'),
                route('admin.dispatch.show', $updated),
                [
                    'kind' => 'ticket_returned',
                    'ticket_id' => $updated->id,
                    'ticket_reference' => $updated->reference,
                    'executor_id' => $executor->id,
                    'reason' => $reason,
                ],
            );

            return $updated;
        });
    }

    /**
     * Murojaatchi o'z murojaatini bekor qiladi (aktual bo'lmay qolgan). Bazadan o'chirilmaydi —
     * "Bekor qilindi" holatida arxivda qoladi, barcha ish ro'yxatlaridan chiqadi, KPI'ga bajarilgan bo'lib kirmaydi.
     */
    public function cancelByRequester(Ticket $ticket, User $requester, ?string $reason = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $requester, $reason): Ticket {
            $ticket->refresh();

            if (! $ticket->canBeCancelledBy($requester)) {
                throw new \DomainException(__("Bu murojaatni bekor qilib bo'lmaydi."));
            }

            $fromStatus = $ticket->status;
            $fromExternal = $ticket->external_status;
            $note = $reason
                ? __('Murojaatchi bekor qildi: :reason', ['reason' => $reason], 'uz')
                : __('Murojaatchi bekor qildi.', [], 'uz');

            $ticket->forceFill([
                'status' => TicketStatus::Cancelled,
                'external_status' => ExternalStatus::Cancelled,
                'closed_at' => now(),
                'metadata' => [...($ticket->metadata ?? []), 'cancelled_by_requester' => true, 'cancel_reason' => $reason],
            ])->save();

            $this->recordHistory($ticket, $requester, $fromStatus, TicketStatus::Cancelled, $fromExternal, ExternalStatus::Cancelled, $note);
            $this->auditService->log($requester->id, 'ticket.cancelled', __('Murojaatchi murojaatni bekor qildi', [], 'uz'), $ticket, [
                'reason' => $reason,
            ]);
            $this->resolvePendingReturnRequests($ticket, $requester);

            $body = "{$ticket->reference}: {$note}";
            $meta = ['kind' => 'ticket_cancelled', 'ticket_id' => $ticket->id, 'ticket_reference' => $ticket->reference];

            $ticket->assignedExecutor?->notify(new TicketStatusNotification(
                __('Murojaat bekor qilindi', [], 'uz'),
                $body,
                route('executor.tickets.show', $ticket),
                $meta,
            ));

            $this->notifyAdmins(__('Murojaat bekor qilindi', [], 'uz'), $body, route('admin.dispatch.show', $ticket), $meta);

            return $ticket->fresh();
        });
    }

    public function addComment(Ticket $ticket, ?User $user, string $body, bool $isPublic): TicketComment
    {
        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user?->id,
            'body' => $body,
            'is_public' => $isPublic,
        ]);

        $this->auditService->log($user?->id, 'ticket.commented', __('Izoh qoldirildi', [], 'uz'), $ticket, [
            'public' => $isPublic,
        ]);

        if ($isPublic) {
            $this->notifyAboutComment($ticket, $user, $body);
        }

        return $comment;
    }

    /**
     * Ommaviy izoh: xodim yozsa — murojaatchiga (yoki operatorga), murojaatchi yozsa — biriktirilgan ijrochiga.
     */
    protected function notifyAboutComment(Ticket $ticket, ?User $author, string $body): void
    {
        $ticket->loadMissing(['requester', 'operator', 'assignedExecutor']);
        $preview = \Illuminate\Support\Str::limit($body, 300);
        $authorName = $author?->name ?? 'Xodim';

        if ($author && $author->id === $ticket->requester_id) {
            $ticket->assignedExecutor?->notify(new TicketStatusNotification(
                __('Murojaatga yangi izoh', [], 'uz'),
                "{$ticket->reference}: {$authorName}: {$preview}",
                route('executor.tickets.show', $ticket),
                ['kind' => 'ticket_comment', 'ticket_id' => $ticket->id, 'ticket_reference' => $ticket->reference],
            ));

            return;
        }

        $recipients = collect([
            $ticket->requester ? [$ticket->requester, route('tickets.show', $ticket)] : null,
            $ticket->operator && $ticket->operator_id !== $ticket->requester_id
                ? [$ticket->operator, route('operator.tickets.show', $ticket)]
                : null,
        ])->filter();

        foreach ($recipients as [$recipient, $url]) {
            if ($author && $recipient->id === $author->id) {
                continue;
            }

            $recipient->notify(new TicketStatusNotification(
                __('Murojaatingizga javob keldi', [], 'uz'),
                "{$ticket->reference}: {$authorName}: {$preview}",
                $url,
                ['kind' => 'ticket_comment', 'ticket_id' => $ticket->id, 'ticket_reference' => $ticket->reference],
            ));
        }
    }

    public function verifyGuestCode(Ticket $ticket, string $code): bool
    {
        if (! $ticket->tracking_code_hash) {
            return false;
        }

        return Hash::check($code, $ticket->tracking_code_hash);
    }

    protected function transition(
        Ticket $ticket,
        User $actor,
        TicketStatus $toStatus,
        ExternalStatus $toExternalStatus,
        ?string $note,
        string $event,
        string $description,
    ): Ticket {
        return DB::transaction(function () use ($ticket, $actor, $toStatus, $toExternalStatus, $note, $event, $description): Ticket {
            $fromStatus = $ticket->status;
            $fromExternal = $ticket->external_status;

            $ticket->forceFill([
                'status' => $toStatus,
                'external_status' => $toExternalStatus,
            ])->save();

            $this->recordHistory($ticket, $actor, $fromStatus, $toStatus, $fromExternal, $toExternalStatus, $note);
            $this->auditService->log($actor->id, $event, $description, $ticket, [
                'note' => $note,
            ]);

            if ($ticket->requester) {
                $ticket->requester->notify(new TicketStatusNotification(
                    __('Murojaat holati yangilandi', [], 'uz'),
                    __(':ref holati: :status', ['ref' => $ticket->reference, 'status' => $ticket->external_status->label('uz')], 'uz'),
                    route('tickets.show', $ticket),
                ));
            }

            return $ticket->fresh();
        });
    }

    protected function recordHistory(
        Ticket $ticket,
        ?User $user,
        ?TicketStatus $fromStatus,
        TicketStatus $toStatus,
        ?ExternalStatus $fromExternalStatus,
        ?ExternalStatus $toExternalStatus,
        ?string $note,
    ): void {
        TicketStatusHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user?->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'from_external_status' => $fromExternalStatus,
            'to_external_status' => $toExternalStatus,
            'note' => $note,
        ]);
    }

    protected function priorityForDeadline(Ticket $ticket): TicketPriority
    {
        if ($ticket->priority !== TicketPriority::Unassigned) {
            return $ticket->priority;
        }

        $categoryPriority = $ticket->category?->default_priority;

        if ($categoryPriority instanceof TicketPriority && $categoryPriority !== TicketPriority::Unassigned) {
            return $categoryPriority;
        }

        return TicketPriority::Medium;
    }

    protected function resolvePendingReturnRequests(Ticket $ticket, User $resolver): void
    {
        TicketReturnRequest::query()
            ->where('ticket_id', $ticket->getKey())
            ->pending()
            ->update([
                'resolved_at' => now(),
                'resolved_by' => $resolver->id,
                'updated_at' => now(),
            ]);
    }

    protected function notifyAdminsAboutNewTicket(Ticket $ticket): void
    {
        $this->notifyAdmins(
            __('Yangi murojaat keldi', [], 'uz'),
            __(":ref bo'yicha yangi murojaat qabul qilindi.", ['ref' => $ticket->reference], 'uz'),
            route('admin.dispatch.show', $ticket),
            [
                'kind' => 'ticket_created',
                'ticket_id' => $ticket->id,
                'ticket_reference' => $ticket->reference,
                'channel' => $ticket->channel,
            ],
        );
    }

    protected function notifyAdmins(string $title, string $body, string $url, array $meta = []): void
    {
        User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', UserRole::Admin->value))
            ->where('is_active', true)
            ->whereNotNull('approved_at')
            ->get()
            ->each(fn (User $admin) => $admin->notify(new TicketStatusNotification($title, $body, $url, $meta)));
    }

    protected function storeAttachment(Ticket $ticket, UploadedFile $file, ?User $actor, string $context): void
    {
        $path = $file->store("tickets/{$ticket->id}", 'local');

        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor?->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'context' => $context,
        ]);
    }

    protected function nextReference(): string
    {
        $datePrefix = now()->format('Ymd');
        $count = Ticket::query()->whereDate('created_at', today())->count() + 1;

        return sprintf('RTT-%s-%04d', $datePrefix, $count);
    }
}

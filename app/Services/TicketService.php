<?php

namespace App\Services;

use App\Enums\ExternalStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
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
            $ticket = Ticket::create([
                'reference' => $this->nextReference(),
                'channel' => $attributes['channel'],
                'requester_id' => $attributes['requester_id'] ?? null,
                'operator_id' => $attributes['operator_id'] ?? null,
                'requester_name' => $attributes['requester_name'],
                'requester_email' => $attributes['requester_email'] ?? null,
                'requester_phone' => $attributes['requester_phone'] ?? null,
                'requester_department' => $attributes['requester_department'] ?? null,
                'requester_job_title' => $attributes['requester_job_title'] ?? null,
                'title' => $attributes['title'] ?? null,
                'description' => $attributes['description'],
                'priority' => TicketPriority::Medium,
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

            $this->recordHistory($ticket, $actor, null, TicketStatus::New, null, ExternalStatus::Accepted, 'Murojaat yaratildi');
            $this->auditService->log($actor?->id, 'ticket.created', 'Murojaat yaratildi', $ticket, [
                'channel' => $ticket->channel,
            ]);

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

            $ticket->forceFill([
                'assigned_department_id' => $departmentId,
                'assigned_executor_id' => $executorId,
                'category_id' => $category?->id,
                'sla_profile_id' => $slaProfile?->id,
                'priority' => $priority,
                'status' => TicketStatus::Assigned,
                'external_status' => ExternalStatus::InProgress,
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

            $this->recordHistory($ticket, $admin, $fromStatus, TicketStatus::Assigned, $fromExternalStatus, ExternalStatus::InProgress, $note);
            $this->auditService->log($admin->id, 'ticket.assigned', 'Murojaat taqsimlandi', $ticket, [
                'executor_id' => $executorId,
                'department_id' => $departmentId,
                'priority' => $priority->value,
            ]);

            if ($ticket->assignedExecutor) {
                $ticket->assignedExecutor->notify(new TicketStatusNotification(
                    'Yangi murojaat biriktirildi',
                    "{$ticket->reference} sizga biriktirildi.",
                    route('executor.tickets.show', $ticket),
                ));
            }

            return $ticket->fresh();
        });
    }

    public function markInProgress(Ticket $ticket, User $executor, ?string $note = null): Ticket
    {
        return $this->transition($ticket, $executor, TicketStatus::InProgress, ExternalStatus::InProgress, $note, 'ticket.in_progress', 'Ijrochi ishni boshladi');
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
                'Ijrochi murojaatni bajardi',
            );

            $updated->forceFill([
                'completed_at' => now(),
            ])->save();

            return $updated->fresh();
        });
    }

    public function close(Ticket $ticket, User $admin, ?string $note = null): Ticket
    {
        $updated = $this->transition($ticket, $admin, TicketStatus::Closed, ExternalStatus::Closed, $note, 'ticket.closed', 'Murojaat yopildi');
        $updated->forceFill(['closed_at' => now()])->save();
        $this->resolvePendingReturnRequests($updated, $admin);

        return $updated->fresh();
    }

    public function reject(Ticket $ticket, User $admin, string $reason): Ticket
    {
        $updated = $this->transition($ticket, $admin, TicketStatus::Rejected, ExternalStatus::Rejected, $reason, 'ticket.rejected', 'Murojaat rad etildi');
        $updated->forceFill([
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ])->save();
        $this->resolvePendingReturnRequests($updated, $admin);

        return $updated->fresh();
    }

    public function requestReturn(Ticket $ticket, User $executor, string $reason): Ticket
    {
        return DB::transaction(function () use ($ticket, $executor, $reason): Ticket {
            TicketReturnRequest::create([
                'ticket_id' => $ticket->id,
                'executor_id' => $executor->id,
                'reason' => $reason,
            ]);

            $updated = $this->transition($ticket, $executor, TicketStatus::Returned, ExternalStatus::InProgress, $reason, 'ticket.returned', 'Ijrochi murojaatni qaytardi');

            $admins = User::role('admin')->get();

            foreach ($admins as $admin) {
                $admin->notify(new TicketStatusNotification(
                    'Qaytarish so‘rovi',
                    "{$ticket->reference} bo‘yicha qaytarish so‘rovi keldi.",
                    route('admin.dispatch.show', $ticket),
                ));
            }

            return $updated;
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

        $this->auditService->log($user?->id, 'ticket.commented', 'Izoh qoldirildi', $ticket, [
            'public' => $isPublic,
        ]);

        return $comment;
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
                    'Murojaat holati yangilandi',
                    "{$ticket->reference} holati: {$ticket->external_status->label()}",
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

    protected function resolvePendingReturnRequests(Ticket $ticket, User $resolver): void
    {
        $ticket->returnRequests()
            ->pending()
            ->update([
                'resolved_at' => now(),
                'resolved_by' => $resolver->id,
                'updated_at' => now(),
            ]);
    }

    protected function storeAttachment(Ticket $ticket, UploadedFile $file, ?User $actor, string $context): void
    {
        $path = $file->store("tickets/{$ticket->id}", 'public');

        TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor?->id,
            'disk' => 'public',
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

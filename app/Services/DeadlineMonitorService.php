<?php

namespace App\Services;

use App\Enums\ExternalStatus;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Notifications\TicketStatusNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeadlineMonitorService
{
    public function markOverdueTickets(bool $notifyAdmins = true): int
    {
        $tickets = Ticket::query()
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<=', now())
            ->whereNotIn('status', $this->finishedStatuses())
            ->get();

        if ($tickets->isEmpty()) {
            return 0;
        }

        $admins = $notifyAdmins ? $this->admins() : collect();
        $markedCount = 0;

        foreach ($tickets as $ticket) {
            $marker = $ticket->deadline_at->toIso8601String();

            if (! $this->markTicketAsOverdue($ticket, $marker)) {
                continue;
            }

            $markedCount++;

            if ($notifyAdmins) {
                $this->notifyOverdueIfNeeded($ticket->fresh(), $admins, $marker);
            }
        }

        return $markedCount;
    }

    public function markTicketAsOverdue(Ticket $ticket, string $marker): bool
    {
        return DB::transaction(function () use ($ticket, $marker): bool {
            $ticket->refresh();

            if (in_array($ticket->status, [
                TicketStatus::Completed,
                TicketStatus::Closed,
                TicketStatus::Rejected,
                TicketStatus::Overdue,
            ], true)) {
                return false;
            }

            $metadata = $ticket->metadata ?? [];
            $metadata['overdue'] = [
                'deadline_at' => $marker,
                'previous_executor_id' => $ticket->assigned_executor_id,
                'marked_at' => now()->toIso8601String(),
            ];

            $fromStatus = $ticket->status;
            $fromExternalStatus = $ticket->external_status;

            $ticket->forceFill([
                'assigned_executor_id' => null,
                'status' => TicketStatus::Overdue,
                'external_status' => ExternalStatus::Overdue,
                'metadata' => $metadata,
            ])->save();

            $ticket->returnRequests()
                ->pending()
                ->update([
                    'resolved_at' => now(),
                    'resolved_by' => null,
                    'updated_at' => now(),
                ]);

            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => null,
                'from_status' => $fromStatus,
                'to_status' => TicketStatus::Overdue,
                'from_external_status' => $fromExternalStatus,
                'to_external_status' => ExternalStatus::Overdue,
                'note' => 'Deadline tugagani uchun ijrochidan yechildi.',
            ]);

            return true;
        });
    }

    /**
     * @param  Collection<int, User>  $admins
     */
    public function notifyOverdueIfNeeded(Ticket $ticket, Collection $admins, string $marker): void
    {
        $sent = $ticket->metadata['deadline_notifications'] ?? [];

        if (($sent['overdue_for'] ?? null) === $marker || $admins->isEmpty()) {
            return;
        }

        foreach ($admins as $admin) {
            $admin->notify(new TicketStatusNotification(
                'Kechikkan murojaat',
                "{$ticket->reference} deadline vaqtidan o'tib ketdi va ijrochidan yechildi.",
                route('admin.dispatch.show', $ticket),
                ['kind' => 'deadline_overdue', 'ticket_id' => $ticket->id],
            ));
        }

        $this->markAsSent($ticket, 'overdue_for', $marker);
    }

    public function markAsSent(Ticket $ticket, string $key, string $marker): void
    {
        $metadata = $ticket->metadata ?? [];
        $notifications = $metadata['deadline_notifications'] ?? [];
        $notifications[$key] = $marker;
        $metadata['deadline_notifications'] = $notifications;

        Ticket::withoutTimestamps(function () use ($ticket, $metadata): void {
            $ticket->forceFill([
                'metadata' => $metadata,
            ])->save();
        });
    }

    /**
     * @return Collection<int, User>
     */
    public function admins(): Collection
    {
        return User::role('admin')->get();
    }

    /**
     * @return array<int, string>
     */
    private function finishedStatuses(): array
    {
        return [
            TicketStatus::Completed->value,
            TicketStatus::Closed->value,
            TicketStatus::Rejected->value,
            TicketStatus::Overdue->value,
        ];
    }
}

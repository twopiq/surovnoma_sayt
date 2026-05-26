<?php

namespace App\Console\Commands;

use App\Enums\ExternalStatus;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Notifications\TicketStatusNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendDeadlineAlertsCommand extends Command
{
    protected $signature = 'tickets:send-deadline-alerts';

    protected $description = 'Deadline yaqinlashgan yoki kechikkan murojaatlar haqida adminlarni ogohlantiradi';

    public function handle(): int
    {
        $tickets = Ticket::query()
            ->with('slaProfile')
            ->whereNotNull('deadline_at')
            ->whereNotIn('status', [
                TicketStatus::Completed->value,
                TicketStatus::Closed->value,
                TicketStatus::Rejected->value,
                TicketStatus::Overdue->value,
            ])
            ->get();

        $admins = User::role('admin')->get();

        foreach ($tickets as $ticket) {
            $minutesLeft = now()->diffInMinutes($ticket->deadline_at, false);
            $marker = $ticket->deadline_at->toIso8601String();
            $sent = $ticket->metadata['deadline_notifications'] ?? [];

            if ($minutesLeft <= 0) {
                $this->markTicketAsOverdue($ticket, $marker);

                if (($sent['overdue_for'] ?? null) !== $marker) {
                    foreach ($admins as $admin) {
                        $admin->notify(new TicketStatusNotification(
                            'Kechikkan murojaat',
                            "{$ticket->reference} deadline vaqtidan o'tib ketdi va ijrochidan yechildi.",
                            route('admin.dispatch.show', $ticket),
                            ['kind' => 'deadline_overdue', 'ticket_id' => $ticket->id],
                        ));
                    }

                    $this->markAsSent($ticket->fresh(), 'overdue_for', $marker);
                }

                $this->line("Kechikkan holatga o'tkazildi: {$ticket->reference}");
                continue;
            }

            if (
                $ticket->slaProfile
                && $minutesLeft > 0
                && $minutesLeft <= $ticket->slaProfile->warning_minutes
                && ($sent['warning_for'] ?? null) !== $marker
                && $admins->isNotEmpty()
            ) {
                foreach ($admins as $admin) {
                    $admin->notify(new TicketStatusNotification(
                        'Deadline yaqinlashmoqda',
                        "{$ticket->reference} uchun {$minutesLeft} daqiqa qoldi.",
                        route('admin.dispatch.show', $ticket),
                        ['kind' => 'deadline_warning', 'ticket_id' => $ticket->id],
                    ));
                }

                $this->markAsSent($ticket, 'warning_for', $marker);
                $this->line("Warning ogohlantirish yuborildi: {$ticket->reference}");
            }
        }

        return self::SUCCESS;
    }

    protected function markTicketAsOverdue(Ticket $ticket, string $marker): void
    {
        DB::transaction(function () use ($ticket, $marker): void {
            $ticket->refresh();

            if (in_array($ticket->status, [
                TicketStatus::Completed,
                TicketStatus::Closed,
                TicketStatus::Rejected,
                TicketStatus::Overdue,
            ], true)) {
                return;
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
        });
    }

    protected function markAsSent(Ticket $ticket, string $key, string $marker): void
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
}

<?php

namespace App\Console\Commands;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Notifications\TicketStatusNotification;
use App\Services\DeadlineMonitorService;
use Illuminate\Console\Command;

class SendDeadlineAlertsCommand extends Command
{
    protected $signature = 'tickets:send-deadline-alerts';

    protected $description = 'Deadline yaqinlashgan yoki kechikkan murojaatlar haqida adminlarni ogohlantiradi';

    public function handle(DeadlineMonitorService $deadlineMonitor): int
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

        $admins = $deadlineMonitor->admins();

        foreach ($tickets as $ticket) {
            $minutesLeft = now()->diffInMinutes($ticket->deadline_at, false);
            $marker = $ticket->deadline_at->toIso8601String();
            $sent = $ticket->metadata['deadline_notifications'] ?? [];

            if ($minutesLeft <= 0) {
                $deadlineMonitor->markTicketAsOverdue($ticket, $marker);
                $deadlineMonitor->notifyOverdueIfNeeded($ticket->fresh(), $admins, $marker);

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

                $deadlineMonitor->markAsSent($ticket, 'warning_for', $marker);
                $this->line("Warning ogohlantirish yuborildi: {$ticket->reference}");
            }
        }

        return self::SUCCESS;
    }
}

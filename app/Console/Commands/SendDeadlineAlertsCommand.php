<?php

namespace App\Console\Commands;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketStatusNotification;
use Illuminate\Console\Command;

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
            ])
            ->get();

        $admins = User::role('admin')->get();

        foreach ($tickets as $ticket) {
            if (! $ticket->slaProfile || $admins->isEmpty()) {
                continue;
            }

            $minutesLeft = now()->diffInMinutes($ticket->deadline_at, false);
            $marker = $ticket->deadline_at->toIso8601String();
            $sent = $ticket->metadata['deadline_notifications'] ?? [];

            if ($minutesLeft <= 0 && ($sent['overdue_for'] ?? null) !== $marker) {
                foreach ($admins as $admin) {
                    $admin->notify(new TicketStatusNotification(
                        'Kechikkan murojaat',
                        "{$ticket->reference} deadline vaqtidan o'tib ketdi.",
                        route('admin.dispatch.show', $ticket),
                        ['kind' => 'deadline_overdue', 'ticket_id' => $ticket->id],
                    ));
                }

                $this->markAsSent($ticket, 'overdue_for', $marker);
                $this->line("Overdue ogohlantirish yuborildi: {$ticket->reference}");
                continue;
            }

            if (
                $minutesLeft > 0
                && $minutesLeft <= $ticket->slaProfile->warning_minutes
                && ($sent['warning_for'] ?? null) !== $marker
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

<?php

namespace App\Livewire\Admin;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Livewire\Component;

class DispatchBoard extends Component
{
    public function render()
    {
        $statuses = [
            TicketStatus::New,
            TicketStatus::Assigned,
            TicketStatus::InProgress,
            TicketStatus::Returned,
            TicketStatus::Rejected,
        ];

        $grouped = collect($statuses)
            ->mapWithKeys(fn (TicketStatus $status) => [
                $status->value => Ticket::query()
                    ->where('status', $status->value)
                    ->latest()
                    ->take(6)
                    ->get(),
            ]);

        return view('livewire.admin.dispatch-board', [
            'statuses' => $statuses,
            'grouped' => $grouped,
        ]);
    }
}

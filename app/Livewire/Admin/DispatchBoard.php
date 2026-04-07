<?php

namespace App\Livewire\Admin;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Livewire\Component;

class DispatchBoard extends Component
{
    public function render()
    {
        $grouped = collect(TicketStatus::cases())
            ->mapWithKeys(fn (TicketStatus $status) => [
                $status->value => Ticket::query()
                    ->where('status', $status->value)
                    ->latest()
                    ->take(6)
                    ->get(),
            ]);

        return view('livewire.admin.dispatch-board', [
            'statuses' => TicketStatus::cases(),
            'grouped' => $grouped,
        ]);
    }
}

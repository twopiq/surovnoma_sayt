<?php

namespace App\Services;

use App\Enums\AvailabilityStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Admin bosh sahifasi (dizayn tizimi: AdminScreen) — ko'rsatkichlar, murojaatlar jadvali va tayinlash paneli.
 */
class AdminHomeService
{
    public const TABS = [
        'all' => 'Barchasi',
        'unassigned' => 'Belgilanmagan',
        'overdue' => 'Kechikkan',
        'archive' => 'Arxiv',
    ];

    public function data(Request $request): array
    {
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? (string) $request->query('tab') : 'all';
        $search = trim((string) $request->query('q'));

        $tickets = $this->tabQuery($tab)
            ->with(['assignedExecutor:id,name', 'category:id,name'])
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $inner) use ($search): void {
                $inner->where('reference', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%");
            }))
            ->orderByRaw('deadline_at is null')
            ->orderBy('deadline_at')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $selected = $request->filled('ticket')
            ? Ticket::query()->with(['assignedExecutor:id,name', 'category:id,name'])->find($request->integer('ticket'))
            : $tickets->getCollection()->first(fn (Ticket $ticket) => $ticket->assigned_executor_id === null) ?? $tickets->first();

        return [
            'tab' => $tab,
            'tabs' => self::TABS,
            'search' => $search,
            'tickets' => $tickets,
            'selected' => $selected,
            'stats' => $this->stats(),
            'executors' => $selected && $this->isActive($selected) ? $this->executors($selected) : collect(),
            'priorities' => TicketPriority::assignableCases(),
            'maxUnits' => User::EXECUTOR_MAX_WORKLOAD_UNITS,
        ];
    }

    private function stats(): array
    {
        $active = Ticket::query()->whereIn('status', $this->activeStatuses());

        return [
            'open' => (clone $active)->count(),
            'unassigned' => (clone $active)->whereNull('assigned_executor_id')->count(),
            'at_risk' => (clone $active)->whereNotNull('deadline_at')->whereBetween('deadline_at', [now(), now()->addHours(2)])->count(),
            'overdue' => (clone $active)->whereNotNull('deadline_at')->where('deadline_at', '<', now())->count(),
        ];
    }

    private function tabQuery(string $tab): Builder
    {
        return match ($tab) {
            'archive' => Ticket::query()->whereIn('status', [TicketStatus::Completed->value, TicketStatus::Closed->value, TicketStatus::Rejected->value]),
            'unassigned' => Ticket::query()->whereIn('status', $this->activeStatuses())->whereNull('assigned_executor_id'),
            'overdue' => Ticket::query()->whereIn('status', $this->activeStatuses())->whereNotNull('deadline_at')->where('deadline_at', '<', now()),
            default => Ticket::query()->whereIn('status', $this->activeStatuses()),
        };
    }

    private function executors(Ticket $ticket)
    {
        return User::query()
            ->role('executor')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'availability_status'])
            ->map(function (User $executor) use ($ticket): array {
                $used = $executor->currentExecutorWorkloadUnits($ticket->id);

                return [
                    'id' => $executor->id,
                    'name' => $executor->name,
                    'used' => $used,
                    'percent' => (int) min(100, round($used / max(1, User::EXECUTOR_MAX_WORKLOAD_UNITS) * 100)),
                    'vacation' => $executor->availability_status === AvailabilityStatus::Vacation,
                    'current' => $ticket->assigned_executor_id === $executor->id,
                ];
            })
            ->sortBy(fn (array $row) => [$row['vacation'], $row['used']])
            ->values();
    }

    private function isActive(Ticket $ticket): bool
    {
        return in_array($ticket->status->value, $this->activeStatuses(), true);
    }

    private function activeStatuses(): array
    {
        return [
            TicketStatus::New->value,
            TicketStatus::Assigned->value,
            TicketStatus::InProgress->value,
            TicketStatus::Returned->value,
            TicketStatus::Overdue->value,
        ];
    }
}

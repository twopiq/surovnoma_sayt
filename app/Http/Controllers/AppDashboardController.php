<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AppDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $viewer = $request->user();
        abort_unless($viewer && $viewer->canAccessAppDashboard(), 403);

        $availableAgents = $this->availableAgents($viewer);
        $agentId = $this->validAgentId($request->query('agent'), $availableAgents, $viewer);
        $years = $this->availableYears($viewer, $agentId);
        $year = $this->validYear($request->query('year'), $years);
        $trend = $this->validTrend($request->query('trend'));

        $summaryQuery = $this->ticketsQuery($viewer);
        $resolvedQuery = (clone $summaryQuery)
            ->whereNotNull('completed_at')
            ->whereIn('status', [TicketStatus::Completed->value, TicketStatus::Closed->value]);

        $totalTickets = (clone $summaryQuery)->count();
        $resolvedTickets = (clone $resolvedQuery)->count();
        $urgentResolved = (clone $resolvedQuery)
            ->whereIn('priority', [TicketPriority::Urgent->value, TicketPriority::High->value])
            ->count();
        $sameDayResolved = (clone $resolvedQuery)
            ->get(['id', 'created_at', 'completed_at'])
            ->filter(fn (Ticket $ticket) => $ticket->created_at && $ticket->completed_at && $ticket->created_at->isSameDay($ticket->completed_at))
            ->count();

        $resolutionSeconds = (clone $resolvedQuery)
            ->get(['id', 'created_at', 'completed_at'])
            ->map(fn (Ticket $ticket) => $ticket->created_at && $ticket->completed_at ? $ticket->completed_at->diffInSeconds($ticket->created_at) : null)
            ->filter(fn ($value) => is_int($value) && $value >= 0);

        $avgResolutionHours = $resolutionSeconds->isNotEmpty()
            ? round(((float) $resolutionSeconds->avg()) / 3600, 1)
            : null;

        $withinSlaCount = (clone $resolvedQuery)
            ->where(function (Builder $query): void {
                $query->whereNull('deadline_at')
                    ->orWhereColumn('completed_at', '<=', 'deadline_at');
            })
            ->count();

        $outsideSlaCount = max(0, $resolvedTickets - $withinSlaCount);
        $withinSlaPercent = $resolvedTickets > 0 ? round(($withinSlaCount / $resolvedTickets) * 100, 1) : 0.0;

        return view('app.dashboard', [
            'filterOptions' => [
                'agents' => $availableAgents,
                'years' => $years,
                'agent' => $agentId,
                'year' => $year,
                'trend' => $trend,
            ],
            'dashboardStats' => [
                'total_tickets' => $totalTickets,
                'avg_resolution_hours' => $avgResolutionHours,
                'customer_satisfaction' => null,
                'urgent_resolved' => $urgentResolved,
                'same_day_resolved' => $sameDayResolved,
                'resolved_total' => $resolvedTickets,
                'within_sla_count' => $withinSlaCount,
                'outside_sla_count' => $outsideSlaCount,
                'within_sla_percent' => $withinSlaPercent,
            ],
            'trendSeries' => $this->trendSeries($viewer, $agentId, $year, $trend),
            'categoryBreakdown' => $this->categoryBreakdown($summaryQuery),
            'priorityMatrix' => $this->priorityMatrix($summaryQuery),
        ]);
    }

    protected function ticketsQuery(User $viewer): Builder
    {
        return Ticket::query()
            ->visibleTo($viewer);
    }

    protected function filteredTicketsQuery(User $viewer, ?int $agentId, ?int $year): Builder
    {
        return $this->ticketsQuery($viewer)
            ->with(['category', 'assignedExecutor'])
            ->when($agentId, fn (Builder $query) => $query->where('assigned_executor_id', $agentId))
            ->when($year, fn (Builder $query) => $query->whereYear('created_at', $year));
    }

    protected function availableAgents(User $viewer): Collection
    {
        if ($viewer->hasAnyRole(['admin', 'manager'])) {
            return User::query()
                ->role('executor')
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get();
        }

        if ($viewer->hasRole('executor')) {
            return collect([$viewer->only(['id', 'name'])]);
        }

        return collect();
    }

    protected function validAgentId(mixed $agentId, Collection $availableAgents, User $viewer): ?int
    {
        if ($agentId === null || $agentId === '' || $agentId === 'all') {
            return $viewer->hasRole('executor') ? $viewer->id : null;
        }

        $agentId = (int) $agentId;

        return $availableAgents->contains(fn ($agent) => (int) data_get($agent, 'id') === $agentId)
            ? $agentId
            : ($viewer->hasRole('executor') ? $viewer->id : null);
    }

    protected function availableYears(User $viewer, ?int $agentId): Collection
    {
        return $this->filteredTicketsQuery($viewer, $agentId, null)
            ->orderByDesc('created_at')
            ->get(['created_at'])
            ->map(fn (Ticket $ticket): ?int => $ticket->created_at?->year)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();
    }

    protected function validYear(mixed $year, Collection $availableYears): ?int
    {
        if ($year === null || $year === '' || $year === 'all') {
            return null;
        }

        $year = (int) $year;

        return $availableYears->contains($year) ? $year : null;
    }

    protected function validTrend(mixed $trend): string
    {
        $trend = strtolower((string) $trend);

        return in_array($trend, ['y', 'q', 'm', 'w', 'd'], true) ? $trend : 'y';
    }

    protected function trendSeries(User $viewer, ?int $agentId, ?int $year, string $trend): array
    {
        $query = $this->filteredTicketsQuery($viewer, $agentId, $year);

        return match ($trend) {
            'q' => $this->quarterSeries($query, $year),
            'm' => $this->monthSeries($query, $year),
            'w' => $this->weekSeries($query, $year),
            'd' => $this->daySeries($query, $year),
            default => $this->yearSeries($query, $year),
        };
    }

    protected function yearSeries(Builder $query, ?int $year): array
    {
        if ($year) {
            $items = collect(range(1, 12))->map(function (int $month) use ($query, $year): array {
                return [
                    'label' => Carbon::create($year, $month, 1)->format('M'),
                    'value' => (clone $query)->whereMonth('created_at', $month)->count(),
                ];
            });
        } else {
            $currentYear = now()->year;
            $items = collect(range($currentYear - 4, $currentYear))->map(function (int $seriesYear) use ($query): array {
                return [
                    'label' => (string) $seriesYear,
                    'value' => (clone $query)->whereYear('created_at', $seriesYear)->count(),
                ];
            });
        }

        return [
            'mode' => 'y',
            'label' => $year ? "{$year} oylik kesimi" : 'Yillik trend',
            'items' => $items->all(),
            'max' => max(1, (int) $items->max('value')),
        ];
    }

    protected function quarterSeries(Builder $query, ?int $year): array
    {
        $year = $year ?: now()->year;
        $items = collect(range(1, 4))->map(function (int $quarter) use ($query, $year): array {
            $startMonth = (($quarter - 1) * 3) + 1;
            $endMonth = $startMonth + 2;
            $start = Carbon::create($year, $startMonth, 1)->startOfMonth();
            $end = Carbon::create($year, $endMonth, 1)->endOfMonth();

            return [
                'label' => 'Q'.$quarter,
                'value' => (clone $query)->whereBetween('created_at', [$start, $end])->count(),
            ];
        });

        return [
            'mode' => 'q',
            'label' => "{$year} chorak kesimi",
            'items' => $items->all(),
            'max' => max(1, (int) $items->max('value')),
        ];
    }

    protected function monthSeries(Builder $query, ?int $year): array
    {
        $year = $year ?: now()->year;
        $items = collect(range(1, 12))->map(function (int $month) use ($query, $year): array {
            return [
                'label' => Carbon::create($year, $month, 1)->format('M'),
                'value' => (clone $query)
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->count(),
            ];
        });

        return [
            'mode' => 'm',
            'label' => "{$year} oylar kesimi",
            'items' => $items->all(),
            'max' => max(1, (int) $items->max('value')),
        ];
    }

    protected function weekSeries(Builder $query, ?int $year): array
    {
        $anchor = $year ? Carbon::create($year, 12, 31) : now();
        $start = $anchor->copy()->subWeeks(7)->startOfWeek();
        $items = collect(range(0, 7))->map(function (int $index) use ($query, $start): array {
            $weekStart = $start->copy()->addWeeks($index);
            $weekEnd = $weekStart->copy()->endOfWeek();

            return [
                'label' => $weekStart->format('d.m'),
                'value' => (clone $query)->whereBetween('created_at', [$weekStart, $weekEnd])->count(),
            ];
        });

        return [
            'mode' => 'w',
            'label' => "So'nggi 8 hafta",
            'items' => $items->all(),
            'max' => max(1, (int) $items->max('value')),
        ];
    }

    protected function daySeries(Builder $query, ?int $year): array
    {
        $anchor = $year ? Carbon::create($year, now()->month, min(now()->day, 28)) : now();
        $start = $anchor->copy()->subDays(13)->startOfDay();
        $items = collect(range(0, 13))->map(function (int $index) use ($query, $start): array {
            $day = $start->copy()->addDays($index);

            return [
                'label' => $day->format('d.m'),
                'value' => (clone $query)->whereDate('created_at', $day)->count(),
            ];
        });

        return [
            'mode' => 'd',
            'label' => "So'nggi 14 kun",
            'items' => $items->all(),
            'max' => max(1, (int) $items->max('value')),
        ];
    }

    protected function categoryBreakdown(Builder $query): array
    {
        $tickets = (clone $query)
            ->with('category:id,name')
            ->get(['id', 'category_id', 'status'])
            ->groupBy(fn (Ticket $ticket) => $ticket->category?->name ?? 'Belgilanmagan');

        $items = $tickets
            ->map(function (Collection $group, string $label): array {
                return [
                    'label' => $label,
                    'total' => $group->count(),
                    'resolved' => $group->whereIn('status', [TicketStatus::Completed, TicketStatus::Closed])->count(),
                ];
            })
            ->sortByDesc('total')
            ->take(6)
            ->values();

        return [
            'items' => $items,
            'max' => max(1, (int) $items->max('total')),
        ];
    }

    protected function priorityMatrix(Builder $query): array
    {
        $tickets = (clone $query)->get(['id', 'priority', 'status']);
        $rows = collect(TicketPriority::dashboardCases())->map(function (TicketPriority $priority) use ($tickets): array {
            $group = $tickets->filter(fn (Ticket $ticket) => $ticket->priority === $priority);

            return [
                'label' => $priority->label(),
                'key' => $priority->value,
                'total' => $group->count(),
                'new' => $group->where('status', TicketStatus::New)->count(),
                'in_progress' => $group->whereIn('status', [TicketStatus::Assigned, TicketStatus::InProgress])->count(),
                'completed' => $group->whereIn('status', [TicketStatus::Completed, TicketStatus::Closed])->count(),
                'returned' => $group->where('status', TicketStatus::Returned)->count(),
            ];
        });

        return [
            'rows' => $rows->all(),
            'max' => max(1, (int) $rows->max('total')),
        ];
    }
}

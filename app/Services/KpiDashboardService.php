<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class KpiDashboardService
{
    public const SCORE_WEIGHTS = [
        'completed' => 40,
        'sla' => 30,
        'late' => 15,
        'quality' => 10,
        'activity' => 5,
    ];

    public function dashboard(Carbon $start, Carbon $end, array $filters = []): array
    {
        $ticketsQuery = $this->ticketsQuery($start, $end, $filters);
        $completedQuery = $this->completedTicketsQuery($start, $end, $filters);

        $summary = $this->summary(clone $ticketsQuery, clone $completedQuery, $start, $end, $filters);

        return [
            'period' => $this->periodPayload($start, $end),
            'filters' => $filters,
            'filterOptions' => $this->filterOptions(),
            'summary' => $summary,
            'scoreWeights' => self::SCORE_WEIGHTS,
            'statusItems' => $this->statusItems(clone $ticketsQuery),
            'completionTrend' => $this->completionTrend($start, $end, $filters),
            'executorScores' => $this->executorScores($start, $end, $filters)->take(8)->values(),
            'departmentItems' => $this->departmentItems(clone $ticketsQuery, clone $completedQuery),
            'categoryItems' => $this->categoryItems(clone $ticketsQuery, clone $completedQuery),
            'monthlyComparison' => $this->monthlyComparison($start, $filters),
            'ticketJournal' => $this->ticketJournal($start, $end, $filters),
        ];
    }

    public function monthlyReport(Carbon $month, array $filters = []): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $dashboard = $this->dashboard($start, $end, $filters);
        $rows = $this->executorScores($start, $end, $filters);

        return [
            ...$dashboard,
            'monthValue' => $start->format('Y-m'),
            'monthLabel' => $start->translatedFormat('F Y'),
            'executorRows' => $rows,
            'topExecutor' => $rows->first(),
            'exportUrls' => [
                'excel' => route('manager.dashboard.export', [
                    'stat' => 'monthly',
                    'month' => $start->format('Y-m'),
                    'format' => 'excel',
                    ...$filters,
                ]),
                'csv' => route('manager.dashboard.export', [
                    'stat' => 'monthly',
                    'month' => $start->format('Y-m'),
                    'format' => 'csv',
                    ...$filters,
                ]),
                'tickets_excel' => route('manager.dashboard.export', [
                    'stat' => 'tickets',
                    'month' => $start->format('Y-m'),
                    'format' => 'excel',
                    ...$filters,
                ]),
            ],
        ];
    }

    public function executorScores(Carbon $start, Carbon $end, array $filters = []): Collection
    {
        $executors = User::query()
            ->role('executor')
            ->select(['id', 'name', 'email', 'phone', 'department_id'])
            ->with('department:id,name')
            ->when($filters['executor_id'] ?? null, fn (Builder $query, int $id): Builder => $query->whereKey($id))
            ->when($filters['department_id'] ?? null, fn (Builder $query, int $id): Builder => $query->where('department_id', $id))
            ->orderBy('name')
            ->get();

        return $executors
            ->map(function (User $executor) use ($start, $end, $filters): array {
                $executorFilters = [
                    ...$filters,
                    'executor_id' => $executor->id,
                ];

                $summary = $this->summary(
                    $this->ticketsQuery($start, $end, $executorFilters),
                    $this->completedTicketsQuery($start, $end, $executorFilters),
                    $start,
                    $end,
                    $executorFilters,
                );

                return [
                    'id' => $executor->id,
                    'name' => $executor->name,
                    'email' => $executor->email,
                    'phone' => $executor->phone,
                    'department' => $executor->department?->name ?? '-',
                    'total' => $summary['total'],
                    'completed' => $summary['completed'],
                    'on_time' => $summary['on_time'],
                    'late_completed' => $summary['late_completed'],
                    'returned' => $summary['returned'],
                    'rejected' => $summary['rejected'],
                    'sla_percent' => $summary['sla_percent'],
                    'avg_resolution_hours' => $summary['avg_resolution_hours'],
                    'kpi_score' => $summary['kpi_score'],
                    'grade' => $this->grade($summary['kpi_score']),
                ];
            })
            ->sortByDesc('kpi_score')
            ->values();
    }

    public function ticketExportRows(Carbon $start, Carbon $end, array $filters = [], string $stat = 'tickets'): \Generator
    {
        $query = match ($stat) {
            'complaints' => $this->complaintTicketsQuery($start, $end, $filters),
            default => $this->completedTicketsQuery($start, $end, $filters),
        };

        foreach ($query->with(['assignedDepartment', 'assignedExecutor', 'category', 'requester'])->latest('updated_at')->cursor() as $ticket) {
            yield $this->ticketRow($ticket);
        }
    }

    public function monthlyExportRows(Carbon $month, array $filters = []): \Generator
    {
        foreach ($this->executorScores($month->copy()->startOfMonth(), $month->copy()->endOfMonth(), $filters) as $row) {
            yield [
                $row['name'],
                $row['department'],
                $row['total'],
                $row['completed'],
                $row['on_time'],
                $row['late_completed'],
                $row['returned'],
                $row['rejected'],
                $row['sla_percent'].'%',
                $row['avg_resolution_hours'] !== null ? $row['avg_resolution_hours'].' soat' : '-',
                $row['kpi_score'],
                $row['grade'],
            ];
        }
    }

    public function filterOptions(): array
    {
        return [
            'departments' => Department::query()->select(['id', 'name'])->where('is_active', true)->orderBy('name')->get(),
            'executors' => User::query()->role('executor')->select(['id', 'name', 'department_id'])->orderBy('name')->get(),
            'categories' => Category::query()->select(['id', 'name'])->where('is_active', true)->orderBy('name')->get(),
            'priorities' => TicketPriority::assignableCases(),
        ];
    }

    protected function summary(Builder $ticketsQuery, Builder $completedQuery, Carbon $start, Carbon $end, array $filters): array
    {
        $total = (clone $ticketsQuery)->count();
        $completed = (clone $completedQuery)->count();
        $closed = (clone $ticketsQuery)->where('status', TicketStatus::Closed->value)->count();
        $active = (clone $ticketsQuery)->whereIn('status', $this->activeStatuses())->count();
        $overdue = (clone $ticketsQuery)->where(function (Builder $query): void {
            $query->where('status', TicketStatus::Overdue->value)
                ->orWhere(function (Builder $inner): void {
                    $inner->whereNotNull('deadline_at')
                        ->where('deadline_at', '<', now())
                        ->whereNotIn('status', $this->finishedStatuses());
                });
        })->count();
        $returned = $this->historyCount($start, $end, TicketStatus::Returned, $filters);
        $rejected = $this->historyCount($start, $end, TicketStatus::Rejected, $filters);
        $complaints = $returned + $rejected;
        $onTime = (clone $completedQuery)->where(function (Builder $query): void {
            $query->whereNull('deadline_at')
                ->orWhereColumn('completed_at', '<=', 'deadline_at');
        })->count();
        $lateCompleted = max(0, $completed - $onTime);
        $sameDay = (clone $completedQuery)
            ->get(['id', 'created_at', 'completed_at'])
            ->filter(fn (Ticket $ticket): bool => $ticket->created_at && $ticket->completed_at && $ticket->created_at->isSameDay($ticket->completed_at))
            ->count();

        $resolutionSeconds = (clone $completedQuery)
            ->get(['id', 'created_at', 'completed_at'])
            ->map(fn (Ticket $ticket): ?int => $ticket->created_at && $ticket->completed_at
                ? $ticket->completed_at->diffInSeconds($ticket->created_at)
                : null)
            ->filter(fn ($value): bool => is_int($value) && $value >= 0);

        $slaPercent = $completed > 0 ? round(($onTime / $completed) * 100, 1) : 0.0;
        $avgResolutionHours = $resolutionSeconds->isNotEmpty()
            ? round(((float) $resolutionSeconds->avg()) / 3600, 1)
            : null;
        $scoreParts = $this->scoreParts($total, $completed, $onTime, $lateCompleted, $complaints);
        $kpiScore = round(array_sum($scoreParts), 1);

        return [
            'total' => $total,
            'active' => $active,
            'completed' => $completed,
            'closed' => $closed,
            'overdue' => $overdue,
            'returned' => $returned,
            'rejected' => $rejected,
            'complaints' => $complaints,
            'on_time' => $onTime,
            'late_completed' => $lateCompleted,
            'same_day' => $sameDay,
            'sla_percent' => $slaPercent,
            'avg_resolution_hours' => $avgResolutionHours,
            'kpi_score' => $kpiScore,
            'grade' => $this->grade($kpiScore),
            'score_parts' => $scoreParts,
        ];
    }

    protected function scoreParts(int $total, int $completed, int $onTime, int $lateCompleted, int $complaints): array
    {
        return [
            'completed' => $total > 0 ? min(self::SCORE_WEIGHTS['completed'], ($completed / $total) * self::SCORE_WEIGHTS['completed']) : 0,
            'sla' => $completed > 0 ? ($onTime / $completed) * self::SCORE_WEIGHTS['sla'] : 0,
            'late' => $completed > 0 ? max(0, (1 - ($lateCompleted / max(1, $completed))) * self::SCORE_WEIGHTS['late']) : 0,
            'quality' => $total > 0 ? max(0, (1 - ($complaints / max(1, $total))) * self::SCORE_WEIGHTS['quality']) : 0,
            'activity' => $total > 0 ? min(self::SCORE_WEIGHTS['activity'], ($completed / max(1, $total)) * self::SCORE_WEIGHTS['activity']) : 0,
        ];
    }

    protected function ticketsQuery(Carbon $start, Carbon $end, array $filters): Builder
    {
        return $this->applyFilters(Ticket::query()
            ->whereBetween('created_at', [$start, $end]), $filters);
    }

    protected function completedTicketsQuery(Carbon $start, Carbon $end, array $filters): Builder
    {
        return $this->applyFilters(Ticket::query()
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start, $end])
            ->whereIn('status', [TicketStatus::Completed->value, TicketStatus::Closed->value]), $filters);
    }

    protected function complaintTicketsQuery(Carbon $start, Carbon $end, array $filters): Builder
    {
        return $this->applyFilters(Ticket::query()->whereIn('id', TicketStatusHistory::query()
            ->select('ticket_id')
            ->whereIn('to_status', [TicketStatus::Returned->value, TicketStatus::Rejected->value])
            ->whereBetween('created_at', [$start, $end])), $filters);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['department_id'] ?? null, fn (Builder $query, int $id): Builder => $query->where('assigned_department_id', $id))
            ->when($filters['executor_id'] ?? null, fn (Builder $query, int $id): Builder => $query->where('assigned_executor_id', $id))
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $id): Builder => $query->where('category_id', $id))
            ->when($filters['priority'] ?? null, fn (Builder $query, string $priority): Builder => $query->where('priority', $priority));
    }

    protected function historyCount(Carbon $start, Carbon $end, TicketStatus $status, array $filters): int
    {
        return $this->applyFilters(Ticket::query()->whereIn('id', TicketStatusHistory::query()
            ->select('ticket_id')
            ->where('to_status', $status->value)
            ->whereBetween('created_at', [$start, $end])), $filters)
            ->distinct('id')
            ->count('id');
    }

    protected function statusItems(Builder $query): Collection
    {
        $counts = (clone $query)
            ->get(['status'])
            ->countBy(fn (Ticket $ticket): string => $ticket->status->value);

        return collect(TicketStatus::cases())->map(fn (TicketStatus $status): array => [
            'label' => $status->label(),
            'value' => (int) ($counts[$status->value] ?? 0),
            'style' => $status->badgeStyle(),
        ]);
    }

    protected function completionTrend(Carbon $start, Carbon $end, array $filters): Collection
    {
        $days = $start->diffInDays($end) + 1;

        if ($days <= 45) {
            return collect(range(0, $days - 1))->map(function (int $index) use ($start, $filters): array {
                $day = $start->copy()->addDays($index);

                return [
                    'label' => $day->format('d.m'),
                    'value' => (clone $this->completedTicketsQuery($day->copy()->startOfDay(), $day->copy()->endOfDay(), $filters))->count(),
                ];
            });
        }

        $cursor = $start->copy()->startOfMonth();
        $items = collect();

        while ($cursor->lte($end)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth()->min($end);
            $items->push([
                'label' => $monthStart->format('M Y'),
                'value' => (clone $this->completedTicketsQuery($monthStart, $monthEnd, $filters))->count(),
            ]);
            $cursor->addMonthNoOverflow();
        }

        return $items;
    }

    protected function departmentItems(Builder $ticketsQuery, Builder $completedQuery): Collection
    {
        $totals = (clone $ticketsQuery)->get(['assigned_department_id'])->countBy('assigned_department_id');
        $completed = (clone $completedQuery)->get(['assigned_department_id'])->countBy('assigned_department_id');

        return Department::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Department $department): array => [
                'label' => $department->name,
                'total' => (int) ($totals[$department->id] ?? 0),
                'completed' => (int) ($completed[$department->id] ?? 0),
            ])
            ->filter(fn (array $item): bool => $item['total'] > 0 || $item['completed'] > 0)
            ->sortByDesc('total')
            ->take(8)
            ->values();
    }

    protected function categoryItems(Builder $ticketsQuery, Builder $completedQuery): Collection
    {
        $totals = (clone $ticketsQuery)->get(['category_id'])->countBy('category_id');
        $completed = (clone $completedQuery)->get(['category_id'])->countBy('category_id');

        return Category::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => [
                'label' => $category->name,
                'total' => (int) ($totals[$category->id] ?? 0),
                'completed' => (int) ($completed[$category->id] ?? 0),
            ])
            ->filter(fn (array $item): bool => $item['total'] > 0 || $item['completed'] > 0)
            ->sortByDesc('total')
            ->take(8)
            ->values();
    }

    protected function monthlyComparison(Carbon $start, array $filters): array
    {
        $currentStart = $start->copy()->startOfMonth();
        $currentEnd = $currentStart->copy()->endOfMonth();
        $previousStart = $currentStart->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $previousStart->copy()->endOfMonth();

        $current = $this->summary(
            $this->ticketsQuery($currentStart, $currentEnd, $filters),
            $this->completedTicketsQuery($currentStart, $currentEnd, $filters),
            $currentStart,
            $currentEnd,
            $filters,
        );
        $previous = $this->summary(
            $this->ticketsQuery($previousStart, $previousEnd, $filters),
            $this->completedTicketsQuery($previousStart, $previousEnd, $filters),
            $previousStart,
            $previousEnd,
            $filters,
        );

        return [
            'current_label' => $currentStart->translatedFormat('F Y'),
            'previous_label' => $previousStart->translatedFormat('F Y'),
            'completed_delta' => $current['completed'] - $previous['completed'],
            'score_delta' => round($current['kpi_score'] - $previous['kpi_score'], 1),
            'sla_delta' => round($current['sla_percent'] - $previous['sla_percent'], 1),
        ];
    }

    protected function ticketJournal(Carbon $start, Carbon $end, array $filters): Collection
    {
        return $this->applyFilters(Ticket::query(), $filters)
            ->where(function (Builder $query) use ($start, $end): void {
                $query->whereBetween('created_at', [$start, $end])
                    ->orWhereBetween('completed_at', [$start, $end]);
            })
            ->with(['assignedExecutor:id,name', 'assignedDepartment:id,name', 'category:id,name'])
            ->latest('updated_at')
            ->limit(12)
            ->get()
            ->map(fn (Ticket $ticket): array => [
                'reference' => $ticket->reference,
                'requester' => $ticket->requester_name,
                'description' => $ticket->description,
                'executor' => $ticket->assignedExecutor?->name ?? 'Biriktirilmagan',
                'department' => $ticket->assignedDepartment?->name ?? '-',
                'status' => $ticket->status->label(),
                'status_style' => $ticket->status->badgeStyle(),
                'created_at' => $ticket->created_at?->format('d.m.Y H:i'),
                'completed_at' => $ticket->completed_at?->format('d.m.Y H:i') ?? '-',
            ]);
    }

    protected function ticketRow(Ticket $ticket): array
    {
        return [
            $ticket->reference,
            $ticket->requester?->name ?? $ticket->requester_name,
            $ticket->priority->label(),
            $ticket->status->label(),
            $ticket->assignedDepartment?->name ?? '-',
            $ticket->assignedExecutor?->name ?? '-',
            $ticket->category?->name ?? '-',
            $ticket->created_at?->format('d.m.Y H:i'),
            $ticket->completed_at?->format('d.m.Y H:i') ?? '-',
            $ticket->deadline_at?->format('d.m.Y H:i') ?? '-',
            $ticket->deadline_at && $ticket->completed_at?->greaterThan($ticket->deadline_at) ? 'Kechikkan' : 'Muddatida',
            $ticket->description,
        ];
    }

    protected function periodPayload(Carbon $start, Carbon $end): array
    {
        return [
            'start' => $start,
            'end' => $end,
            'from' => $start->format('Y-m-d'),
            'to' => $end->format('Y-m-d'),
            'label' => $start->format('d.m.Y').' - '.$end->format('d.m.Y'),
        ];
    }

    protected function grade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'Juda yaxshi',
            $score >= 75 => 'Yaxshi',
            $score >= 60 => 'Qoniqarli',
            default => "E'tibor kerak",
        };
    }

    protected function activeStatuses(): array
    {
        return [
            TicketStatus::New->value,
            TicketStatus::Assigned->value,
            TicketStatus::InProgress->value,
            TicketStatus::Returned->value,
            TicketStatus::Overdue->value,
        ];
    }

    protected function finishedStatuses(): array
    {
        return [
            TicketStatus::Completed->value,
            TicketStatus::Closed->value,
            TicketStatus::Rejected->value,
        ];
    }
}

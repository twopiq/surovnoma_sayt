<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Support\TableExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManagerDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        [$start, $end, $monthValue, $monthLabel] = $this->reportPeriod($request);
        $filters = $this->reportFilters($request);
        $completionChartOptions = $this->completionChartOptions($request);
        [$completionChartItems, $completionChartMeta] = $this->completionChart($completionChartOptions, $filters);
        $completedQuery = $this->applyBreakdownFilters($this->completedTicketsQuery($start, $end), $filters);
        $completedCount = (clone $completedQuery)->count();
        $onTimeCount = $this->applyStatFilter(clone $completedQuery, 'on_time')->count();
        $complaintsCount = $this->complaintTicketsQuery($start, $end)->count();
        $averageRating = $this->averageRating($completedCount, $onTimeCount);

        $stats = collect($this->statMeta())->map(function (array $meta, string $stat) use ($start, $end, $monthValue, $filters): array {
            return [
                ...$meta,
                'value' => match ($stat) {
                    'rating' => number_format($this->averageRating(
                        $this->applyBreakdownFilters($this->completedTicketsQuery($start, $end), $filters)->count(),
                        $this->applyStatFilter(
                            $this->applyBreakdownFilters($this->completedTicketsQuery($start, $end), $filters),
                            'on_time',
                        )->count(),
                    ), 1),
                    'complaints' => $this->complaintTicketsQuery($start, $end)->count(),
                    default => $this->applyBreakdownFilters($this->completedTicketsQuery($start, $end), $filters)->count(),
                },
                'excel_url' => route('manager.dashboard.export', [
                    'stat' => $stat,
                    'month' => $monthValue,
                    'format' => 'excel',
                    ...$filters,
                ]),
                'csv_url' => route('manager.dashboard.export', [
                    'stat' => $stat,
                    'month' => $monthValue,
                    'format' => 'csv',
                    ...$filters,
                ]),
            ];
        })->all();

        $employeeResults = (clone $completedQuery)
            ->select('assigned_executor_id')
            ->selectRaw('count(*) as total')
            ->with('assignedExecutor:id,name')
            ->groupBy('assigned_executor_id')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn (Ticket $ticket): array => [
                'label' => $ticket->assignedExecutor?->name ?? 'Biriktirilmagan',
                'value' => (int) $ticket->total,
            ]);

        $employeeMax = max(1, (int) $employeeResults->max('value'));

        $monthlyIndicators = collect([
            ['label' => 'Yakunlangan', 'value' => $completedCount, 'hex' => '#14b8a6'],
            ['label' => 'Shikoyatlar', 'value' => $complaintsCount, 'hex' => '#f43f5e'],
            ['label' => 'Reyting x10', 'value' => (int) round($averageRating * 10), 'hex' => '#8b5cf6'],
        ]);

        $indicatorMax = max(1, (int) $monthlyIndicators->max('value'));
        $activeWorkload = $this->activeWorkloadByExecutor();
        $activeWorkloadMax = max(1, (int) $activeWorkload->max('value'));
        $topExecutorWorkload = $this->topExecutorWorkload();
        $executors = User::query()->role('executor')->select(['id', 'name'])->orderBy('name')->get();

        return view('manager.dashboard', [
            'monthValue' => $monthValue,
            'monthLabel' => $monthLabel,
            'stats' => $stats,
            'completionChartItems' => $completionChartItems,
            'completionChartMax' => max(1, (int) $completionChartItems->max('value')),
            'completionChartMeta' => $completionChartMeta,
            'completionChartOptions' => $completionChartOptions,
            'executors' => $executors,
            'employeeResults' => $employeeResults,
            'employeeMax' => $employeeMax,
            'monthlyIndicators' => $monthlyIndicators,
            'indicatorMax' => $indicatorMax,
            'activeWorkload' => $activeWorkload,
            'activeWorkloadMax' => $activeWorkloadMax,
            'topExecutorWorkload' => $topExecutorWorkload,
            'activeFilters' => $filters,
        ]);
    }

    public function export(Request $request, string $stat): StreamedResponse
    {
        abort_unless(array_key_exists($stat, $this->statMeta()), 404);

        [$start, $end, $monthValue, $monthLabel] = $this->reportPeriod($request);
        $filters = $this->reportFilters($request);
        $format = (string) $request->query('format', 'excel');
        $query = $this->reportTicketsForStat($stat, $start, $end, $filters)
            ->with(['assignedDepartment', 'assignedExecutor', 'requester', 'category'])
            ->latest('updated_at');

        $filename = 'yakunlangan-ishlar-'.$stat.'-'.$monthValue;
        $title = $this->statMeta()[$stat]['label'].' - '.$monthLabel;
        $headings = [
            'Raqam',
            'Sarlavha',
            'Murojaatchi',
            'Prioritet',
            'Holat',
            "Bo'lim",
            'Ijrochi',
            'Kategoriya',
            'Topshirilgan vaqt',
            'Yakunlangan vaqt',
            'Muddat',
            'SLA natija',
        ];
        $rows = (function () use ($query): \Generator {
            foreach ($query->cursor() as $ticket) {
                yield $this->ticketRow($ticket);
            }
        })();

        return TableExport::download($format, $filename, $title, $headings, $rows, [
            'Oy' => $monthLabel,
            'Eksport qilingan vaqt' => now(),
            'Format' => strtolower($format) === 'csv' ? 'CSV' : 'Excel',
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string, 3: string}
     */
    protected function reportPeriod(Request $request): array
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $monthValue = $validated['month'] ?? now()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m-d', $monthValue.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [$start, $end, $monthValue, $start->translatedFormat('F Y')];
    }

    /**
     * @return array{priority?: string}
     */
    protected function reportFilters(Request $request): array
    {
        $validated = $request->validate([
            'priority' => ['nullable', Rule::in(array_column(TicketPriority::cases(), 'value'))],
        ]);

        return array_filter([
            'priority' => $validated['priority'] ?? null,
        ], fn (?string $value): bool => $value !== null && $value !== '');
    }

    /**
     * @return array{period: string, scope: string, executor_id: ?int, date: Carbon}
     */
    protected function completionChartOptions(Request $request): array
    {
        $validated = $request->validate([
            'chart_period' => ['nullable', Rule::in(['day', 'week', 'month', 'year'])],
            'chart_scope' => ['nullable', Rule::in(['total', 'employees'])],
            'chart_executor_id' => ['nullable', 'integer', 'exists:users,id'],
            'chart_date' => ['nullable', 'date'],
        ]);

        return [
            'period' => $validated['chart_period'] ?? 'month',
            'scope' => $validated['chart_scope'] ?? 'total',
            'executor_id' => ($validated['chart_executor_id'] ?? null) ? (int) $validated['chart_executor_id'] : null,
            'date' => isset($validated['chart_date']) ? Carbon::parse($validated['chart_date']) : now(),
        ];
    }

    protected function completedTicketsQuery(Carbon $start, Carbon $end): Builder
    {
        return Ticket::query()
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start, $end])
            ->whereIn('status', [TicketStatus::Completed->value, TicketStatus::Closed->value]);
    }

    protected function applyBreakdownFilters(Builder $query, array $filters): Builder
    {
        return $query->when($filters['priority'] ?? null, fn (Builder $query, string $priority): Builder => $query->where('priority', $priority));
    }

    protected function applyStatFilter(Builder $query, string $stat): Builder
    {
        return match ($stat) {
            'on_time' => $query->where(function (Builder $query): void {
                $query->whereNull('deadline_at')
                    ->orWhereColumn('completed_at', '<=', 'deadline_at');
            }),
            'overdue' => $query->whereNotNull('deadline_at')
                ->whereColumn('completed_at', '>', 'deadline_at'),
            'waiting_close' => $query->where('status', TicketStatus::Completed->value),
            'closed' => $query->where('status', TicketStatus::Closed->value),
            default => $query,
        };
    }

    protected function complaintTicketsQuery(Carbon $start, Carbon $end): Builder
    {
        return Ticket::query()->whereIn('id', TicketStatusHistory::query()
            ->select('ticket_id')
            ->whereIn('to_status', [TicketStatus::Returned->value, TicketStatus::Rejected->value])
            ->whereBetween('created_at', [$start, $end]));
    }

    protected function reportTicketsForStat(string $stat, Carbon $start, Carbon $end, array $filters): Builder
    {
        if ($stat === 'complaints') {
            return $this->complaintTicketsQuery($start, $end);
        }

        return $this->applyBreakdownFilters($this->completedTicketsQuery($start, $end), $filters);
    }

    protected function averageRating(int $completedCount, int $onTimeCount): float
    {
        if ($completedCount === 0) {
            return 0.0;
        }

        return round(($onTimeCount / $completedCount) * 5, 1);
    }

    protected function activeWorkloadByExecutor(): Collection
    {
        return User::query()
            ->role('executor')
            ->select(['id', 'name'])
            ->withCount(['assignedTickets as active_tickets_count' => function (Builder $query): void {
                $query->whereIn('status', [
                    TicketStatus::Assigned->value,
                    TicketStatus::InProgress->value,
                    TicketStatus::Returned->value,
                ]);
            }])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'label' => $user->name,
                'value' => (int) $user->active_tickets_count,
            ]);
    }

    protected function topExecutorWorkload(): Collection
    {
        return User::query()
            ->role('executor')
            ->select(['id', 'name', 'email', 'phone'])
            ->with(['assignedTickets' => function ($query): void {
                $query
                    ->select(['id', 'assigned_executor_id', 'priority', 'status'])
                    ->whereIn('status', [
                        TicketStatus::Assigned->value,
                        TicketStatus::InProgress->value,
                        TicketStatus::Returned->value,
                    ]);
            }])
            ->get()
            ->map(function (User $user): array {
                $activeTickets = $user->assignedTickets;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'active_count' => $activeTickets->count(),
                    'workload_units' => $activeTickets->sum(fn (Ticket $ticket): int => $ticket->priority->workloadUnits()),
                ];
            })
            ->sortByDesc('workload_units')
            ->take(5)
            ->values();
    }

    protected function completionChart(array $options, array $filters): array
    {
        [$start, $end, $buckets] = $this->completionChartPeriod($options['period'], $options['date']);

        $tickets = $this->applyBreakdownFilters($this->completedTicketsQuery($start, $end), $filters)
            ->with('assignedExecutor:id,name')
            ->when($options['executor_id'], fn (Builder $query, int $executorId): Builder => $query->where('assigned_executor_id', $executorId))
            ->get(['id', 'assigned_executor_id', 'completed_at']);

        if ($options['scope'] === 'employees') {
            $counts = $tickets
                ->groupBy('assigned_executor_id')
                ->map(fn (Collection $group): int => $group->count());

            $items = User::query()
                ->role('executor')
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
                ->when($options['executor_id'], fn (Collection $executors): Collection => $executors->where('id', $options['executor_id'])->values())
                ->map(fn (User $user): array => [
                    'label' => $user->name,
                    'value' => (int) ($counts[$user->id] ?? 0),
                ])
                ->sortByDesc('value')
                ->values();
        } else {
            $counts = $tickets
                ->groupBy(fn (Ticket $ticket): string => $this->completionBucketKey($ticket->completed_at, $options['period']))
                ->map(fn (Collection $group): int => $group->count());

            $items = $buckets->map(fn (array $bucket): array => [
                'label' => $bucket['label'],
                'value' => (int) ($counts[$bucket['key']] ?? 0),
            ]);
        }

        $executorName = $options['executor_id']
            ? User::query()->whereKey($options['executor_id'])->value('name')
            : null;

        return [
            $items,
            [
                'period_label' => $this->completionPeriodLabel($options['period']),
                'scope_label' => $options['scope'] === 'employees' ? 'Xodimlar kesimida' : 'Butun bajaruvchilar kesimida',
                'executor_label' => $executorName ?: 'Barcha bajaruvchilar',
                'range_label' => $start->format('d.m.Y').' - '.$end->format('d.m.Y'),
            ],
        ];
    }

    protected function completionChartPeriod(string $period, Carbon $date): array
    {
        return match ($period) {
            'day' => $this->dayBuckets($date),
            'week' => $this->weekBuckets($date),
            'year' => $this->yearBuckets($date),
            default => $this->monthBuckets($date),
        };
    }

    protected function dayBuckets(Carbon $date): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();
        $buckets = collect(range(0, 23))->map(fn (int $hour): array => [
            'key' => $start->copy()->addHours($hour)->format('Y-m-d H'),
            'label' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT),
        ]);

        return [$start, $end, $buckets];
    }

    protected function weekBuckets(Carbon $date): array
    {
        $start = $date->copy()->startOfWeek();
        $end = $date->copy()->endOfWeek();
        $buckets = collect(range(0, 6))->map(fn (int $day): array => [
            'key' => $start->copy()->addDays($day)->format('Y-m-d'),
            'label' => $start->copy()->addDays($day)->format('d.m'),
        ]);

        return [$start, $end, $buckets];
    }

    protected function monthBuckets(Carbon $date): array
    {
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();
        $buckets = collect(range(1, $start->daysInMonth))->map(fn (int $day): array => [
            'key' => $start->copy()->day($day)->format('Y-m-d'),
            'label' => str_pad((string) $day, 2, '0', STR_PAD_LEFT),
        ]);

        return [$start, $end, $buckets];
    }

    protected function yearBuckets(Carbon $date): array
    {
        $start = $date->copy()->startOfYear();
        $end = $date->copy()->endOfYear();
        $buckets = collect(range(1, 12))->map(fn (int $month): array => [
            'key' => $start->copy()->month($month)->format('Y-m'),
            'label' => $start->copy()->month($month)->format('M'),
        ]);

        return [$start, $end, $buckets];
    }

    protected function completionBucketKey(?Carbon $completedAt, string $period): string
    {
        return match ($period) {
            'day' => $completedAt?->format('Y-m-d H') ?? '',
            'year' => $completedAt?->format('Y-m') ?? '',
            default => $completedAt?->format('Y-m-d') ?? '',
        };
    }

    protected function completionPeriodLabel(string $period): string
    {
        return match ($period) {
            'day' => 'Kunlik',
            'week' => 'Haftalik',
            'year' => 'Yillik',
            default => 'Oylik',
        };
    }

    protected function ticketRow(Ticket $ticket): array
    {
        return [
            'reference' => $ticket->reference,
            'title' => $ticket->title ?? $ticket->description,
            'requester' => $ticket->requester?->name ?? $ticket->requester_name,
            'priority' => $ticket->priority->label(),
            'status' => $ticket->status->label(),
            'department' => $ticket->assignedDepartment?->name,
            'executor' => $ticket->assignedExecutor?->name,
            'category' => $ticket->category?->name,
            'created_at' => $ticket->created_at?->toDateTimeString(),
            'completed_at' => $ticket->completed_at?->toDateTimeString(),
            'deadline_at' => $ticket->deadline_at?->toDateTimeString(),
            'sla_result' => $ticket->deadline_at && $ticket->completed_at?->greaterThan($ticket->deadline_at) ? 'Kechikkan' : 'Muddatida',
        ];
    }

    protected function statMeta(): array
    {
        return [
            'selected' => [
                'label' => 'Tanlangan ishlar',
                'description' => 'Tanlangan oyda yakunlangan ishlar.',
                'accent' => 'bg-emerald-100 text-emerald-700',
                'icon' => 'check',
            ],
            'rating' => [
                'label' => "O'rtacha reyting",
                'description' => 'SLA asosida 5 ballik baho.',
                'accent' => 'bg-orange-100 text-orange-700',
                'icon' => 'star',
            ],
            'complaints' => [
                'label' => 'Shikoyatlar',
                'description' => 'Qaytarilgan yoki rad etilgan murojaatlar.',
                'accent' => 'bg-rose-100 text-rose-700',
                'icon' => 'alert',
            ],
        ];
    }
}

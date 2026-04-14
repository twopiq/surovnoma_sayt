<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManagerDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        [$start, $end, $monthValue, $monthLabel] = $this->reportPeriod($request);
        $filters = $this->reportFilters($request);
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

        return view('manager.dashboard', [
            'monthValue' => $monthValue,
            'monthLabel' => $monthLabel,
            'stats' => $stats,
            'employeeResults' => $employeeResults,
            'employeeMax' => $employeeMax,
            'monthlyIndicators' => $monthlyIndicators,
            'indicatorMax' => $indicatorMax,
            'activeWorkload' => $activeWorkload,
            'activeWorkloadMax' => $activeWorkloadMax,
            'activeFilters' => $filters,
        ]);
    }

    public function export(Request $request, string $stat): StreamedResponse
    {
        abort_unless(array_key_exists($stat, $this->statMeta()), 404);

        [$start, $end, $monthValue, $monthLabel] = $this->reportPeriod($request);
        $filters = $this->reportFilters($request);
        $query = $this->reportTicketsForStat($stat, $start, $end, $filters)
            ->with(['assignedDepartment', 'assignedExecutor', 'requester', 'category'])
            ->latest('updated_at');

        $filename = 'yakunlangan-ishlar-'.$stat.'-'.$monthValue;

        return Response::streamDownload(function () use ($query, $monthLabel, $stat): void {
            echo "\xEF\xBB\xBF";
            echo '<html><head><meta charset="UTF-8"></head><body>';
            echo '<table border="1">';
            echo '<tr><th colspan="12">'.e($this->statMeta()[$stat]['label']).' - '.e($monthLabel).'</th></tr>';
            echo '<tr>';

            foreach ([
                'Raqam',
                'Sarlavha',
                'Murojaatchi',
                'Prioritet',
                'Holat',
                'Bo\'lim',
                'Ijrochi',
                'Kategoriya',
                'Topshirilgan vaqt',
                'Yakunlangan vaqt',
                'Muddat',
                'SLA natija',
            ] as $heading) {
                echo '<th>'.e($heading).'</th>';
            }

            echo '</tr>';

            foreach ($query->cursor() as $ticket) {
                echo '<tr>';

                foreach ($this->ticketRow($ticket) as $value) {
                    echo '<td>'.e((string) $value).'</td>';
                }

                echo '</tr>';
            }

            echo '</table></body></html>';
        }, $filename.'.xls', [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
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

<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $stats = collect($this->statMeta())->map(function (array $meta, string $stat) use ($start, $end, $monthValue, $filters): array {
            return [
                ...$meta,
                'value' => $this->applyStatFilter(
                    $this->applyBreakdownFilters($this->completedTicketsQuery($start, $end), $filters),
                    $stat,
                )->count(),
                'csv_url' => route('manager.dashboard.export', [
                    'stat' => $stat,
                    'format' => 'csv',
                    'month' => $monthValue,
                    ...$filters,
                ]),
                'json_url' => route('manager.dashboard.export', [
                    'stat' => $stat,
                    'format' => 'json',
                    'month' => $monthValue,
                    ...$filters,
                ]),
            ];
        })->all();

        $byPriorityCounts = (clone $this->applyBreakdownFilters($this->completedTicketsQuery($start, $end), $filters))
            ->selectRaw('priority, count(*) as aggregate')
            ->groupBy('priority')
            ->pluck('aggregate', 'priority');

        $byPriority = collect(TicketPriority::cases())->map(fn (TicketPriority $priority): array => [
            'label' => $priority->label(),
            'value' => $priority->value,
            'count' => (int) ($byPriorityCounts[$priority->value] ?? 0),
            'csv_url' => route('manager.dashboard.export', [
                'stat' => 'all',
                'format' => 'csv',
                'month' => $monthValue,
                ...$filters,
                'priority' => $priority->value,
            ]),
            'json_url' => route('manager.dashboard.export', [
                'stat' => 'all',
                'format' => 'json',
                'month' => $monthValue,
                ...$filters,
                'priority' => $priority->value,
            ]),
        ]);

        $completedTickets = $this->applyBreakdownFilters($this->completedTicketsQuery($start, $end), $filters)
            ->with(['assignedDepartment', 'assignedExecutor', 'requester', 'category'])
            ->latest('completed_at')
            ->paginate(12)
            ->withQueryString();

        return view('manager.dashboard', [
            'monthValue' => $monthValue,
            'monthLabel' => $monthLabel,
            'stats' => $stats,
            'byPriority' => $byPriority,
            'completedTickets' => $completedTickets,
            'activeFilters' => $filters,
        ]);
    }

    public function export(Request $request, string $stat, string $format): StreamedResponse|JsonResponse
    {
        abort_unless(array_key_exists($stat, $this->statMeta()), 404);
        abort_unless(in_array($format, ['csv', 'json'], true), 404);

        [$start, $end, $monthValue, $monthLabel] = $this->reportPeriod($request);
        $filters = $this->reportFilters($request);
        $query = $this->applyStatFilter(
            $this->applyBreakdownFilters(
                $this->completedTicketsQuery($start, $end)
                    ->with(['assignedDepartment', 'assignedExecutor', 'requester', 'category'])
                    ->latest('completed_at'),
                $filters,
            ),
            $stat,
        );

        $filename = 'yakunlangan-ishlar-'.$stat.'-'.$monthValue;

        if ($format === 'json') {
            return response()->json([
                'report' => 'Yakunlangan ishlar',
                'period' => $monthLabel,
                'stat' => $this->statMeta()[$stat]['label'],
                'filters' => $filters,
                'data' => $query->get()->map(fn (Ticket $ticket): array => $this->ticketRow($ticket))->values(),
            ]);
        }

        return Response::streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
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
            ]);

            foreach ($query->cursor() as $ticket) {
                fputcsv($handle, $this->ticketRow($ticket));
            }

            fclose($handle);
        }, $filename.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
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
            'all' => [
                'label' => 'Jami yakunlangan',
                'description' => 'Tanlangan oyda ijrochi tomonidan yakunlangan ishlar.',
            ],
            'on_time' => [
                'label' => 'Muddatida yakunlangan',
                'description' => 'SLA muddati ichida yoki muddatsiz yakunlangan ishlar.',
            ],
            'overdue' => [
                'label' => 'Muddatdan kechikkan',
                'description' => 'Belgilangan muddatdan keyin yakunlangan ishlar.',
            ],
            'waiting_close' => [
                'label' => 'Yopishga tayyor',
                'description' => 'Ijrochi bajargan, admin yopishini kutayotgan ishlar.',
            ],
            'closed' => [
                'label' => 'Yopilgan',
                'description' => 'Admin tomonidan yopilgan yakunlangan ishlar.',
            ],
        ];
    }
}

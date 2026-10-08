<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\View\Components\TicketCard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * /app/dashboard — "B: hikoya" ko'rinishi uchun ma'lumotlar.
 * Faqat so'ralgan vidjetlar hisoblanadi (rahbarga yashirilgan vidjet ma'lumoti umuman tayyorlanmaydi).
 */
class DashboardReportService extends KpiDashboardService
{
    public const HEATMAP_HOURS = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18];

    private const WEEKDAYS = [1 => 'Du', 2 => 'Se', 3 => 'Ch', 4 => 'Pa', 5 => 'Ju', 6 => 'Sh', 7 => 'Ya'];

    private const CHANNELS = [
        'guest' => 'Mehmon (veb, bot)',
        'requester' => 'Shaxsiy kabinet',
        'operator' => 'Operator',
    ];

    // [nomi, kalit, oshishi yaxshimi]
    private const KPI_CARDS = [
        ['Jami murojaatlar', 'total', true],
        ['Faol', 'active', false],
        ['Bajarildi', 'completed', true],
        ['Yopildi', 'closed', true],
        ['Kechikkan', 'overdue', false],
        ['Qaytarilgan', 'returned', false],
        ['Rad etilgan', 'rejected', false],
        ['Shikoyatlar', 'complaints', false],
    ];

    public function report(Carbon $start, Carbon $end, array $filters, array $widgets, bool $compare): array
    {
        $wants = fn (string $key): bool => in_array($key, $widgets, true);
        [$prevStart, $prevEnd] = $this->previousPeriod($start, $end);

        $summary = $this->summary($this->ticketsQuery($start, $end, $filters), $this->completedTicketsQuery($start, $end, $filters), $start, $end, $filters);
        $previous = $compare && $wants('kpi')
            ? $this->summary($this->ticketsQuery($prevStart, $prevEnd, $filters), $this->completedTicketsQuery($prevStart, $prevEnd, $filters), $prevStart, $prevEnd, $filters)
            : null;

        return [
            'summary' => $summary,
            'previousLabel' => $prevStart->format('d.m.Y').' – '.$prevEnd->format('d.m.Y'),
            'kpi' => $wants('kpi') ? $this->kpiCards($summary, $previous) : null,
            'trend' => $wants('trend') ? $this->trendChart($start, $end, $prevStart, $prevEnd, $filters, $compare) : null,
            'funnel' => $wants('funnel') ? $this->funnel($start, $end, $filters) : null,
            'statuses' => $wants('statuses') ? $this->statusDonut($this->ticketsQuery($start, $end, $filters)) : null,
            'channels' => $wants('channels') ? $this->channels($this->ticketsQuery($start, $end, $filters)) : null,
            'heatmap' => $wants('heatmap') ? $this->heatmap($this->ticketsQuery($start, $end, $filters)) : null,
            'resolution' => $wants('resolution') ? $this->resolution($this->completedTicketsQuery($start, $end, $filters)) : null,
            'atRisk' => $wants('at_risk') ? $this->atRisk($filters) : null,
            'executors' => $wants('executors') ? $this->executorScores($start, $end, $filters)->take(8)->values() : null,
            'scoreParts' => $wants('score_parts') ? $this->scorePartRows($summary) : null,
            'departments' => $wants('departments') ? $this->departmentItems($this->ticketsQuery($start, $end, $filters), $this->completedTicketsQuery($start, $end, $filters)) : null,
        ];
    }

    public function exportQuery(Carbon $start, Carbon $end, array $filters): Builder
    {
        return $this->ticketsQuery($start, $end, $filters);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function previousPeriod(Carbon $start, Carbon $end): array
    {
        $days = (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;

        return [$start->copy()->subDays($days)->startOfDay(), $start->copy()->subDay()->endOfDay()];
    }

    public static function letterGrade(float $score): string
    {
        return match (true) {
            $score >= 85 => 'A',
            $score >= 70 => 'B',
            $score >= 55 => 'C',
            default => 'D',
        };
    }

    private function kpiCards(array $summary, ?array $previous): array
    {
        return collect(self::KPI_CARDS)->map(function (array $card) use ($summary, $previous): array {
            [$label, $key, $upIsGood] = $card;
            $label = __($label);
            $value = (int) $summary[$key];
            $delta = null;
            $tone = 'neu';

            if ($previous !== null) {
                $before = (int) $previous[$key];
                $delta = $before > 0 ? round((($value - $before) / $before) * 100, 1) : ($value > 0 ? 100.0 : 0.0);
                $tone = $delta == 0 ? 'neu' : ((($delta > 0) === $upIsGood) ? 'up' : 'dn');
            }

            return ['label' => $label, 'value' => $value, 'delta' => $delta, 'tone' => $tone];
        })->all();
    }

    private function trendChart(Carbon $start, Carbon $end, Carbon $prevStart, Carbon $prevEnd, array $filters, bool $compare): array
    {
        $current = $this->completionTrend($start, $end, $filters)->values();
        $previous = $compare ? $this->completionTrend($prevStart, $prevEnd, $filters)->pluck('value')->values() : collect();

        [$left, $right, $top, $bottom] = [36, 612, 8, 158];
        $max = max(1, (int) $current->max('value'), (int) $previous->max());
        $count = max(1, $current->count() - 1);
        $x = fn (int $i): float => round($left + ($right - $left) * $i / $count, 1);
        $y = fn (int $v): float => round($bottom - ($bottom - $top) * $v / $max, 1);

        $points = $current->map(fn (array $item, int $i): string => $x($i).','.$y((int) $item['value']))->implode(' ');
        $prevPoints = $previous->take($current->count())->map(fn ($v, int $i): string => $x($i).','.$y((int) $v))->implode(' ');
        $labelStep = max(1, (int) ceil($current->count() / 8));

        return [
            'points' => $points,
            'area' => $current->isEmpty() ? '' : "{$left},{$bottom} {$points} {$x($current->count() - 1)},{$bottom}",
            'previous' => $prevPoints,
            'last' => $current->isEmpty() ? null : ['x' => $x($current->count() - 1), 'y' => $y((int) $current->last()['value'])],
            'gridY' => collect(range(0, 4))->map(fn (int $i): array => [
                'y' => round($top + ($bottom - $top) * $i / 4, 1),
                'label' => (int) round($max * (4 - $i) / 4),
            ])->all(),
            'labels' => $current->filter(fn ($item, int $i): bool => $i % $labelStep === 0)
                ->map(fn (array $item, int $i): array => ['x' => $x($i), 'label' => $item['label']])->values()->all(),
            'total' => (int) $current->sum('value'),
        ];
    }

    private function funnel(Carbon $start, Carbon $end, array $filters): array
    {
        $stages = [
            [TicketStatus::New, []],
            [TicketStatus::Assigned, [TicketStatus::Assigned, TicketStatus::InProgress, TicketStatus::Completed, TicketStatus::Closed, TicketStatus::Overdue, TicketStatus::Returned]],
            [TicketStatus::InProgress, [TicketStatus::InProgress, TicketStatus::Completed, TicketStatus::Closed]],
            [TicketStatus::Completed, [TicketStatus::Completed, TicketStatus::Closed]],
            [TicketStatus::Closed, [TicketStatus::Closed]],
        ];

        $total = $this->ticketsQuery($start, $end, $filters)->count();

        return collect($stages)->map(function (array $stage, int $index) use ($start, $end, $filters, $total): array {
            [$status, $reached] = $stage;
            $value = $index === 0 ? $total : $this->ticketsQuery($start, $end, $filters)
                ->where(function (Builder $query) use ($status, $reached): void {
                    $query->whereIn('status', array_map(fn (TicketStatus $s) => $s->value, $reached))
                        ->orWhereHas('histories', fn (Builder $history) => $history->where('to_status', $status->value));
                })
                ->count();

            return [
                'label' => $status->label(),
                'value' => $value,
                'percent' => $total > 0 ? (int) round($value / $total * 100) : 0,
                'opacity' => round(1 - $index * 0.14, 2),
            ];
        })->all();
    }

    private function statusDonut(Builder $query): array
    {
        $counts = $query->get(['status'])->countBy(fn (Ticket $ticket): string => $ticket->status->value);
        $total = (int) $counts->sum();
        $circumference = 2 * M_PI * 54;
        $offset = 0.0;

        $items = collect(TicketStatus::cases())
            ->map(fn (TicketStatus $status): array => ['status' => $status, 'value' => (int) ($counts[$status->value] ?? 0)])
            ->filter(fn (array $item): bool => $item['value'] > 0)
            ->map(function (array $item) use ($total, $circumference, &$offset): array {
                $length = $total > 0 ? $circumference * $item['value'] / $total : 0;
                $segment = [
                    'label' => $item['status']->label(),
                    'tone' => $item['status']->tone(),
                    'value' => $item['value'],
                    'dash' => round(max(0, $length - 2), 1).' '.round($circumference, 1),
                    'offset' => round(-$offset, 1),
                ];
                $offset += $length;

                return $segment;
            })
            ->values()
            ->all();

        return ['total' => $total, 'items' => $items];
    }

    private function channels(Builder $query): array
    {
        $counts = $query->get(['channel'])->countBy('channel')->sortDesc();
        $max = max(1, (int) $counts->max());
        $colors = ['accent', 'role-operator', 'role-ijrochi', 'role-rahbar'];

        return $counts->keys()->values()->map(fn (string $channel, int $i): array => [
            'label' => isset(self::CHANNELS[$channel]) ? __(self::CHANNELS[$channel]) : ucfirst($channel),
            'value' => (int) $counts[$channel],
            'width' => (int) round($counts[$channel] / $max * 100),
            'color' => $colors[$i % count($colors)],
        ])->all();
    }

    private function heatmap(Builder $query): array
    {
        $counts = [];

        foreach ($query->get(['id', 'created_at']) as $ticket) {
            $hour = (int) $ticket->created_at->format('G');
            $hour = max(self::HEATMAP_HOURS[0], min(self::HEATMAP_HOURS[count(self::HEATMAP_HOURS) - 1], $hour));
            $key = $ticket->created_at->isoWeekday().'-'.$hour;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $max = max(1, ...array_values($counts ?: [0]));

        return [
            'max' => array_sum($counts) > 0 ? $max : 0,
            'rows' => collect(self::WEEKDAYS)->map(fn (string $label, int $day): array => [
                'label' => __($label),
                'cells' => collect(self::HEATMAP_HOURS)->map(fn (int $hour): array => [
                    'hour' => $hour,
                    'value' => $counts["{$day}-{$hour}"] ?? 0,
                    'opacity' => round(max(0.06, ($counts["{$day}-{$hour}"] ?? 0) / $max), 2),
                ])->all(),
            ])->values()->all(),
        ];
    }

    private function resolution(Builder $completedQuery): array
    {
        $hours = $completedQuery->get(['id', 'created_at', 'completed_at'])
            ->map(fn (Ticket $t): ?float => $t->created_at && $t->completed_at ? abs($t->completed_at->diffInSeconds($t->created_at)) / 3600 : null)
            ->filter(fn ($v): bool => $v !== null)
            ->sort()
            ->values();

        $buckets = [['<1', 0, 1], ['1-2', 1, 2], ['2-4', 2, 4], ['4-8', 4, 8], ['8-24', 8, 24], ['1-3k', 24, 72], ['3k+', 72, INF]];
        $counts = collect($buckets)->map(fn (array $b): int => $hours->filter(fn (float $h): bool => $h >= $b[1] && $h < $b[2])->count());
        $max = max(1, (int) $counts->max());

        return [
            'bars' => collect($buckets)->map(fn (array $b, int $i): array => [
                'label' => $b[0],
                'value' => $counts[$i],
                'height' => round(120 * $counts[$i] / $max, 1),
            ])->all(),
            'median' => $this->percentile($hours, 0.5),
            'p90' => $this->percentile($hours, 0.9),
            'count' => $hours->count(),
        ];
    }

    private function percentile(Collection $sorted, float $p): ?float
    {
        if ($sorted->isEmpty()) {
            return null;
        }

        return round((float) $sorted[(int) floor(($sorted->count() - 1) * $p)], 1);
    }

    private function atRisk(array $filters): array
    {
        return $this->applyFilters(Ticket::query(), $filters)
            ->whereIn('status', $this->activeStatuses())
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<=', now()->addDay())
            ->with(['category:id,name', 'attachments:id,ticket_id,original_name'])
            ->orderBy('deadline_at')
            ->limit(6)
            ->get()
            ->map(function (Ticket $ticket): array {
                $card = new TicketCard($ticket);

                return [
                    'ticket' => $ticket,
                    'subject' => $card->subject,
                    'pill' => $card->pill,
                    'tone' => $card->tone === 'late' ? 'new' : 'in-progress',
                ];
            })
            ->all();
    }

    private function scorePartRows(array $summary): array
    {
        $labels = [
            'completed' => ['Bajarilgan murojaatlar', 'accent'],
            'sla' => ['SLA muddatida', 'role-operator'],
            'late' => ['Kechikish kamligi', 'status-in-progress-dot'],
            'quality' => ['Sifat (shikoyatsiz)', 'role-ijrochi'],
            'activity' => ['Faollik', 'role-rahbar'],
        ];

        return [
            'rows' => collect(self::SCORE_WEIGHTS)->map(fn (int $weight, string $key): array => [
                'label' => __($labels[$key][0]),
                'color' => $labels[$key][1],
                'value' => round((float) ($summary['score_parts'][$key] ?? 0), 1),
                'weight' => $weight,
                'width' => (int) round(($summary['score_parts'][$key] ?? 0) / $weight * 100),
            ])->values()->all(),
            'total' => $summary['kpi_score'],
        ];
    }
}

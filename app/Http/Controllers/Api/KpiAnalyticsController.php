<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketPriority;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KpiAnalyticsController extends KpiBaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->userFromRequest($request)) {
            return response()->json(['message' => 'Avtorizatsiya talab qilinadi.'], 401);
        }

        return response()->json([
            'trend'        => $this->trend(),
            'byDepartment' => $this->byDepartment(),
            'byCategory'   => $this->byCategory(),
            'byChannel'    => $this->byChannel(),
            'byPriority'   => $this->byPriority(),
            'byWeekday'    => $this->byWeekday(),
        ]);
    }

    private function trend(): array
    {
        $days = 30;
        $from = now()->subDays($days - 1)->startOfDay();

        $created = DB::table('tickets')
            ->selectRaw('date(created_at) as day, count(*) as cnt')
            ->where('created_at', '>=', $from)
            ->groupByRaw('date(created_at)')
            ->orderBy('day')
            ->pluck('cnt', 'day');

        $completed = DB::table('tickets')
            ->selectRaw('date(completed_at) as day, count(*) as cnt')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $from)
            ->groupByRaw('date(completed_at)')
            ->orderBy('day')
            ->pluck('cnt', 'day');

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $result[] = [
                'date'      => $day,
                'created'   => (int) ($created[$day] ?? 0),
                'completed' => (int) ($completed[$day] ?? 0),
            ];
        }

        return $result;
    }

    private function byDepartment(): array
    {
        return DB::table('tickets')
            ->join('departments', 'departments.id', '=', 'tickets.assigned_department_id')
            ->select('departments.name as label', DB::raw('count(tickets.id) as value'))
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('value')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'value' => (int) $r->value])
            ->toArray();
    }

    private function byCategory(): array
    {
        return DB::table('tickets')
            ->join('categories', 'categories.id', '=', 'tickets.category_id')
            ->select('categories.name as label', DB::raw('count(tickets.id) as value'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('value')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'value' => (int) $r->value])
            ->toArray();
    }

    private function byChannel(): array
    {
        $channelLabels = [
            'web'      => 'Veb sayt',
            'telegram' => 'Telegram',
            'phone'    => 'Telefon',
            'email'    => 'Email',
            'manual'   => 'Qo\'lda kiritildi',
        ];

        return DB::table('tickets')
            ->selectRaw('channel, count(*) as value')
            ->whereNotNull('channel')
            ->groupBy('channel')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($r) => [
                'label' => $channelLabels[$r->channel] ?? ucfirst($r->channel),
                'value' => (int) $r->value,
            ])
            ->toArray();
    }

    private function byPriority(): array
    {
        $labels = [
            TicketPriority::Urgent->value     => 'Shoshilinch',
            TicketPriority::High->value       => 'Yuqori',
            TicketPriority::Medium->value     => "O'rta",
            TicketPriority::Low->value        => 'Past',
            TicketPriority::Unassigned->value => 'Belgilanmagan',
        ];

        return DB::table('tickets')
            ->selectRaw('priority, count(*) as value')
            ->groupBy('priority')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($r) => [
                'label' => $labels[$r->priority] ?? ucfirst($r->priority),
                'value' => (int) $r->value,
            ])
            ->toArray();
    }

    private function byWeekday(): array
    {
        $dayLabels = ['Yakshanba', 'Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba'];

        // SQLite: strftime('%w', ...) returns 0=Sunday...6=Saturday
        $rows = DB::table('tickets')
            ->selectRaw("strftime('%w', created_at) as wd, count(*) as value")
            ->groupByRaw("strftime('%w', created_at)")
            ->orderBy('wd')
            ->get()
            ->keyBy('wd');

        $result = [];
        // Show Mon–Fri first (1-5), then Sat-Sun
        foreach ([1, 2, 3, 4, 5, 6, 0] as $wd) {
            $result[] = [
                'label' => $dayLabels[$wd],
                'value' => (int) ($rows[$wd]->value ?? 0),
            ];
        }

        return $result;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Enums\AvailabilityStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use BackedEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KpiDashboardController extends KpiBaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->userFromRequest($request)) {
            return response()->json(['message' => 'Avtorizatsiya talab qilinadi.'], 401);
        }

        $month = $this->parseMonth($request);

        $base = fn () => Ticket::query()->when(
            $month,
            fn ($q) => $q->whereYear('created_at', $month[0])->whereMonth('created_at', $month[1])
        );

        $total      = $base()->count();
        $waiting    = $base()->whereIn('status', [TicketStatus::New->value, TicketStatus::Assigned->value])->count();
        $inProgress = $base()->whereIn('status', [TicketStatus::InProgress->value, TicketStatus::Returned->value])->count();
        $overdue    = $base()->where('status', TicketStatus::Overdue->value)->count();
        $urgent     = $base()->where('priority', TicketPriority::Urgent->value)->count();

        $completed = $base()
            ->where(fn ($q) => $q->whereIn('status', [TicketStatus::Completed->value, TicketStatus::Closed->value])
                ->orWhereNotNull('completed_at'))
            ->count();

        $onTime = $base()
            ->whereNotNull('completed_at')
            ->where(fn ($q) => $q->whereNull('deadline_at')->orWhereColumn('completed_at', '<=', 'deadline_at'))
            ->count();

        $complaints = TicketStatusHistory::query()
            ->whereIn('to_status', [TicketStatus::Returned->value, TicketStatus::Rejected->value])
            ->when($month, fn ($q) => $q->whereHas('ticket', fn ($tq) => $tq
                ->whereYear('created_at', $month[0])
                ->whereMonth('created_at', $month[1])))
            ->distinct('ticket_id')
            ->count('ticket_id');

        $rating     = $this->averageRating($completed, $onTime);
        $slaPercent = $completed > 0 ? round(($onTime / $completed) * 100, 1) : 0;

        $statusDistribution = $base()
            ->select('status', DB::raw('count(*) as value'))
            ->groupBy('status')
            ->orderByDesc('value')
            ->get()
            ->map(fn (Ticket $t) => [
                'label' => $this->statusLabel($this->enumValue($t->status)),
                'value' => (int) $t->value,
            ])
            ->values();

        $employeeRows = User::query()
            ->role([
                UserRole::Executor->value,
                UserRole::Operator->value,
                UserRole::Manager->value,
                UserRole::Admin->value,
            ])
            ->withCount([
                'assignedTickets as total',
                'assignedTickets as completed' => fn ($q) => $q->when($month, fn ($mq) => $mq
                    ->whereYear('created_at', $month[0])->whereMonth('created_at', $month[1]))
                    ->where(fn ($iq) => $iq
                        ->whereIn('status', [TicketStatus::Completed->value, TicketStatus::Closed->value])
                        ->orWhereNotNull('completed_at')),
                'assignedTickets as failed' => fn ($q) => $q->when($month, fn ($mq) => $mq
                    ->whereYear('created_at', $month[0])->whereMonth('created_at', $month[1]))
                    ->whereNotNull('deadline_at')
                    ->where('deadline_at', '<', now())
                    ->whereNotIn('status', [TicketStatus::Completed->value, TicketStatus::Closed->value, TicketStatus::Rejected->value]),
                'assignedTickets as on_time' => fn ($q) => $q->when($month, fn ($mq) => $mq
                    ->whereYear('created_at', $month[0])->whereMonth('created_at', $month[1]))
                    ->whereNotNull('completed_at')
                    ->where(fn ($iq) => $iq->whereNull('deadline_at')->orWhereColumn('completed_at', '<=', 'deadline_at')),
                'assignedTickets as active_works' => fn ($q) => $q->whereIn('status', [
                    TicketStatus::New->value, TicketStatus::Assigned->value,
                    TicketStatus::InProgress->value, TicketStatus::Returned->value, TicketStatus::Overdue->value,
                ]),
            ])
            ->orderByDesc('completed')
            ->orderByDesc('total')
            ->orderBy('name')
            ->get();

        $employeeKpi = $employeeRows->map(function (User $e) use ($completed): array {
            $c = (int) $e->completed;
            $o = (int) $e->on_time;
            return [
                'id'        => $e->id,
                'name'      => $e->name,
                'jobTitle'  => $e->job_title,
                'online'    => $this->enumValue($e->availability_status) === AvailabilityStatus::Active->value,
                'completed' => $c,
                'failed'    => (int) $e->failed,
                'rating'    => $this->averageRating($c, $o),
                'share'     => $completed > 0 ? round(($c / $completed) * 100, 1) : 0,
                'activeWorks' => (int) $e->active_works,
            ];
        })->values();

        $employees = $employeeRows->map(fn (User $e) => [
            'id'          => $e->id,
            'name'        => $e->name,
            'jobTitle'    => $e->job_title,
            'employeeCode'=> 'ID: '.substr((string) ($e->id * 917263), 0, 7),
            'online'      => $this->enumValue($e->availability_status) === AvailabilityStatus::Active->value,
            'availability'=> $this->availabilityLabel($this->enumValue($e->availability_status)),
            'activeWorks' => (int) $e->active_works,
        ])->values();

        $ticketJournal = $base()
            ->with('assignedExecutor:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function (Ticket $t): array {
                $status = $this->enumValue($t->status);
                return [
                    'dbId'        => $t->id,
                    'id'          => $t->reference ?: "#{$t->id}",
                    'customer'    => $t->requester_name ?: "Noma'lum mijoz",
                    'phone'       => $t->requester_phone ?: '-',
                    'location'    => $t->requester_department ?: '-',
                    'title'       => $t->title ?: 'Murojaat',
                    'description' => $t->description ?: '',
                    'assignee'    => $t->assignedExecutor?->name ?: 'Biriktirilmagan',
                    'priority'    => $this->enumValue($t->priority),
                    'priorityLabel' => $this->priorityLabel($this->enumValue($t->priority)),
                    'status'      => $status,
                    'statusLabel' => $this->statusLabel($status),
                    'date'        => $t->created_at?->format('Y-m-d H:i:s') ?: '',
                ];
            })->values();

        return response()->json([
            'updatedAt' => now()->toISOString(),
            'month'     => $month ? sprintf('%04d-%02d', $month[0], $month[1]) : now()->format('Y-m'),
            'overview'  => [
                'total'      => $total,
                'waiting'    => $waiting,
                'inProgress' => $inProgress,
                'completed'  => $completed,
                'overdue'    => $overdue,
                'complaints' => $complaints,
                'urgent'     => $urgent,
                'slaPercent' => $slaPercent,
                'rating'     => round($rating, 1),
            ],
            'cards' => [
                ['key' => 'total',      'label' => 'Barcha murojaatlar', 'value' => $total,      'tone' => 'primary'],
                ['key' => 'waiting',    'label' => 'Kutilmoqda',         'value' => $waiting,    'tone' => 'soft'],
                ['key' => 'in_progress','label' => 'Jarayonda',          'value' => $inProgress, 'tone' => 'secondary'],
                ['key' => 'completed',  'label' => 'Bajarildi',          'value' => $completed,  'tone' => 'accent'],
                ['key' => 'overdue',    'label' => 'Muddati o\'tgan',    'value' => $overdue,    'tone' => 'deep'],
            ],
            'monthlyCards' => [
                ['key' => 'completed',  'label' => 'Yakunlangan ishlar', 'value' => $completed,                    'tone' => 'accent'],
                ['key' => 'rating',     'label' => "O'rtacha reyting",   'value' => number_format($rating, 1),     'tone' => 'primary'],
                ['key' => 'sla',        'label' => 'SLA bajarish',       'value' => $slaPercent.'%',               'tone' => 'soft'],
                ['key' => 'complaints', 'label' => 'Shikoyatlar',        'value' => $complaints,                   'tone' => 'deep'],
            ],
            'statusDistribution' => $statusDistribution,
            'employeeResults' => $employeeKpi
                ->map(fn ($e) => ['label' => $e['name'], 'value' => $e['completed']])
                ->sortByDesc('value')->take(10)->values(),
            'monthlyIndicators' => [
                ['label' => 'Yakunlangan', 'value' => $completed],
                ['label' => 'Qaytarilgan', 'value' => $complaints],
                ['label' => 'Reyting x10', 'value' => (int) round($rating * 10)],
            ],
            'ticketJournal' => $ticketJournal,
            'employeeKpi'   => $employeeKpi,
            'employees'     => $employees,
            'employeeStatusChart' => $employees
                ->map(fn ($e) => ['label' => $e['name'], 'value' => $e['activeWorks']])
                ->values(),
        ]);
    }

    private function averageRating(int $completed, int $onTime): float
    {
        return $completed === 0 ? 0 : round(($onTime / $completed) * 5, 1);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            TicketStatus::New->value        => 'Yangi',
            TicketStatus::Assigned->value   => 'Taqsimlandi',
            TicketStatus::InProgress->value => 'Jarayonda',
            TicketStatus::Returned->value   => 'Qaytarildi',
            TicketStatus::Overdue->value    => 'Kechikkan',
            TicketStatus::Completed->value  => 'Bajarildi',
            TicketStatus::Closed->value     => 'Yopildi',
            TicketStatus::Rejected->value   => 'Rad etildi',
            default                         => $status,
        };
    }

    private function priorityLabel(string $priority): string
    {
        return match ($priority) {
            TicketPriority::Low->value        => 'Past',
            TicketPriority::Medium->value     => "O'rta",
            TicketPriority::High->value       => 'Yuqori',
            TicketPriority::Urgent->value     => 'Shoshilinch',
            TicketPriority::Unassigned->value => 'Belgilanmagan',
            default                           => $priority,
        };
    }

    private function availabilityLabel(string $status): string
    {
        return match ($status) {
            AvailabilityStatus::Active->value   => 'Faol',
            AvailabilityStatus::Busy->value     => 'Band',
            AvailabilityStatus::Offline->value  => 'Ishda emas',
            AvailabilityStatus::Vacation->value => "Ta'tilda",
            default                             => $status,
        };
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof BackedEnum ? (string) $value->value : (string) $value;
    }
}

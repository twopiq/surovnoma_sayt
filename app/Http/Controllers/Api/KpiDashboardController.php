<?php

namespace App\Http\Controllers\Api;

use App\Enums\AvailabilityStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use BackedEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Throwable;

class KpiDashboardController extends Controller
{
    private const COOKIE_NAME = 'kpi_auth';

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->userFromRequest($request)) {
            return response()->json([
                'message' => 'Avtorizatsiya talab qilinadi.',
            ], 401);
        }

        $total = Ticket::query()->count();
        $waiting = $this->countByStatuses([TicketStatus::New, TicketStatus::Assigned]);
        $inProgress = $this->countByStatuses([TicketStatus::InProgress, TicketStatus::Returned]);
        $completed = Ticket::query()
            ->where(function ($query): void {
                $query
                    ->whereIn('status', [TicketStatus::Completed->value, TicketStatus::Closed->value])
                    ->orWhereNotNull('completed_at');
            })
            ->count();
        $onTime = Ticket::query()
            ->whereNotNull('completed_at')
            ->where(function ($query): void {
                $query
                    ->whereNull('deadline_at')
                    ->orWhereColumn('completed_at', '<=', 'deadline_at');
            })
            ->count();
        $complaints = TicketStatusHistory::query()
            ->whereIn('to_status', [TicketStatus::Returned->value, TicketStatus::Rejected->value])
            ->distinct('ticket_id')
            ->count('ticket_id');
        $urgent = Ticket::query()
            ->where('priority', TicketPriority::Urgent->value)
            ->count();
        $failed = Ticket::query()
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<', now())
            ->whereNotIn('status', [
                TicketStatus::Completed->value,
                TicketStatus::Closed->value,
                TicketStatus::Rejected->value,
            ])
            ->count();
        $rating = $this->averageRating($completed, $onTime);

        $statusDistribution = Ticket::query()
            ->select('status', DB::raw('count(*) as value'))
            ->groupBy('status')
            ->orderByDesc('value')
            ->get()
            ->map(fn (Ticket $ticket): array => [
                'label' => $this->statusLabel($this->enumValue($ticket->status)),
                'value' => (int) $ticket->value,
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
                'assignedTickets as completed' => function ($query): void {
                    $query->where(function ($innerQuery): void {
                        $innerQuery
                            ->whereIn('status', [TicketStatus::Completed->value, TicketStatus::Closed->value])
                            ->orWhereNotNull('completed_at');
                    });
                },
                'assignedTickets as failed' => function ($query): void {
                    $query
                        ->whereNotNull('deadline_at')
                        ->where('deadline_at', '<', now())
                        ->whereNotIn('status', [
                            TicketStatus::Completed->value,
                            TicketStatus::Closed->value,
                            TicketStatus::Rejected->value,
                        ]);
                },
                'assignedTickets as on_time' => function ($query): void {
                    $query
                        ->whereNotNull('completed_at')
                        ->where(function ($innerQuery): void {
                            $innerQuery
                                ->whereNull('deadline_at')
                                ->orWhereColumn('completed_at', '<=', 'deadline_at');
                        });
                },
                'assignedTickets as active_works' => function ($query): void {
                    $query->whereIn('status', [
                        TicketStatus::New->value,
                        TicketStatus::Assigned->value,
                        TicketStatus::InProgress->value,
                        TicketStatus::Returned->value,
                    ]);
                },
            ])
            ->orderByDesc('completed')
            ->orderByDesc('total')
            ->orderBy('name')
            ->get();

        $employeeKpi = $employeeRows
            ->map(function (User $employee) use ($completed): array {
                $completedCount = (int) $employee->completed;
                $onTimeCount = (int) $employee->on_time;

                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'online' => $this->isOnline($employee),
                    'completed' => $completedCount,
                    'failed' => (int) $employee->failed,
                    'rating' => $this->averageRating($completedCount, $onTimeCount),
                    'share' => $this->sharePercent($completedCount, $completed),
                ];
            })
            ->values();

        $employees = $employeeRows
            ->map(fn (User $employee): array => [
                'id' => $employee->id,
                'name' => $employee->name,
                'employeeCode' => $this->employeeCode($employee->id),
                'online' => $this->isOnline($employee),
                'activeWorks' => (int) $employee->active_works,
            ])
            ->values();

        $ticketJournal = Ticket::query()
            ->with('assignedExecutor:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function (Ticket $ticket): array {
                $status = $this->enumValue($ticket->status);

                return [
                    'id' => $ticket->reference ?: "#{$ticket->id}",
                    'customer' => $ticket->requester_name ?: "Noma'lum mijoz",
                    'phone' => $ticket->requester_phone ?: '-',
                    'location' => $ticket->requester_department ?: '-',
                    'title' => $ticket->title ?: 'Murojaat',
                    'description' => $ticket->description ?: '',
                    'assignee' => $ticket->assignedExecutor?->name ?: 'Biriktirilmagan',
                    'status' => $status,
                    'statusLabel' => $this->statusLabel($status),
                    'date' => $ticket->created_at?->format('Y-m-d H:i:s') ?: '',
                ];
            })
            ->values();

        $monthlyIndicators = [
            ['label' => 'Yakunlangan', 'value' => $completed],
            ['label' => 'Qaytarilgan', 'value' => $complaints],
            ['label' => 'Reyting x10', 'value' => (int) round($rating * 10)],
        ];

        return response()->json([
            'updatedAt' => now()->toISOString(),
            'month' => now()->format('Y-m'),
            'overview' => [
                'total' => $total,
                'waiting' => $waiting,
                'inProgress' => $inProgress,
                'completed' => $completed,
                'complaints' => $complaints,
                'urgent' => $urgent,
            ],
            'cards' => [
                ['key' => 'total', 'label' => 'Barcha murojaatlar', 'value' => $total, 'tone' => 'primary'],
                ['key' => 'waiting', 'label' => 'Kutilmoqda', 'value' => $waiting, 'tone' => 'soft'],
                ['key' => 'in_progress', 'label' => 'Jarayonda', 'value' => $inProgress, 'tone' => 'secondary'],
                ['key' => 'completed', 'label' => 'Bajarildi', 'value' => $completed, 'tone' => 'accent'],
                ['key' => 'complaints', 'label' => 'Qaytarilgan', 'value' => $complaints, 'tone' => 'deep'],
            ],
            'statusDistribution' => $statusDistribution,
            'monthlyCards' => [
                ['key' => 'completed', 'label' => 'Yakunlangan ishlar', 'value' => $completed, 'tone' => 'accent'],
                ['key' => 'rating', 'label' => "O'rtacha reyting", 'value' => number_format($rating, 1), 'tone' => 'primary'],
                ['key' => 'complaints', 'label' => 'Shikoyatlar', 'value' => $complaints, 'tone' => 'deep'],
            ],
            'employeeResults' => $employeeKpi
                ->map(fn (array $employee): array => ['label' => $employee['name'], 'value' => $employee['completed']])
                ->sortByDesc('value')
                ->take(10)
                ->values(),
            'monthlyIndicators' => $monthlyIndicators,
            'ticketJournal' => $ticketJournal,
            'employeeKpi' => $employeeKpi,
            'employees' => $employees,
            'employeeStatusChart' => $employees
                ->map(fn (array $employee): array => ['label' => $employee['name'], 'value' => $employee['activeWorks']])
                ->values(),
        ]);
    }

    private function userFromRequest(Request $request): ?User
    {
        $token = $this->tokenFromRequest($request);

        if (! $token) {
            return null;
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($payload) || ($payload['expires_at'] ?? 0) < now()->timestamp) {
            return null;
        }

        $user = User::query()->find($payload['user_id'] ?? null);

        return $user && $user->isApproved() && $user->hasRole(UserRole::Admin->value)
            ? $user
            : null;
    }

    private function countByStatuses(array $statuses): int
    {
        return Ticket::query()
            ->whereIn('status', array_map(fn (TicketStatus $status): string => $status->value, $statuses))
            ->count();
    }

    private function tokenFromRequest(Request $request): ?string
    {
        $bearerToken = $request->bearerToken();

        return $bearerToken ?: $request->cookie(self::COOKIE_NAME);
    }

    private function averageRating(int $completed, int $onTime): float
    {
        if ($completed === 0) {
            return 0;
        }

        return round(($onTime / $completed) * 5, 1);
    }

    private function sharePercent(int $value, int $total): float
    {
        if ($total === 0) {
            return 0;
        }

        return round(($value / $total) * 100, 1);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            TicketStatus::New->value => 'Kutilmoqda',
            TicketStatus::Assigned->value,
            TicketStatus::InProgress->value => 'Jarayonda',
            TicketStatus::Returned->value => 'Qaytarilgan',
            TicketStatus::Completed->value,
            TicketStatus::Closed->value => 'Bajarildi',
            TicketStatus::Rejected->value => 'Rad etildi',
            default => $status,
        };
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof BackedEnum ? (string) $value->value : (string) $value;
    }

    private function isOnline(User $employee): bool
    {
        return $this->enumValue($employee->availability_status) === AvailabilityStatus::Active->value;
    }

    private function employeeCode(int $id): string
    {
        return 'ID: '.substr((string) ($id * 917263), 0, 7);
    }
}

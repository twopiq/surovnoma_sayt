<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;

class AppHomeController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        $user = auth()->user();
        $homeData = [];

        if ($user->hasSystemRole(UserRole::Executor)) {
            $homeData = $this->executorHomeData($user->id);
        }

        if (
            $user->hasSystemRole(UserRole::Requester)
            && ! $user->hasAnyRole([
                UserRole::Admin->value,
                UserRole::Manager->value,
                UserRole::Operator->value,
                UserRole::Executor->value,
            ])
        ) {
            return redirect()->route('tickets.index');
        }

        return view('app.home', [
            'homeData' => $homeData,
        ]);
    }

    protected function executorHomeData(int $executorId): array
    {
        $activeStatuses = [
            TicketStatus::Assigned->value,
            TicketStatus::InProgress->value,
            TicketStatus::Returned->value,
        ];

        $activeQuery = Ticket::query()
            ->with(['assignedExecutor', 'requester', 'category', 'slaProfile'])
            ->where('assigned_executor_id', $executorId)
            ->whereIn('status', $activeStatuses);

        $activeTickets = (clone $activeQuery)
            ->orderByRaw('deadline_at is null')
            ->orderBy('deadline_at')
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $dueTodayCount = (clone $activeQuery)
            ->whereDate('deadline_at', today())
            ->count();

        $todayTasks = (clone $activeQuery)
            ->where(function ($query): void {
                $query
                    ->whereDate('deadline_at', today())
                    ->orWhereDate('created_at', today());
            })
            ->orderByRaw('deadline_at is null')
            ->orderBy('deadline_at')
            ->limit(5)
            ->get();

        $overdueCount = Ticket::query()
            ->whereNull('assigned_executor_id')
            ->where('status', TicketStatus::Overdue->value)
            ->count();

        $overdueTickets = Ticket::query()
            ->with(['assignedExecutor', 'requester', 'category', 'slaProfile'])
            ->whereNull('assigned_executor_id')
            ->where('status', TicketStatus::Overdue->value)
            ->orderByRaw('deadline_at is null')
            ->orderBy('deadline_at')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $availableCount = Ticket::query()
            ->whereNull('assigned_executor_id')
            ->whereIn('status', [TicketStatus::New->value, TicketStatus::Assigned->value, TicketStatus::Returned->value])
            ->count();

        $availableTickets = Ticket::query()
            ->with(['assignedExecutor', 'requester', 'category', 'slaProfile'])
            ->whereNull('assigned_executor_id')
            ->whereIn('status', [TicketStatus::New->value, TicketStatus::Assigned->value, TicketStatus::Returned->value])
            ->orderByRaw('deadline_at is null')
            ->orderBy('deadline_at')
            ->latest('created_at')
            ->limit(4)
            ->get();

        return [
            'workloadSummary' => auth()->user()->executorWorkloadSummary(),
            'activeTickets' => $activeTickets,
            'todayTasks' => $todayTasks,
            'overdueTickets' => $overdueTickets,
            'availableTickets' => $availableTickets,
            'stats' => [
                'active_count' => (clone $activeQuery)->count(),
                'in_progress_count' => (clone $activeQuery)->where('status', TicketStatus::InProgress->value)->count(),
                'returned_count' => (clone $activeQuery)->where('status', TicketStatus::Returned->value)->count(),
                'due_today_count' => $dueTodayCount,
                'overdue_count' => $overdueCount,
                'available_count' => $availableCount,
            ],
        ];
    }
}

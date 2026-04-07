<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\View\View;

class ManagerDashboardController extends Controller
{
    public function __invoke(): View
    {
        $tickets = Ticket::query();

        return view('manager.dashboard', [
            'summary' => [
                'jami' => $tickets->count(),
                'jarayonda' => Ticket::query()->whereIn('status', ['assigned', 'in_progress', 'returned'])->count(),
                'kechikkan' => Ticket::query()->whereNotNull('deadline_at')->where('deadline_at', '<', now())->whereNotIn('status', ['closed', 'rejected'])->count(),
                'yopilgan' => Ticket::query()->where('status', 'closed')->count(),
                'foydalanuvchilar' => User::count(),
            ],
            'byPriority' => Ticket::query()
                ->selectRaw('priority, count(*) as aggregate')
                ->groupBy('priority')
                ->pluck('aggregate', 'priority'),
        ]);
    }
}

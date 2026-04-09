<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('welcome', [
            'stats' => [
                'tickets' => Ticket::count(),
                'staff' => User::count(),
                'open' => Ticket::query()->whereNotIn('status', ['closed', 'rejected'])->count(),
            ],
        ]);
    }

    public function pendingApproval(): View
    {
        $user = auth()->user();

        return view('auth.pending-approval', [
            'email' => $user?->email ?? session('pending_approval_email'),
            'isRejected' => $user
                ? (! $user->is_active && $user->approved_at === null)
                : (bool) session('pending_approval_rejected', false),
        ]);
    }
}

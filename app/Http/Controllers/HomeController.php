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
        return view('auth.pending-approval', [
            'email' => auth()->user()?->email ?? session('pending_approval_email'),
        ]);
    }
}

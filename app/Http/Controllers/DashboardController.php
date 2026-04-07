<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->hasSystemRole(UserRole::Admin)) {
            return redirect()->route('admin.dispatch.index');
        }

        if ($user->hasSystemRole(UserRole::Executor)) {
            return redirect()->route('executor.tickets.index');
        }

        if ($user->hasSystemRole(UserRole::Manager)) {
            return redirect()->route('manager.dashboard');
        }

        if ($user->hasSystemRole(UserRole::Operator)) {
            return redirect()->route('operator.tickets.index');
        }

        return redirect()->route('tickets.index');
    }
}

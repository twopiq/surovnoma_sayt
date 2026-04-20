<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        if (! $request->user()->isApproved()) {
            $pendingUser = $request->user();

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put('pending_approval_email', $pendingUser->email);
            $request->session()->put('pending_approval_rejected', ! $pendingUser->is_active && $pendingUser->approved_at === null);

            return redirect()->route('pending-approval');
        }

        return redirect()->route('app.home');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $redirect = match ($request->input('redirect')) {
            'login' => route('login'),
            'home' => route('home'),
            default => '/',
        };

        return redirect($redirect);
    }
}

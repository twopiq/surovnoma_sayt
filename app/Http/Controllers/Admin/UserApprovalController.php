<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserApprovalController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'pendingUsers' => User::query()->whereNull('approved_at')->where('is_active', true)->latest()->get(),
            'rejectedUsers' => User::query()->whereNull('approved_at')->where('is_active', false)->latest()->limit(20)->get(),
            'approvedUsers' => User::query()->whereNotNull('approved_at')->latest()->limit(20)->get(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'role' => ['nullable', Rule::in(UserRole::values())],
        ]);

        if ($data['decision'] === 'approve') {
            $request->validate([
                'role' => ['required', Rule::in(UserRole::values())],
            ]);

            Role::findOrCreate($data['role'], 'web');
            $user->syncRoles([$data['role']]);
            $user->forceFill([
                'approved_at' => now(),
                'is_active' => true,
            ])->save();

            return back()->with('status', 'Foydalanuvchi tasdiqlandi.');
        }

        $user->forceFill([
            'approved_at' => null,
            'is_active' => false,
        ])->save();

        return back()->with('status', "Ro'yxatdan o'tish so'rovi rad etildi.");
    }
}

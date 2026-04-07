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
            'pendingUsers' => User::query()->whereNull('approved_at')->latest()->get(),
            'approvedUsers' => User::query()->whereNotNull('approved_at')->latest()->limit(20)->get(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(UserRole::values())],
            'approved' => ['required', 'boolean'],
        ]);

        Role::findOrCreate($data['role'], 'web');
        $user->syncRoles([$data['role']]);
        $user->forceFill([
            'approved_at' => $data['approved'] ? now() : null,
            'is_active' => (bool) $data['approved'],
        ])->save();

        return back()->with('status', 'Foydalanuvchi holati yangilandi.');
    }
}

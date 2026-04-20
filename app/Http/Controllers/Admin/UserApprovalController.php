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
            'latestUsers' => User::query()->with(['department', 'roles'])->latest()->limit(8)->get(),
            'pendingUsers' => User::query()->with(['department', 'roles'])->whereNull('approved_at')->where('is_active', true)->latest()->get(),
            'rejectedUsers' => User::query()->with(['department', 'roles'])->whereNull('approved_at')->where('is_active', false)->latest()->limit(20)->get(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function list(Request $request): View
    {
        $perPage = in_array((int) $request->input('per_page', 10), [10, 25, 50], true)
            ? (int) $request->input('per_page', 10)
            : 10;
        $search = trim((string) $request->input('search', ''));

        $users = User::query()
            ->with(['department', 'roles'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('login', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('job_title', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.users.list', [
            'users' => $users,
            'perPage' => $perPage,
            'search' => $search,
        ]);
    }

    public function profile(Request $request): View
    {
        $selectedUser = User::query()
            ->with(['department', 'roles'])
            ->find($request->integer('user')) ?? auth()->user()->load(['department', 'roles']);

        return view('admin.users.profile', [
            'selectedUser' => $selectedUser,
            'users' => User::query()->with(['roles'])->latest()->limit(12)->get(),
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

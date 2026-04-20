<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Support\TableExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
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

        $users = $this->usersListQuery($request)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.users.list', [
            'users' => $users,
            'perPage' => $perPage,
            'filters' => $this->userListFilters($request),
            'roles' => UserRole::cases(),
            'statuses' => $this->userStatusOptions(),
        ]);
    }

    public function recent(): View
    {
        return view('admin.users.recent', [
            'users' => User::query()
                ->with(['department', 'roles'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^\S+(?:\s+\S+)+$/'],
            'phone' => ['nullable', 'regex:/^\+998 \d{2} \d{3} \d{2} \d{2}$/'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', Rule::in(UserRole::values())],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'name.regex' => "F.I.Sh. kamida ism va familiyadan iborat bo'lishi kerak.",
            'phone.regex' => "Telefon raqami +998 99 999 99 99 ko'rinishida bo'lishi kerak.",
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'email' => $data['email'],
            'approved_at' => now(),
            'is_active' => true,
            'password' => Hash::make($data['password']),
        ]);

        Role::findOrCreate($data['role'], 'web');
        $user->assignRole($data['role']);

        return redirect()->route('admin.users.list')->with('status', 'Foydalanuvchi yaratildi.');
    }

    public function export(Request $request)
    {
        $query = $this->usersListQuery($request)->latest();
        $format = (string) $request->query('format', 'excel');
        $headings = ['F.I.Sh.', 'Email', 'Login', 'Holat', 'Rol', 'Telefon', 'Lavozim', "Bo'lim", "Ro'yxatdan o'tgan", 'Tasdiqlangan'];
        $rows = (function () use ($query): \Generator {
            foreach ($query->cursor() as $user) {
                yield [
                    $user->name,
                    $user->email,
                    $user->login,
                    $this->userStatusLabel($user),
                    $user->display_role,
                    $user->phone,
                    $user->job_title,
                    $user->department?->name,
                    $user->created_at,
                    $user->approved_at,
                ];
            }
        })();

        return TableExport::download($format, 'foydalanuvchilar', 'Foydalanuvchilar ro\'yxati', $headings, $rows, [
            'Eksport qilingan vaqt' => now(),
            'Format' => strtolower($format) === 'csv' ? 'CSV' : 'Excel',
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

    protected function usersListQuery(Request $request)
    {
        $filters = $this->userListFilters($request);

        return User::query()
            ->with(['department', 'roles'])
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('login', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('job_title', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] !== '', fn ($query) => $query->role($filters['role']))
            ->when($filters['date'] !== '', fn ($query) => $query->whereDate('created_at', $filters['date']))
            ->when($filters['status'] !== '', function ($query) use ($filters): void {
                match ($filters['status']) {
                    'active' => $query->whereNotNull('approved_at')->where('is_active', true),
                    'pending' => $query->whereNull('approved_at')->where('is_active', true),
                    'inactive' => $query->where('is_active', false),
                    default => null,
                };
            });
    }

    protected function userListFilters(Request $request): array
    {
        $role = (string) $request->input('role', '');
        $status = (string) $request->input('status', '');
        $date = (string) $request->input('date', '');

        return [
            'search' => trim((string) $request->input('search', '')),
            'role' => in_array($role, UserRole::values(), true) ? $role : '',
            'status' => array_key_exists($status, $this->userStatusOptions()) ? $status : '',
            'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '',
        ];
    }

    protected function userStatusOptions(): array
    {
        return [
            'active' => 'Active',
            'pending' => 'Pending',
            'inactive' => 'Inactive',
        ];
    }

    protected function userStatusLabel(User $user): string
    {
        if ($user->approved_at && $user->is_active) {
            return 'Active';
        }

        if (! $user->is_active) {
            return 'Inactive';
        }

        return 'Pending';
    }
}

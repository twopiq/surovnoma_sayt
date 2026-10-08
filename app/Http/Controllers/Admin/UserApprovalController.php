<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Support\TableExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserApprovalController extends Controller
{
    /** Ro'yxat segmentlari (dizayn: UsersA). "Tasdiq kutayotganlar" alohida sahifa — UsersBPending. */
    public const SEGMENTS = [
        'all' => 'Hammasi',
        'new' => 'Yangi (7 kun)',
        'inactive' => 'Nofaol',
    ];

    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'rejected' ? 'rejected' : 'pending';

        return view('admin.users.index', [
            'tab' => $tab,
            'users' => User::query()
                ->with(['department', 'roles'])
                ->whereNull('approved_at')
                ->where('is_active', $tab === 'pending')
                ->latest()
                ->get(),
            'counts' => $this->counts(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function list(Request $request): View
    {
        $users = $this->usersListQuery($request)
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $selectedUser = $request->filled('user')
            ? User::query()->with(['department', 'roles'])->find($request->integer('user'))
            : null;

        return view('admin.users.list', [
            'users' => $users,
            'filters' => $this->userListFilters($request),
            'segments' => self::SEGMENTS,
            'counts' => $this->counts(),
            'roles' => UserRole::cases(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedUser' => $selectedUser,
            'lastActivity' => $this->lastActivity($users->getCollection()->pluck('id')->push($selectedUser?->id)->filter()->all()),
        ]);
    }

    public function recent(): RedirectResponse
    {
        return redirect()->route('admin.users.list', ['segment' => 'new']);
    }

    public function create(): View
    {
        return view('admin.users.profile', [
            'selectedUser' => null,
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'roles' => UserRole::cases(),
            'statuses' => $this->userStatusOptions(),
            'lastActivity' => [],
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
            'login' => \App\Support\LoginSuggester::rules(),
            'role' => ['required', Rule::in(UserRole::values())],
            'can_access_app_dashboard' => ['nullable', 'boolean'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [...$this->messages(), ...\App\Support\LoginSuggester::messages()]);

        $user = User::query()->create([
            'name' => $data['name'],
            'login' => $data['login'] ?? null,
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
        $user->forceFill([
            'can_access_app_dashboard' => $data['role'] === UserRole::Manager->value && (bool) ($data['can_access_app_dashboard'] ?? false),
        ])->save();

        return redirect()->route('admin.users.list', ['user' => $user->id])->with('status', 'Foydalanuvchi yaratildi.');
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

    public function profile(Request $request): View|RedirectResponse
    {
        $selectedUser = User::query()->with(['department', 'roles'])->find($request->integer('user'));

        if (! $selectedUser) {
            return redirect()->route('admin.users.list');
        }

        return view('admin.users.profile', [
            'selectedUser' => $selectedUser,
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'roles' => UserRole::cases(),
            'statuses' => $this->userStatusOptions(),
            'lastActivity' => $this->lastActivity([$selectedUser->id]),
        ]);
    }

    public function updateProfile(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^\S+(?:\s+\S+)+$/'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => ['nullable', 'regex:/^\+998 \d{2} \d{3} \d{2} \d{2}$/'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'role' => ['required', Rule::in(UserRole::values())],
            'status' => ['required', Rule::in(array_keys($this->userStatusOptions()))],
            'can_access_app_dashboard' => ['nullable', 'boolean'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'return' => ['nullable', Rule::in(['list'])],
        ], $this->messages());

        Role::findOrCreate($data['role'], 'web');

        $statusValues = match ($data['status']) {
            'active' => [
                'approved_at' => $user->approved_at ?? now(),
                'is_active' => true,
            ],
            'pending' => [
                'approved_at' => null,
                'is_active' => true,
            ],
            default => [
                'approved_at' => null,
                'is_active' => false,
            ],
        };

        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'can_access_app_dashboard' => $data['role'] === UserRole::Manager->value
                ? (bool) ($data['can_access_app_dashboard'] ?? false)
                : false,
            ...$statusValues,
            ...(filled($data['password'] ?? null) ? ['password' => Hash::make($data['password'])] : []),
        ])->save();

        $user->syncRoles([$data['role']]);

        $redirect = ($data['return'] ?? null) === 'list'
            ? redirect()->route('admin.users.list', [
                ...array_filter(\Illuminate\Support\Arr::only((array) $request->input('keep', []), ['segment', 'search', 'role', 'department_id', 'page']), 'filled'),
                'user' => $user->id,
            ])
            : redirect()->route('admin.users.profile', ['user' => $user->id]);

        return $redirect->with('status', 'Foydalanuvchi maʼlumotlari yangilandi.');
    }

    public function sendPasswordReset(User $user): RedirectResponse
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        return back()->with('status', $status === Password::RESET_LINK_SENT
            ? "Parolni tiklash havolasi {$user->email} manziliga yuborildi."
            : __($status));
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

    public function updateDashboardAccess(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->hasSystemRole(UserRole::Manager), 404);

        $data = $request->validate([
            'can_access_app_dashboard' => ['nullable', 'boolean'],
        ]);

        $user->forceFill([
            'can_access_app_dashboard' => (bool) ($data['can_access_app_dashboard'] ?? false),
        ])->save();

        return back()->with('status', 'Rahbar uchun dashboard ruxsati yangilandi.');
    }

    protected function counts(): array
    {
        return [
            'all' => User::query()->count(),
            'pending' => User::query()->whereNull('approved_at')->where('is_active', true)->count(),
            'rejected' => User::query()->whereNull('approved_at')->where('is_active', false)->count(),
            'new' => User::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'inactive' => User::query()->where('is_active', false)->count(),
        ];
    }

    /** @return array<int, Carbon> foydalanuvchi id => so'nggi faollik (database sessiyalaridan) */
    protected function lastActivity(array $userIds): array
    {
        if ($userIds === [] || ! Schema::hasTable('sessions')) {
            return [];
        }

        return DB::table('sessions')
            ->whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, max(last_activity) as last_activity')
            ->pluck('last_activity', 'user_id')
            ->map(fn ($timestamp) => Carbon::createFromTimestamp((int) $timestamp))
            ->all();
    }

    protected function usersListQuery(Request $request): Builder
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
            ->when($filters['department_id'], fn ($query, int $id) => $query->where('department_id', $id))
            ->when($filters['segment'] === 'new', fn ($query) => $query->where('created_at', '>=', now()->subDays(7)))
            ->when($filters['segment'] === 'inactive', fn ($query) => $query->where('is_active', false));
    }

    protected function userListFilters(Request $request): array
    {
        $role = (string) $request->input('role', '');
        $segment = (string) $request->input('segment', 'all');

        return [
            'search' => trim((string) $request->input('search', '')),
            'role' => in_array($role, UserRole::values(), true) ? $role : '',
            'department_id' => $request->integer('department_id') ?: null,
            'segment' => array_key_exists($segment, self::SEGMENTS) ? $segment : 'all',
        ];
    }

    protected function userStatusOptions(): array
    {
        return [
            'active' => 'Faol',
            'pending' => 'Kutilmoqda',
            'inactive' => 'Nofaol',
        ];
    }

    protected function userStatusLabel(User $user): string
    {
        if ($user->approved_at && $user->is_active) {
            return 'Faol';
        }

        if (! $user->is_active) {
            return 'Nofaol';
        }

        return 'Kutilmoqda';
    }

    protected function messages(): array
    {
        return [
            'name.regex' => "F.I.Sh. kamida ism va familiyadan iborat bo'lishi kerak.",
            'phone.regex' => "Telefon raqami +998 99 999 99 99 ko'rinishida bo'lishi kerak.",
        ];
    }
}

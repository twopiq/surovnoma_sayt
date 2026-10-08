@php
    $query = request()->except(['user', 'page']);
    $statusOf = fn ($u) => match (true) {
        $u->approved_at && $u->is_active => ['Faol', 'completed'],
        ! $u->is_active => ['Nofaol', 'closed'],
        default => ['Kutilmoqda', 'assigned'],
    };
    $initials = fn (string $name) => collect(preg_split('/\s+/u', trim($name)))->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('');
    $isSelf = $selectedUser && $selectedUser->is(auth()->user());
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="pg-crumb">Odamlar</div>
                <h2>Foydalanuvchilar</h2>
                <p class="pg-sub">Hisoblar, rollar va tasdiqlash — bir joyda.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.users.export', array_merge($query, ['format' => 'excel'])) }}" class="btn btn-secondary">Eksport</a>
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Foydalanuvchi qo'shish</a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-none px-4 pt-6 sm:px-6 lg:px-8">
        <nav class="ui-tabs" aria-label="Foydalanuvchilar bo'limlari">
            <a href="{{ route('admin.users.list', \Illuminate\Support\Arr::except($query, ['segment'])) }}" @if ($filters['segment'] === 'all') aria-current="page" @endif>Hammasi <span class="ui-count">{{ $counts['all'] }}</span></a>
            <a href="{{ route('admin.users.index') }}">Tasdiq kutayotganlar <span @class(['ui-count', 'ui-count--alert' => $counts['pending'] > 0])>{{ $counts['pending'] }}</span></a>
            <a href="{{ route('admin.users.list', array_merge($query, ['segment' => 'new'])) }}" @if ($filters['segment'] === 'new') aria-current="page" @endif>Yangi (7 kun) <span class="ui-count">{{ $counts['new'] }}</span></a>
            <a href="{{ route('admin.users.list', array_merge($query, ['segment' => 'inactive'])) }}" @if ($filters['segment'] === 'inactive') aria-current="page" @endif>Nofaol <span class="ui-count">{{ $counts['inactive'] }}</span></a>
        </nav>

        <form method="GET" action="{{ route('admin.users.list') }}" class="mb-3 flex flex-wrap items-center gap-2" data-auto-filter>
            @if ($filters['segment'] !== 'all')
                <input type="hidden" name="segment" value="{{ $filters['segment'] }}">
            @endif
            <label class="relative block w-full sm:w-[280px]">
                <span class="sr-only">Qidirish</span>
                <svg class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><circle cx="9" cy="9" r="5.5"/><path d="m13.5 13.5 3 3"/></svg>
                <input name="search" value="{{ $filters['search'] }}" placeholder="Ism, email yoki login" class="ui-input pl-8">
            </label>
            <select name="role" aria-label="Rol" class="py-1.5 pr-8 text-[13px]">
                <option value="">Rol: Barchasi</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected($filters['role'] === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
            <select name="department_id" aria-label="Bo'lim" class="py-1.5 pr-8 text-[13px]">
                <option value="">Bo'lim: Barchasi</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected($filters['department_id'] === $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
            @if ($filters['search'] !== '' || $filters['role'] !== '' || $filters['department_id'])
                <a href="{{ route('admin.users.list', array_filter(['segment' => $filters['segment'] !== 'all' ? $filters['segment'] : null])) }}" class="text-[13px] text-muted hover:text-ink">Filtrlarni tozalash</a>
            @endif
        </form>

        <div class="ui-split">
            <div class="ui-card overflow-x-auto px-3 pb-3 pt-2">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>Foydalanuvchi</th>
                            <th>Rol</th>
                            <th class="hidden md:table-cell">Bo'lim</th>
                            <th>Holat</th>
                            <th class="hidden xl:table-cell">So'nggi faollik</th>
                            <th class="w-px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            @php([$statusLabel, $statusTone] = $statusOf($user))
                            <tr @class(['is-selected' => $selectedUser?->is($user)])>
                                <td>
                                    <a href="{{ route('admin.users.list', array_merge(request()->query(), ['user' => $user->id])) }}" class="flex items-center gap-2.5">
                                        <span class="ui-avatar">{{ $initials($user->name) }}</span>
                                        <span class="min-w-0">
                                            <b class="block truncate text-[14px] font-medium text-ink">{{ $user->name }}</b>
                                            <span class="block truncate text-xs text-muted">{{ $user->email }} · {{ $user->login }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td><span class="ui-role">{{ $user->display_role }}</span></td>
                                <td class="hidden text-muted md:table-cell">{{ $user->department?->name ?? '—' }}</td>
                                <td><span class="status-badge status--{{ $statusTone }}">{{ $statusLabel }}</span></td>
                                <td class="ui-mono hidden text-muted xl:table-cell">{{ isset($lastActivity[$user->id]) ? $lastActivity[$user->id]->format('d.m.Y H:i') : '—' }}</td>
                                <td class="text-right">
                                    @if ($statusTone === 'assigned')
                                        <a href="{{ route('admin.users.index') }}" class="btn btn-primary !px-2.5 !py-1">Ko'rib chiqish</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-muted">Foydalanuvchi topilmadi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="flex flex-wrap items-center justify-between gap-2 px-1 pt-3">
                    <span class="text-xs text-muted">{{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} / {{ $users->total() }} ta</span>
                    {{ $users->onEachSide(1)->links() }}
                </div>
            </div>

            <aside class="ui-card ui-split__side">
                @if ($selectedUser)
                    <div class="mb-3 flex items-center gap-3">
                        <span class="ui-avatar ui-avatar--lg">{{ $initials($selectedUser->name) }}</span>
                        <div class="min-w-0">
                            <b class="block truncate font-display text-[18px] font-semibold leading-6">{{ $selectedUser->name }}</b>
                            <span class="block text-xs text-muted">
                                Ro'yxatdan o'tgan: {{ $selectedUser->created_at?->format('d.m.Y') }}
                                · So'nggi faollik: {{ isset($lastActivity[$selectedUser->id]) ? $lastActivity[$selectedUser->id]->format('d.m.Y H:i') : '—' }}
                            </span>
                        </div>
                    </div>

                    @if ($isSelf)
                        <p class="ui-note">Bu sizning hisobingiz. O'z ma'lumotlaringizni <a href="{{ route('profile.edit') }}">Profil</a> sahifasida o'zgartirasiz.</p>
                    @else
                        <form method="POST" action="{{ route('admin.users.profile.update', $selectedUser) }}" class="grid gap-2.5">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="return" value="list">
                            @foreach (['segment', 'search', 'role', 'department_id', 'page'] as $keep)
                                @if (request()->filled($keep))
                                    <input type="hidden" name="keep[{{ $keep }}]" value="{{ request($keep) }}">
                                @endif
                            @endforeach

                            @include('admin.users.partials.fields', ['user' => $selectedUser, 'compact' => true])
                            @include('admin.users.partials.status-fields', ['user' => $selectedUser, 'boxed' => true, 'statuses' => ['active' => 'Faol', 'pending' => 'Kutilmoqda', 'inactive' => 'Nofaol']])

                            <div class="mt-1.5 flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary">Saqlash</button>
                                <a href="{{ route('admin.users.profile', ['user' => $selectedUser->id]) }}" class="btn btn-secondary">To'liq profil</a>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.users.password-reset', $selectedUser) }}" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-secondary w-full">Parolni tiklash havolasini yuborish</button>
                        </form>
                        <a href="{{ route('admin.users.profile', ['user' => $selectedUser->id]) }}#delete-user" class="mt-3 inline-block text-[13px] font-semibold text-red-700 hover:underline">Foydalanuvchini o'chirish…</a>
                    @endif
                @else
                    <div class="py-6 text-center">
                        <p class="ui-card__title">Foydalanuvchini tanlang</p>
                        <p class="ui-card__sub mt-1">Ro'yxatdan kimnidir bossangiz, profili shu yerda tahrirlanadi.</p>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</x-app-layout>

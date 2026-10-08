@php
    $user = auth()->user();
    $unreadNotificationsCount = $user?->unreadNotifications()->count() ?? 0;
    $isRequesterOnly = ($user?->hasRole(\App\Enums\UserRole::Requester->value) ?? false)
        && ! ($user?->hasAnyRole([
            \App\Enums\UserRole::Admin->value,
            \App\Enums\UserRole::Manager->value,
            \App\Enums\UserRole::Operator->value,
            \App\Enums\UserRole::Executor->value,
        ]) ?? false);
    $isAdmin = $user?->hasRole(\App\Enums\UserRole::Admin->value) ?? false;
    $isManagerWithDashboard = (! $isAdmin) && ($user?->canAccessAppDashboard() ?? false);

    $homeHref = $isRequesterOnly
        ? route('tickets.index')
        : ($isManagerWithDashboard ? route('app.dashboard') : route('app.home'));

    $notificationsItem = [
        'label' => 'Bildirishnomalar',
        'href' => route('notifications.index'),
        'active' => request()->routeIs('notifications.*'),
        'icon' => 'notifications',
        'badge' => $unreadNotificationsCount,
    ];

    if ($isAdmin) {
        // Dizayn tizimi: "Admin menyusi" — A varianti (guruhlangan yon menyu, ichki tablarsiz)
        $pendingUsersCount = \App\Models\User::query()->whereNull('approved_at')->where('is_active', true)->count();

        $sidebarItems = [
            ['label' => 'Bosh sahifa', 'href' => route('app.home'), 'active' => request()->routeIs('app.home'), 'icon' => 'home'],
            ['label' => 'Dashboard', 'href' => route('app.dashboard'), 'active' => request()->routeIs('app.dashboard'), 'icon' => 'dashboard'],
            ['group' => 'Ish'],
            ['label' => 'Murojaatlar', 'href' => route('admin.dispatch.tickets'), 'active' => request()->routeIs('admin.dispatch.tickets', 'admin.dispatch.index', 'admin.dispatch.status', 'admin.dispatch.show') && request()->query('source') !== 'archive', 'icon' => 'tickets'],
            ['label' => 'Arxiv', 'href' => route('admin.dispatch.archive'), 'active' => request()->routeIs('admin.dispatch.archive') || (request()->routeIs('admin.dispatch.show') && request()->query('source') === 'archive'), 'icon' => 'archive'],
            ['group' => 'Odamlar'],
            ['label' => 'Foydalanuvchilar', 'href' => route('admin.users.list'), 'active' => request()->routeIs('admin.users.*'), 'icon' => 'users', 'badge' => $pendingUsersCount, 'badgeTone' => 'alert'],
            ['group' => 'Sozlamalar'],
            ['label' => 'Deadline', 'href' => route('admin.dispatch.deadlines'), 'active' => request()->routeIs('admin.dispatch.deadlines', 'admin.sla.*'), 'icon' => 'clock'],
            ['label' => 'Ish kunlari', 'href' => route('admin.dispatch.work-schedule'), 'active' => request()->routeIs('admin.dispatch.work-schedule'), 'icon' => 'calendar'],
            ['label' => 'Guest himoya', 'href' => route('admin.guest-blocks.index'), 'active' => request()->routeIs('admin.guest-blocks.*'), 'icon' => 'shield'],
            ['label' => 'Zahira nusxalar', 'href' => route('admin.backups.index'), 'active' => request()->routeIs('admin.backups.*'), 'icon' => 'database'],
            ['group' => 'Tizim'],
            ['label' => 'Tizim holati', 'href' => route('admin.system.health'), 'active' => request()->routeIs('admin.system.health'), 'icon' => 'pulse'],
            ['label' => 'Loglar', 'href' => route('admin.system.logs'), 'active' => request()->routeIs('admin.system.logs*'), 'icon' => 'logs'],
            ['group' => ''],
            $notificationsItem,
        ];
    } else {
        $sidebarItems = [
            ...(! $isRequesterOnly && ! $isManagerWithDashboard ? [[
                'label' => 'Bosh sahifa',
                'href' => route('app.home'),
                'active' => request()->routeIs('app.home'),
                'icon' => 'home',
            ]] : []),
            ...($isRequesterOnly ? [[
                'label' => 'Murojaatlarim',
                'href' => route('tickets.index'),
                'active' => request()->routeIs('tickets.*'),
                'icon' => 'tickets',
            ]] : []),
            ...($user?->canAccessAppDashboard() ? [[
                'label' => $isManagerWithDashboard ? 'Bosh sahifa' : 'Dashboard',
                'href' => route('app.dashboard'),
                'active' => request()->routeIs('app.dashboard'),
                'icon' => $isManagerWithDashboard ? 'home' : 'dashboard',
            ]] : []),
            $notificationsItem,
        ];
    }
@endphp

@php
    $sidebarIcons = [
        'home' => '<path d="M3.5 9 10 3.5 16.5 9v7a1 1 0 0 1-1 1h-3.5v-4.5h-4V17H4.5a1 1 0 0 1-1-1z"/>',
        'dashboard' => '<path d="M3.5 16.5v-6M8 16.5v-10M12.5 16.5v-7M17 16.5v-12"/>',
        'tickets' => '<path d="M3 5.5h14v3a1.5 1.5 0 0 0 0 3v3H3v-3a1.5 1.5 0 0 0 0-3z"/>',
        'users' => '<circle cx="8" cy="7" r="2.6"/><path d="M3 16c.4-2.6 2.6-4 5-4s4.6 1.4 5 4M13.5 4.6a2.6 2.6 0 0 1 0 4.8M15 12.4c1.2.6 1.9 1.7 2 3.6"/>',
        'shield' => '<path d="M10 3 4.5 5v4.5c0 3.5 2.3 6 5.5 7.5 3.2-1.5 5.5-4 5.5-7.5V5z"/>',
        'archive' => '<rect x="3" y="4" width="14" height="4" rx="1"/><path d="M4.5 8v7.5h11V8M8 11.5h4"/>',
        'clock' => '<circle cx="10" cy="10" r="7"/><path d="M10 6v4l2.5 1.5"/>',
        'pulse' => '<path d="M2.5 10h3l2-5 3.5 10 2-5h4.5"/>',
        'logs' => '<rect x="4" y="3" width="12" height="14" rx="1.5"/><path d="M7 7h6M7 10h6M7 13h4"/>',
        'database' => '<ellipse cx="10" cy="5" rx="6" ry="2.5"/><path d="M4 5v10c0 1.4 2.7 2.5 6 2.5s6-1.1 6-2.5V5M4 10c0 1.4 2.7 2.5 6 2.5s6-1.1 6-2.5"/>',
        'calendar' => '<rect x="3.5" y="4.5" width="13" height="12" rx="1.5"/><path d="M3.5 8h13M7 3v3M13 3v3"/>',
        'notifications' => '<path d="M5 14V9.5a5 5 0 0 1 10 0V14l1.3 1.8H3.7zM8.3 17.5a2 2 0 0 0 3.4 0"/>',
    ];
@endphp

<aside x-data="{ open: false }" class="relative z-[90]">
    <div class="sticky top-0 flex h-14 items-center justify-between border-b border-line bg-sunken px-4 lg:hidden">
        <a href="{{ $homeHref }}" class="flex items-center gap-2.5">
            <x-application-logo class="h-8 w-8" />
            <span class="font-display text-[17px] font-semibold leading-6 text-ink">RTT Markazi</span>
            <span class="role-chip">{{ $user->display_role }}</span>
        </a>
        <div class="flex items-center gap-2">
            <x-theme-toggle />
            <button type="button" @click="open = ! open" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-line-strong bg-surface text-ink" :aria-expanded="open.toString()">
                <span class="sr-only">Menyu</span>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 20 20" stroke-width="1.6" stroke-linecap="round" aria-hidden="true">
                    <path d="M3.5 5.5h13M3.5 10h13M3.5 14.5h13" />
                </svg>
            </button>
        </div>
    </div>

    <div
        x-show="open"
        x-transition.opacity
        @click="open = false"
        class="fixed inset-0 z-[80] bg-ink/40 lg:hidden"
        style="display: none;"
    ></div>

    <div
        :class="open ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-[90] flex w-[232px] flex-col border-r border-line bg-sunken transition-transform duration-200 lg:translate-x-0"
    >
        <div class="flex items-center justify-between gap-2 px-5 pb-4 pt-5">
            <a href="{{ $homeHref }}" class="flex min-w-0 items-center gap-2.5">
                <x-application-logo class="h-8 w-8 shrink-0" />
                <span class="truncate font-display text-[17px] font-semibold leading-6 text-ink">RTT Markazi</span>
            </a>
            <span class="role-chip">{{ $user->display_role }}</span>
        </div>

        <nav class="flex flex-1 flex-col gap-0.5 overflow-y-auto px-3 pb-3" aria-label="Asosiy menyu">
            @foreach ($sidebarItems as $item)
                @if (array_key_exists('group', $item))
                    <div class="side-nav__group" @if ($item['group'] === '') aria-hidden="true" @endif>{{ $item['group'] }}</div>
                    @continue
                @endif
                <a href="{{ $item['href'] }}" class="side-nav__item" @if ($item['active']) aria-current="page" @endif>
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $sidebarIcons[$item['icon']] ?? '' !!}</svg>
                    <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                    @if (($item['badge'] ?? 0) > 0)
                        <span @class(['side-nav__count', 'side-nav__count--alert' => ($item['badgeTone'] ?? null) === 'alert'])>{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="relative border-t border-line p-3" x-data="{ userMenuOpen: false }" @click.outside="userMenuOpen = false">
            <div
                x-show="userMenuOpen"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute left-3 right-3 z-[120] origin-bottom rounded-3xl border border-line bg-surface p-1 shadow-xl"
                style="bottom: calc(100% + 0.5rem); display: none;"
                @click="userMenuOpen = false"
            >
                <a href="{{ route('profile.edit') }}" class="side-nav__item">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="7" r="3"/><path d="M4 17c.6-3 3-4.5 6-4.5s5.4 1.5 6 4.5"/></svg>
                    <span>Profil</span>
                </a>
                <a href="{{ route('app.settings') }}" class="side-nav__item">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="10" r="2.5"/><path d="M10 2.5v2M10 15.5v2M2.5 10h2M15.5 10h2M4.7 4.7l1.4 1.4M13.9 13.9l1.4 1.4M4.7 15.3l1.4-1.4M13.9 6.1l1.4-1.4"/></svg>
                    <span>Sozlamalar</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="side-nav__item w-full text-left !text-red-700 hover:!bg-red-50">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4.5V4a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-.5M9 10h8M14.5 7.5 17 10l-2.5 2.5"/></svg>
                        <span>Chiqish</span>
                    </button>
                </form>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="userMenuOpen = ! userMenuOpen"
                    :aria-expanded="userMenuOpen.toString()"
                    class="flex min-w-0 flex-1 items-center gap-2.5 rounded-md px-2 py-1.5 text-left transition hover:bg-surface"
                >
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent text-[13px] font-semibold uppercase text-accent-on">
                        {{ mb_substr($user->name, 0, 1) }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[13px] font-semibold leading-[18px] text-ink">{{ $user->name }}</span>
                        <span class="block truncate text-xs text-muted">{{ $user->email }}</span>
                    </span>
                </button>
                <x-theme-toggle class="hidden lg:inline-flex" />
            </div>
        </div>
    </div>
</aside>

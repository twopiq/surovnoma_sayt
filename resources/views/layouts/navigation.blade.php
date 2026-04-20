@php
    $user = auth()->user();
    $unreadNotificationsCount = $user?->unreadNotifications()->count() ?? 0;

    $sidebarItems = [
        [
            'label' => 'Home',
            'href' => route('app.home'),
            'active' => request()->routeIs('app.home'),
            'icon' => 'home',
        ],
        ...($user?->hasRole(\App\Enums\UserRole::Admin->value) ? [[
            'label' => 'Ticket management',
            'href' => route('admin.dispatch.tickets'),
            'active' => request()->routeIs('admin.dispatch.*'),
            'icon' => 'tickets',
        ]] : []),
        ...($user?->hasRole(\App\Enums\UserRole::Admin->value) ? [[
            'label' => 'User management',
            'href' => route('admin.users.list'),
            'active' => request()->routeIs('admin.users.*'),
            'icon' => 'users',
        ]] : []),
        [
            'label' => 'Dashboard',
            'href' => route('app.dashboard'),
            'active' => request()->routeIs('app.dashboard'),
            'icon' => 'dashboard',
        ],
        [
            'label' => 'Bildirishnomalar',
            'href' => route('notifications.index'),
            'active' => request()->routeIs('notifications.*'),
            'icon' => 'notifications',
            'badge' => $unreadNotificationsCount,
        ],
    ];
@endphp

<aside x-data="{ open: false }" class="relative z-[90]">
    <div class="sticky top-0 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 lg:hidden">
        <div class="flex items-center gap-3">
            <a href="{{ route('app.home') }}" class="flex items-center gap-3">
                <x-application-logo class="h-9 w-9 fill-current text-cyan-700" />
                <div>
                    <div class="font-['Space_Grotesk'] text-sm font-bold uppercase tracking-[0.16em] text-slate-800">RTT</div>
                    <div class="text-xs text-slate-500">{{ $user->display_role }}</div>
                </div>
            </a>
            <x-theme-toggle />
        </div>
        <button @click="open = ! open" class="rounded-md border border-slate-200 p-2 text-slate-600">
            <span class="sr-only">Menyu</span>
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <div
        x-show="open"
        x-transition.opacity
        @click="open = false"
        class="fixed inset-0 z-[80] bg-slate-950/40 lg:hidden"
        style="display: none;"
    ></div>

    <div
        :class="open ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-[90] flex w-64 flex-col border-r border-emerald-950 bg-[#123b2f] text-white shadow-2xl transition-transform duration-200 lg:translate-x-0"
    >
        <div class="flex min-h-[96px] items-center border-b border-white/10 px-4 py-4">
            <div class="flex w-full items-start justify-between gap-3">
                <a href="{{ route('app.home') }}" class="flex items-center gap-3">
                    <x-application-logo class="h-10 w-10 fill-current text-cyan-200" />
                    <div>
                        <div class="font-['Space_Grotesk'] text-sm font-bold uppercase tracking-[0.2em]">RTT</div>
                        <div class="text-xs text-cyan-100/75">{{ $user->display_role }}</div>
                    </div>
                </a>
                <x-theme-toggle tone="sidebar" />
            </div>
        </div>

        <nav class="flex-1 space-y-1 px-3 py-4" aria-label="Sidebar">
            @foreach ($sidebarItems as $item)
                @if ($item['active'])
                    <span class="flex items-center gap-3 rounded-md bg-cyan-300/20 px-3 py-2 text-sm font-semibold text-white" aria-current="page">
                        @if ($item['icon'] === 'home')
                            <svg class="h-5 w-5 text-cyan-100" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M9.3 2.45a1 1 0 0 1 1.4 0l6.25 6.1a1 1 0 0 1-1.4 1.43l-.55-.54V16a2 2 0 0 1-2 2h-2.25a.75.75 0 0 1-.75-.75v-4.5h-2v4.5a.75.75 0 0 1-.75.75H5a2 2 0 0 1-2-2V9.44l-.55.54a1 1 0 0 1-1.4-1.43l6.25-6.1Z" />
                            </svg>
                        @elseif ($item['icon'] === 'dashboard')
                            <svg class="h-5 w-5 text-cyan-100" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M3 4.5A1.5 1.5 0 0 1 4.5 3h3A1.5 1.5 0 0 1 9 4.5v3A1.5 1.5 0 0 1 7.5 9h-3A1.5 1.5 0 0 1 3 7.5v-3ZM11 4.5A1.5 1.5 0 0 1 12.5 3h3A1.5 1.5 0 0 1 17 4.5v3A1.5 1.5 0 0 1 15.5 9h-3A1.5 1.5 0 0 1 11 7.5v-3ZM3 12.5A1.5 1.5 0 0 1 4.5 11h3A1.5 1.5 0 0 1 9 12.5v3A1.5 1.5 0 0 1 7.5 17h-3A1.5 1.5 0 0 1 3 15.5v-3ZM11 12.5a1.5 1.5 0 0 1 1.5-1.5h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3a1.5 1.5 0 0 1-1.5-1.5v-3Z" />
                            </svg>
                        @elseif ($item['icon'] === 'notifications')
                            <svg class="h-5 w-5 text-cyan-100" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M4 8a6 6 0 1 1 12 0v3.586l.707.707A1 1 0 0 1 16 14H4a1 1 0 0 1-.707-1.707L4 11.586V8Z" />
                                <path d="M8 15a2 2 0 1 0 4 0H8Z" />
                            </svg>
                        @elseif ($item['icon'] === 'users')
                            <svg class="h-5 w-5 text-cyan-100" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M7.5 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM2 16.5A5.5 5.5 0 0 1 12.1 13.46a.75.75 0 0 0 1.24-.84 7 7 0 0 0-12.84 3.88.75.75 0 0 0 1.5 0ZM13 8.5a2.5 2.5 0 1 0 0-5 .75.75 0 0 0 0 1.5 1 1 0 1 1 0 2 .75.75 0 0 0 0 1.5ZM13.5 10.5a.75.75 0 0 0 0 1.5 3 3 0 0 1 3 3 .75.75 0 0 0 1.5 0 4.5 4.5 0 0 0-4.5-4.5Z" />
                            </svg>
                        @elseif ($item['icon'] === 'tickets')
                            <svg class="h-5 w-5 text-cyan-100" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M4.5 3A2.5 2.5 0 0 0 2 5.5v1.75a.75.75 0 0 0 .75.75 2 2 0 1 1 0 4 .75.75 0 0 0-.75.75v1.75A2.5 2.5 0 0 0 4.5 17h11a2.5 2.5 0 0 0 2.5-2.5v-1.75a.75.75 0 0 0-.75-.75 2 2 0 1 1 0-4 .75.75 0 0 0 .75-.75V5.5A2.5 2.5 0 0 0 15.5 3h-11Zm6.25 3.25a.75.75 0 0 0-1.5 0v1a.75.75 0 0 0 1.5 0v-1Zm0 3.5a.75.75 0 0 0-1.5 0v.5a.75.75 0 0 0 1.5 0v-.5Zm0 3a.75.75 0 0 0-1.5 0v1a.75.75 0 0 0 1.5 0v-1Z" clip-rule="evenodd" />
                            </svg>
                        @endif
                        <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                        @if (($item['badge'] ?? 0) > 0)
                            <span class="rounded-full bg-cyan-100 px-2 py-0.5 text-xs font-bold text-emerald-950">{{ $item['badge'] }}</span>
                        @endif
                    </span>
                @else
                    <a href="{{ $item['href'] }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-semibold text-cyan-50/85 transition hover:bg-white/10 hover:text-white">
                        @if ($item['icon'] === 'home')
                            <svg class="h-5 w-5 text-cyan-100/75" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M9.3 2.45a1 1 0 0 1 1.4 0l6.25 6.1a1 1 0 0 1-1.4 1.43l-.55-.54V16a2 2 0 0 1-2 2h-2.25a.75.75 0 0 1-.75-.75v-4.5h-2v4.5a.75.75 0 0 1-.75.75H5a2 2 0 0 1-2-2V9.44l-.55.54a1 1 0 0 1-1.4-1.43l6.25-6.1Z" />
                            </svg>
                        @elseif ($item['icon'] === 'dashboard')
                            <svg class="h-5 w-5 text-cyan-100/75" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M3 4.5A1.5 1.5 0 0 1 4.5 3h3A1.5 1.5 0 0 1 9 4.5v3A1.5 1.5 0 0 1 7.5 9h-3A1.5 1.5 0 0 1 3 7.5v-3ZM11 4.5A1.5 1.5 0 0 1 12.5 3h3A1.5 1.5 0 0 1 17 4.5v3A1.5 1.5 0 0 1 15.5 9h-3A1.5 1.5 0 0 1 11 7.5v-3ZM3 12.5A1.5 1.5 0 0 1 4.5 11h3A1.5 1.5 0 0 1 9 12.5v3A1.5 1.5 0 0 1 7.5 17h-3A1.5 1.5 0 0 1 3 15.5v-3ZM11 12.5a1.5 1.5 0 0 1 1.5-1.5h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3a1.5 1.5 0 0 1-1.5-1.5v-3Z" />
                            </svg>
                        @elseif ($item['icon'] === 'notifications')
                            <svg class="h-5 w-5 text-cyan-100/75" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M4 8a6 6 0 1 1 12 0v3.586l.707.707A1 1 0 0 1 16 14H4a1 1 0 0 1-.707-1.707L4 11.586V8Z" />
                                <path d="M8 15a2 2 0 1 0 4 0H8Z" />
                            </svg>
                        @elseif ($item['icon'] === 'users')
                            <svg class="h-5 w-5 text-cyan-100/75" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M7.5 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM2 16.5A5.5 5.5 0 0 1 12.1 13.46a.75.75 0 0 0 1.24-.84 7 7 0 0 0-12.84 3.88.75.75 0 0 0 1.5 0ZM13 8.5a2.5 2.5 0 1 0 0-5 .75.75 0 0 0 0 1.5 1 1 0 1 1 0 2 .75.75 0 0 0 0 1.5ZM13.5 10.5a.75.75 0 0 0 0 1.5 3 3 0 0 1 3 3 .75.75 0 0 0 1.5 0 4.5 4.5 0 0 0-4.5-4.5Z" />
                            </svg>
                        @elseif ($item['icon'] === 'tickets')
                            <svg class="h-5 w-5 text-cyan-100/75" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M4.5 3A2.5 2.5 0 0 0 2 5.5v1.75a.75.75 0 0 0 .75.75 2 2 0 1 1 0 4 .75.75 0 0 0-.75.75v1.75A2.5 2.5 0 0 0 4.5 17h11a2.5 2.5 0 0 0 2.5-2.5v-1.75a.75.75 0 0 0-.75-.75 2 2 0 1 1 0-4 .75.75 0 0 0 .75-.75V5.5A2.5 2.5 0 0 0 15.5 3h-11Zm6.25 3.25a.75.75 0 0 0-1.5 0v1a.75.75 0 0 0 1.5 0v-1Zm0 3.5a.75.75 0 0 0-1.5 0v.5a.75.75 0 0 0 1.5 0v-.5Zm0 3a.75.75 0 0 0-1.5 0v1a.75.75 0 0 0 1.5 0v-1Z" clip-rule="evenodd" />
                            </svg>
                        @endif
                        <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                        @if (($item['badge'] ?? 0) > 0)
                            <span class="rounded-full bg-cyan-100/90 px-2 py-0.5 text-xs font-bold text-emerald-950">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="relative border-t border-white/10 p-3" x-data="{ userMenuOpen: false }" @click.outside="userMenuOpen = false">
            <div
                x-show="userMenuOpen"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute left-3 right-3 z-[120] origin-bottom rounded-md border border-white/10 bg-white p-1 shadow-2xl ring-1 ring-black/5"
                style="bottom: calc(100% + 0.5rem); display: none;"
                @click="userMenuOpen = false"
            >
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-950">
                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-7 9a7 7 0 1 1 14 0H3Z" clip-rule="evenodd" />
                    </svg>
                    <span>Profil</span>
                </a>
                <a href="{{ route('app.settings') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-950">
                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.84 1.804A1 1 0 0 1 8.82 1h2.36a1 1 0 0 1 .98.804l.27 1.35c.37.15.72.35 1.04.6l1.29-.46a1 1 0 0 1 1.17.39l1.18 2.05a1 1 0 0 1-.19 1.22l-1.02.91a6.98 6.98 0 0 1 0 1.2l1.02.91a1 1 0 0 1 .19 1.22l-1.18 2.05a1 1 0 0 1-1.17.39l-1.29-.46c-.32.25-.67.45-1.04.6l-.27 1.35a1 1 0 0 1-.98.804H8.82a1 1 0 0 1-.98-.804l-.27-1.35a5.54 5.54 0 0 1-1.04-.6l-1.29.46a1 1 0 0 1-1.17-.39l-1.18-2.05a1 1 0 0 1 .19-1.22l1.02-.91a6.98 6.98 0 0 1 0-1.2l-1.02-.91a1 1 0 0 1-.19-1.22l1.18-2.05a1 1 0 0 1 1.17-.39l1.29.46c.32-.25.67-.45 1.04-.6l.27-1.35ZM10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" clip-rule="evenodd" />
                    </svg>
                    <span>Sozlamalar</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm font-medium text-red-600 transition hover:bg-red-50">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3 4.25A2.25 2.25 0 0 1 5.25 2h5.5A2.25 2.25 0 0 1 13 4.25v2a.75.75 0 0 1-1.5 0v-2a.75.75 0 0 0-.75-.75h-5.5a.75.75 0 0 0-.75.75v11.5c0 .414.336.75.75.75h5.5a.75.75 0 0 0 .75-.75v-2a.75.75 0 0 1 1.5 0v2A2.25 2.25 0 0 1 10.75 18h-5.5A2.25 2.25 0 0 1 3 15.75V4.25Zm10.72 4.22a.75.75 0 0 1 1.06 0l1.75 1.75a.75.75 0 0 1 0 1.06l-1.75 1.75a.75.75 0 1 1-1.06-1.06l.47-.47H8.75a.75.75 0 0 1 0-1.5h5.44l-.47-.47a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                        <span>Chiqish</span>
                    </button>
                </form>
            </div>

            <button
                type="button"
                @click="userMenuOpen = ! userMenuOpen"
                :aria-expanded="userMenuOpen.toString()"
                class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left transition hover:bg-white/10"
            >
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-cyan-100 text-sm font-bold uppercase text-emerald-950">
                    {{ mb_substr($user->name, 0, 1) }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-white">{{ $user->name }}</span>
                    <span class="block truncate text-xs text-cyan-100/70">{{ $user->email }}</span>
                </span>
                <svg class="h-4 w-4 shrink-0 text-cyan-100/75 transition" :class="{ 'rotate-180': userMenuOpen }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>
</aside>

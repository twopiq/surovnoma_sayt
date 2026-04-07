@php($user = auth()->user())
<nav x-data="{ open: false }" class="relative z-[90] overflow-visible border-b border-slate-200/80 bg-white/92 backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between gap-6 overflow-visible">
            <div class="flex items-center gap-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <x-application-logo class="h-10 w-10 fill-current text-cyan-700" />
                    <div class="hidden sm:block">
                        <div class="font-['Space_Grotesk'] text-sm font-bold uppercase tracking-[0.2em] text-slate-700">RTT</div>
                        <div class="text-xs text-slate-500">{{ $user->display_role }}</div>
                    </div>
                </a>

                <div class="hidden sm:flex sm:items-center sm:gap-2">
                    @role('requester')
                        <x-nav-link :href="route('tickets.index')" :active="request()->routeIs('tickets.*')">Mening murojaatlarim</x-nav-link>
                    @endrole
                    @role('operator')
                        <x-nav-link :href="route('operator.tickets.index')" :active="request()->routeIs('operator.tickets.*')">Operator paneli</x-nav-link>
                    @endrole
                    @role('executor')
                        <x-nav-link :href="route('executor.tickets.index')" :active="request()->routeIs('executor.tickets.*')">Mening vazifalarim</x-nav-link>
                    @endrole
                    @role('manager')
                        <x-nav-link :href="route('manager.dashboard')" :active="request()->routeIs('manager.dashboard')">Dashboard</x-nav-link>
                    @endrole
                    @role('admin')
                        <x-nav-link :href="route('admin.dispatch.index')" :active="request()->routeIs('admin.dispatch.*')">Dispetcher</x-nav-link>
                        <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">Foydalanuvchilar</x-nav-link>
                        <x-nav-link :href="route('admin.departments.index')" :active="request()->routeIs('admin.departments.*')">Bo‘limlar</x-nav-link>
                        <x-nav-link :href="route('admin.sla.index')" :active="request()->routeIs('admin.sla.*')">SLA</x-nav-link>
                    @endrole
                </div>
            </div>

            <div class="hidden overflow-visible sm:flex sm:items-center sm:gap-4">
                <livewire:notifications-dropdown />

                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-3 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:text-slate-900">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-cyan-100 text-cyan-700">
                                {{ mb_substr($user->name, 0, 1) }}
                            </span>
                            <span class="text-left">
                                <span class="block text-sm font-semibold text-slate-800">{{ $user->name }}</span>
                                <span class="block text-xs text-slate-500">{{ $user->email }}</span>
                            </span>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Profil</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                Chiqish
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{ 'block': open, 'hidden': ! open }" class="hidden border-t border-slate-200 bg-white sm:hidden">
        <div class="space-y-1 px-4 py-4">
            @role('requester')
                <x-responsive-nav-link :href="route('tickets.index')" :active="request()->routeIs('tickets.*')">Mening murojaatlarim</x-responsive-nav-link>
            @endrole
            @role('operator')
                <x-responsive-nav-link :href="route('operator.tickets.index')" :active="request()->routeIs('operator.tickets.*')">Operator paneli</x-responsive-nav-link>
            @endrole
            @role('executor')
                <x-responsive-nav-link :href="route('executor.tickets.index')" :active="request()->routeIs('executor.tickets.*')">Mening vazifalarim</x-responsive-nav-link>
            @endrole
            @role('manager')
                <x-responsive-nav-link :href="route('manager.dashboard')" :active="request()->routeIs('manager.dashboard')">Dashboard</x-responsive-nav-link>
            @endrole
            @role('admin')
                <x-responsive-nav-link :href="route('admin.dispatch.index')" :active="request()->routeIs('admin.dispatch.*')">Dispetcher</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">Foydalanuvchilar</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.departments.index')" :active="request()->routeIs('admin.departments.*')">Bo‘limlar</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.sla.index')" :active="request()->routeIs('admin.sla.*')">SLA</x-responsive-nav-link>
            @endrole
        </div>

        <div class="border-t border-slate-200 px-4 py-4">
            <div class="font-medium text-base text-slate-800">{{ $user->name }}</div>
            <div class="text-sm text-slate-500">{{ $user->email }}</div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">Profil</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        Chiqish
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

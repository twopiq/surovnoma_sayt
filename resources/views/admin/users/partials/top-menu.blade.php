@php
    $userTabs = [
        [
            'label' => 'Users CRUD',
            'href' => route('admin.users.list'),
            'active' => request()->routeIs('admin.users.list') || request()->routeIs('admin.users.create') || request()->routeIs('admin.users.profile'),
        ],
        [
            'label' => 'Recent registrations',
            'href' => route('admin.users.recent'),
            'active' => request()->routeIs('admin.users.recent'),
        ],
        [
            'label' => 'Tasdiq kutayotganlar',
            'href' => route('admin.users.index'),
            'active' => request()->routeIs('admin.users.index'),
            'badge' => $pendingCount ?? 0,
        ],
    ];
@endphp

<div class="flex flex-wrap items-center gap-2">
    @foreach ($userTabs as $tab)
        <a
            href="{{ $tab['href'] }}"
            @class([
                'inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition',
                'bg-cyan-100 text-cyan-900 ring-1 ring-cyan-200' => $tab['active'],
                'text-slate-600 hover:bg-slate-100 hover:text-slate-950' => ! $tab['active'],
            ])
        >
            {{ $tab['label'] }}
            @if (($tab['badge'] ?? 0) > 0)
                <span @class([
                    'inline-flex items-center justify-center rounded-full px-1.5 py-0.5 text-[11px] font-bold leading-none',
                    'bg-cyan-700 text-white' => $tab['active'],
                    'bg-amber-100 text-amber-700' => ! $tab['active'],
                ])>{{ $tab['badge'] }}</span>
            @endif
        </a>
    @endforeach
</div>

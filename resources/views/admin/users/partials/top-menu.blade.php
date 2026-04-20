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
    ];
@endphp

<div class="flex flex-wrap items-center gap-2">
    @foreach ($userTabs as $tab)
        <a
            href="{{ $tab['href'] }}"
            @class([
                'rounded-md px-4 py-2 text-sm font-semibold transition',
                'bg-cyan-100 text-cyan-900 ring-1 ring-cyan-200' => $tab['active'],
                'text-slate-600 hover:bg-slate-100 hover:text-slate-950' => ! $tab['active'],
            ])
        >
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>

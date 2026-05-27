@php
    $dashboardTabs = [
        [
            'label' => 'Dashboard',
            'href' => route('app.dashboard'),
            'active' => request()->routeIs('app.dashboard'),
        ],
    ];
@endphp

<div class="flex flex-wrap items-center gap-2">
    @foreach ($dashboardTabs as $tab)
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

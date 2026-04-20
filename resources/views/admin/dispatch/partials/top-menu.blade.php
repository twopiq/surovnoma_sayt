@php
    $dispatchTabs = [
        [
            'label' => 'Doska',
            'href' => route('admin.dispatch.index'),
            'active' => request()->routeIs('admin.dispatch.index'),
        ],
        [
            'label' => 'Murojaatlar',
            'href' => route('admin.dispatch.tickets'),
            'active' => request()->routeIs('admin.dispatch.tickets'),
        ],
        [
            'label' => 'Arxiv',
            'href' => route('admin.dispatch.archive'),
            'active' => request()->routeIs('admin.dispatch.archive'),
        ],
    ];
@endphp

<div class="flex flex-wrap items-center gap-2">
    @foreach ($dispatchTabs as $tab)
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

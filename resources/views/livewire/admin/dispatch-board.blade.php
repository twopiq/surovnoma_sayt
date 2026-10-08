<div class="grid items-start gap-4" style="grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr))">
    @php
        $source = request()->routeIs('app.home') ? 'home' : 'board';
    @endphp

    @foreach ($statuses as $status)
        @php
            $statusUrl = route('admin.dispatch.status', ['status' => $status->value]);
            $total = $totals[$status->value] ?? 0;
        @endphp

        <div class="rounded-xl border p-3.5 {{ $status->columnCssClass() }}">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex min-w-0 items-center gap-2">
                    <h3 class="m-0"><x-ticket-status-badge :status="$status" size="md" /></h3>
                    <span class="rounded-full bg-white/80 px-2 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">{{ $total }}</span>
                </div>
                <a href="{{ $statusUrl }}" class="rounded-full bg-white/90 px-3 py-1 text-xs font-bold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-cyan-50 hover:text-cyan-800">
                    Barchasi
                </a>
            </div>
            <div class="space-y-3">
                @forelse ($grouped[$status->value] as $ticket)
                    <a href="{{ route('admin.dispatch.show', ['ticket' => $ticket, 'source' => $source]) }}" class="block rounded-[14px] focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-600">
                        <x-ticket-card :ticket="$ticket" :show-status="false" />
                    </a>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 p-4 text-sm text-slate-400">Bo'sh</div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>

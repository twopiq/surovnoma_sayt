<div class="grid gap-4 lg:grid-cols-3 xl:grid-cols-4">
    @php
        $source = request()->routeIs('app.home') ? 'home' : 'board';
    @endphp

    @foreach ($statuses as $status)
        @php
            $statusUrl = route('admin.dispatch.status', ['status' => $status->value]);
            $total = $totals[$status->value] ?? 0;
        @endphp

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" style="{{ $status->boardStyle() }}">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex min-w-0 items-center gap-2">
                    <h3 class="inline-flex rounded-md px-2 py-1 text-sm font-semibold ring-1" style="{{ $status->badgeStyle() }}">{{ $status->label() }}</h3>
                    <span class="rounded-full bg-white/80 px-2 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">{{ $total }}</span>
                </div>
                <a href="{{ $statusUrl }}" class="rounded-full bg-white/90 px-3 py-1 text-xs font-bold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-cyan-50 hover:text-cyan-800">
                    Barchasi
                </a>
            </div>
            <div class="space-y-3">
                @forelse ($grouped[$status->value] as $ticket)
                    <a href="{{ route('admin.dispatch.show', ['ticket' => $ticket, 'source' => $source]) }}" class="block rounded-xl bg-slate-50 p-3 text-sm text-slate-700 transition hover:bg-cyan-50">
                        <div class="font-semibold">{{ $ticket->reference }}</div>
                        <div class="mt-1 text-slate-500">{{ \Illuminate\Support\Str::limit($ticket->requester_name, 24) }}</div>
                    </a>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 p-4 text-sm text-slate-400">Bo'sh</div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>

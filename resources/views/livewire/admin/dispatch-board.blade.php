<div class="grid gap-4 lg:grid-cols-3 xl:grid-cols-4">
    @foreach ($statuses as $status)
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" style="{{ $status->boardStyle() }}">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="inline-flex rounded-md px-2 py-1 text-sm font-semibold ring-1" style="{{ $status->badgeStyle() }}">{{ $status->label() }}</h3>
                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-500">{{ $grouped[$status->value]->count() }}</span>
            </div>
            <div class="space-y-3">
                @forelse ($grouped[$status->value] as $ticket)
                    <a href="{{ route('admin.dispatch.show', ['ticket' => $ticket, 'source' => 'board']) }}" class="block rounded-xl bg-slate-50 p-3 text-sm text-slate-700 transition hover:bg-cyan-50">
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

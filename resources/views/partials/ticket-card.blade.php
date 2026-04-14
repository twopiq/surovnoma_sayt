<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ $ticket->reference }}</div>
            <div class="mt-1 text-lg font-semibold text-slate-900">{{ \Illuminate\Support\Str::limit($ticket->description, 90) }}</div>
        </div>
        <div class="text-right text-sm">
            <div class="font-semibold text-cyan-700">{{ $ticket->priority->label() }}</div>
            <x-ticket-status-badge :status="$ticket->status" class="mt-2" />
        </div>
    </div>
    <div class="mt-4 flex flex-wrap gap-4 text-sm text-slate-500">
        <span>Murojaatchi: {{ $ticket->requester_name }}</span>
        @if ($ticket->deadline_at)
            <span>Deadline: {{ $ticket->deadline_at->format('d.m.Y H:i') }}</span>
        @endif
        @if ($ticket->assignedExecutor)
            <span>Ijrochi: {{ $ticket->assignedExecutor->name }}</span>
        @endif
    </div>
</div>

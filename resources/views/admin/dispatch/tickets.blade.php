@php
    $query = request()->except(['ticket', 'page']);
    $currentStatus = request('status');
    $overdueOnly = request()->boolean('overdue');
    $allCount = collect($statusCounts)->sum();
    $slaBar = function ($ticket) {
        $card = new \App\View\Components\TicketCard($ticket);
        $percent = $ticket->slaElapsedPercent();
        $color = match (true) {
            $card->tone === 'late' => 'status-new-dot',
            $percent !== null && $percent >= 75 => 'status-in-progress-dot',
            default => 'accent',
        };

        return ['pill' => $card->pill, 'late' => $card->tone === 'late', 'percent' => $percent ?? 0, 'color' => $color, 'subject' => $card->subject];
    };
    $hasFilters = collect(['q', 'priority', 'category_id', 'executor_id', 'status', 'overdue'])->contains(fn ($key) => request()->filled($key));
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="pg-crumb">{{ __('Ish') }}</div>
                <h2>{{ __('Murojaatlar') }}</h2>
                <p class="pg-sub">{{ __('Faol murojaatlarni filtrlang va kartochkaga o\'ting.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.dispatch.index') }}" class="btn btn-secondary">{{ __('Doska ko\'rinishi') }}</a>
                <a href="{{ route('admin.dispatch.export', array_merge($query, ['format' => 'csv'])) }}" class="btn btn-secondary">CSV</a>
                <a href="{{ route('admin.dispatch.export', array_merge($query, ['format' => 'excel'])) }}" class="btn btn-primary">Excel</a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-none px-4 pt-6 sm:px-6 lg:px-8">
        <div class="ui-card mb-3 !p-3">
            <div class="mb-2.5 flex flex-wrap gap-2">
                <a href="{{ route('admin.dispatch.tickets', \Illuminate\Support\Arr::except($query, ['status', 'overdue'])) }}" class="ui-chip" @if (! $currentStatus && ! $overdueOnly) aria-current="page" @endif>{{ __('Barchasi') }} <b>{{ $allCount }}</b></a>
                @foreach ($chipStatuses as $status)
                    <a href="{{ route('admin.dispatch.tickets', array_merge(\Illuminate\Support\Arr::except($query, ['overdue']), ['status' => $status->value])) }}" class="ui-chip" @if ($currentStatus === $status->value) aria-current="page" @endif>{{ $status->label() }} <b>{{ $statusCounts[$status->value] ?? 0 }}</b></a>
                @endforeach
                <a href="{{ route('admin.dispatch.tickets', array_merge(\Illuminate\Support\Arr::except($query, ['status']), ['overdue' => 1])) }}" class="ui-chip" @if ($overdueOnly) aria-current="page" @endif>{{ __('Kechikkan') }} <b>{{ $overdueCount }}</b></a>
            </div>

            <form method="GET" action="{{ route('admin.dispatch.tickets') }}" class="flex flex-wrap items-center gap-2" data-auto-filter>
                @foreach (['status', 'overdue'] as $keep)
                    @if (request()->filled($keep))
                        <input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">
                    @endif
                @endforeach
                <label class="relative block w-full sm:w-[260px]">
                    <span class="sr-only">{{ __('Qidirish') }}</span>
                    <svg class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><circle cx="9" cy="9" r="5.5"/><path d="m13.5 13.5 3 3"/></svg>
                    <input name="q" value="{{ request('q') }}" placeholder="{{ __('Raqam, mavzu yoki murojaatchi') }}" class="ui-input pl-8">
                </label>
                <select name="priority" aria-label="{{ __('Muhimlik') }}" class="py-1.5 pr-8 text-[13px]">
                    <option value="">{{ __('Muhimlik: Barchasi') }}</option>
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority->value }}" @selected(request('priority') === $priority->value)>{{ $priority->label() }}</option>
                    @endforeach
                </select>
                <select name="category_id" aria-label="{{ __('Kategoriya') }}" class="py-1.5 pr-8 text-[13px]">
                    <option value="">{{ __('Kategoriya: Barchasi') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <select name="executor_id" aria-label="{{ __('Ijrochi') }}" class="py-1.5 pr-8 text-[13px]">
                    <option value="">{{ __('Ijrochi: Barchasi') }}</option>
                    @foreach ($executors as $executor)
                        <option value="{{ $executor->id }}" @selected((string) request('executor_id') === (string) $executor->id)>{{ $executor->name }}</option>
                    @endforeach
                </select>
                @if ($hasFilters)
                    <a href="{{ route('admin.dispatch.tickets') }}" class="text-[13px] text-muted hover:text-ink sm:ml-auto">{{ __('Filtrlarni tozalash') }}</a>
                @endif
            </form>
        </div>

        <div class="ui-split">
            <div class="ui-card overflow-hidden !p-0">
                @forelse ($tickets as $ticket)
                    @php($sla = $slaBar($ticket))
                    <a href="{{ route('admin.dispatch.tickets', array_merge(request()->query(), ['ticket' => $ticket->id])) }}" class="ui-list-item" @if ($selected?->is($ticket)) aria-current="true" @endif>
                        <div class="flex items-start justify-between gap-2">
                            <b class="min-w-0 truncate text-[14px] font-medium">{{ $sla['subject'] }}</b>
                            <x-ticket-status-badge :status="$ticket->status" />
                        </div>
                        <div class="mt-0.5 truncate text-xs text-muted">
                            <span class="ui-mono text-xs">{{ $ticket->reference }}</span> · {{ $ticket->requester_name }} · {{ $ticket->category?->name ?? 'Kategoriyasiz' }}
                        </div>
                        <div class="mt-1.5 flex flex-wrap items-center justify-between gap-2 text-[13px]">
                            <x-ui.priority :priority="$ticket->priority" />
                            <span class="whitespace-nowrap">
                                <span class="ui-meter"><i style="width: {{ $sla['percent'] }}%; background: rgb(var(--c-{{ $sla['color'] }}))"></i></span>
                                <span @class(['ui-mono', 'text-red-700' => $sla['late']])>{{ $sla['pill'] }}</span>
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-10 text-center text-muted">{{ __('Filtr bo\'yicha murojaat topilmadi.') }}</div>
                @endforelse
                @if ($tickets->hasPages())
                    <div class="border-t border-line px-3 py-2.5">{{ $tickets->onEachSide(1)->links() }}</div>
                @endif
            </div>

            <aside class="ui-card ui-split__side">
                @if ($selected)
                    @php($sla = $slaBar($selected))
                    <div class="flex items-center justify-between gap-2">
                        <span class="ui-mono">{{ $selected->reference }}</span>
                        <x-ticket-status-badge :status="$selected->status" />
                    </div>
                    <h3 class="mb-1 mt-2 font-display text-[20px] font-semibold leading-[26px]">{{ $sla['subject'] }}</h3>
                    <p class="mb-3 whitespace-pre-line text-[14px] text-muted">{{ \Illuminate\Support\Str::limit($selected->description, 400) }}</p>

                    <dl class="ui-kv">
                        <dt>{{ __('Murojaatchi') }}</dt>
                        <dd>{{ $selected->requester_name }}@if ($selected->requester_department) · {{ $selected->requester_department }}@endif</dd>
                        <dt>{{ __('Kategoriya') }}</dt>
                        <dd>{{ $selected->category?->name ?? 'Kategoriyasiz' }}</dd>
                        <dt>{{ __('Muhimlik') }}</dt>
                        <dd><x-ui.priority :priority="$selected->priority" /></dd>
                        <dt>{{ __('Ijrochi') }}</dt>
                        <dd @class(['text-muted' => ! $selected->assignedExecutor])>{{ $selected->assignedExecutor?->name ?? __('Belgilanmagan') }}</dd>
                    </dl>

                    <div class="mb-1.5 mt-3.5 text-xs text-muted">{{ __('Muddat') }}</div>
                    <div class="grid grid-cols-3 gap-2 text-[13px]">
                        <div><div class="text-xs text-muted">{{ __('Qabul') }}</div><div class="ui-mono">{{ $selected->created_at?->format('d.m H:i') }}</div></div>
                        <div><div class="text-xs text-muted">{{ __('Berilgan') }}</div><div class="ui-mono">{{ $selected->slaDurationLabel() }}</div></div>
                        <div><div class="text-xs text-muted">{{ __('Tugash') }}</div><div @class(['ui-mono', 'text-red-700' => $sla['late']])>{{ $selected->deadline_at ? $sla['pill'] : '—' }}</div></div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2" x-data="{ reject: false }">
                        <a href="{{ route('admin.dispatch.show', $selected) }}#assign" class="btn btn-primary">{{ __('Tayinlash') }}</a>
                        @if (! in_array($selected->status, [\App\Enums\TicketStatus::Rejected, \App\Enums\TicketStatus::Closed, \App\Enums\TicketStatus::Cancelled], true))
                            <button type="button" class="btn btn-secondary" @click="reject = ! reject" :aria-expanded="reject.toString()">{{ __('Rad etish') }}</button>
                            <form method="POST" action="{{ route('admin.dispatch.close', $selected) }}" onsubmit="return confirm(@js(__("Murojaat yopilib arxivga o'tkazilsinmi?")))">
                                @csrf
                                <button type="submit" class="btn btn-secondary">{{ __('Yopish') }}</button>
                            </form>
                        @endif
                        <a href="{{ route('admin.dispatch.show', $selected) }}" class="btn btn-secondary">{{ __('Batafsil') }}</a>

                        <form x-show="reject" x-cloak method="POST" action="{{ route('admin.dispatch.reject', $selected) }}" class="mt-1 grid w-full gap-2">
                            @csrf
                            <label class="ui-field-label" for="reject-reason">{{ __('Rad etish sababi') }}</label>
                            <textarea id="reject-reason" name="reason" rows="3" minlength="5" required class="ui-input" placeholder="{{ __('Murojaatchiga ko\'rinadigan izoh') }}"></textarea>
                            <button type="submit" class="btn btn-danger">{{ __('Rad etishni tasdiqlash') }}</button>
                        </form>
                    </div>
                @else
                    <div class="py-6 text-center">
                        <p class="ui-card__title">{{ __('Murojaat tanlanmagan') }}</p>
                        <p class="ui-card__sub mt-1">{{ __('Ro\'yxatdan murojaatni bosing — tafsilotlari shu yerda chiqadi.') }}</p>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</x-app-layout>

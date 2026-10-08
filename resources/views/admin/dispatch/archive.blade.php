@php
    $query = request()->except(['ticket', 'page']);
    $currentStatus = request('status');
    $allCount = collect($statusCounts)->sum();
    $subjectOf = fn ($ticket) => (new \App\View\Components\TicketCard($ticket))->subject;
    $hasFilters = collect(['q', 'priority', 'category_id', 'executor_id', 'status', 'period'])->contains(fn ($key) => request()->filled($key));
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="pg-crumb">{{ __('Ish') }}</div>
                <h2>{{ __('Murojaatlar arxivi') }}</h2>
                <p class="pg-sub">{{ __('Bajarilgan, yopilgan va rad etilgan murojaatlar shu yerda saqlanadi.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.dispatch.export', array_merge($query, ['archive' => 1, 'format' => 'csv'])) }}" class="btn btn-secondary">CSV</a>
                <a href="{{ route('admin.dispatch.export', array_merge($query, ['archive' => 1, 'format' => 'excel'])) }}" class="btn btn-primary">Excel</a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-none px-4 pt-6 sm:px-6 lg:px-8">
        <div class="ui-card mb-3 !p-3">
            <div class="mb-2.5 flex flex-wrap gap-2">
                <a href="{{ route('admin.dispatch.archive', \Illuminate\Support\Arr::except($query, ['status'])) }}" class="ui-chip" @if (! $currentStatus) aria-current="page" @endif>{{ __('Barchasi') }} <b>{{ $allCount }}</b></a>
                @foreach ($statuses as $status)
                    <a href="{{ route('admin.dispatch.archive', array_merge($query, ['status' => $status->value])) }}" class="ui-chip" @if ($currentStatus === $status->value) aria-current="page" @endif>{{ $status->label() }} <b>{{ $statusCounts[$status->value] ?? 0 }}</b></a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('admin.dispatch.archive') }}" class="flex flex-wrap items-center gap-2" data-auto-filter>
                @if ($currentStatus)
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif
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
                <select name="period" aria-label="{{ __('Davr') }}" class="py-1.5 pr-8 text-[13px]">
                    @foreach ($periods as $value => $label)
                        <option value="{{ $value }}" @selected($period === (string) $value)>{{ __('Davr: :label', ['label' => __($label)]) }}</option>
                    @endforeach
                </select>
                @if ($hasFilters)
                    <a href="{{ route('admin.dispatch.archive') }}" class="text-[13px] text-muted hover:text-ink sm:ml-auto">{{ __('Filtrlarni tozalash') }}</a>
                @endif
            </form>
        </div>

        <div class="ui-split">
            <div class="ui-card overflow-hidden !p-0">
                @forelse ($groups as $group => $items)
                    <div class="bg-sunken px-3.5 py-2.5 text-[11px] font-semibold uppercase leading-4 tracking-[.06em] text-muted">{{ $group }}</div>
                    @foreach ($items as $ticket)
                        <a href="{{ route('admin.dispatch.archive', array_merge(request()->query(), ['ticket' => $ticket->id])) }}" class="ui-list-item !border-t-line" @if ($selected?->is($ticket)) aria-current="true" @endif>
                            <div class="flex items-start justify-between gap-2">
                                <b class="min-w-0 truncate text-[14px] font-medium">{{ $subjectOf($ticket) }}</b>
                                <x-ticket-status-badge :status="$ticket->status" />
                            </div>
                            <div class="mt-0.5 truncate text-xs text-muted">
                                <span class="ui-mono text-xs">{{ $ticket->reference }}</span> · {{ $ticket->requester_name }} · {{ $ticket->category?->name ?? 'Kategoriyasiz' }}
                            </div>
                            <div class="mt-1.5 flex flex-wrap items-center justify-between gap-2 text-[13px]">
                                <x-ui.priority :priority="$ticket->priority" />
                                <span class="text-xs text-muted">{{ $ticket->finishedAt()?->format('d.m H:i') }}</span>
                            </div>
                        </a>
                    @endforeach
                @empty
                    <div class="px-4 py-10 text-center text-muted">{{ __('Arxivda murojaat topilmadi.') }}</div>
                @endforelse
                @if ($tickets->hasPages())
                    <div class="border-t border-line px-3 py-2.5">{{ $tickets->onEachSide(1)->links() }}</div>
                @endif
            </div>

            <div class="ui-split__side grid gap-3">
                @if ($selected)
                    @php([$resultLabel, $resultTone] = $selected->resultLabel())
                    <div class="ui-card">
                        <div class="flex items-center justify-between gap-2">
                            <span class="ui-mono">{{ $selected->reference }}</span>
                            <x-ticket-status-badge :status="$selected->status" />
                        </div>
                        <h3 class="mb-3 mt-2 font-display text-[20px] font-semibold leading-[26px]">{{ $subjectOf($selected) }}</h3>
                        <dl class="ui-kv">
                            <dt>{{ __('Murojaatchi') }}</dt>
                            <dd>{{ $selected->requester_name }}</dd>
                            <dt>{{ __('Ijrochi') }}</dt>
                            <dd @class(['text-muted' => ! $selected->assignedExecutor])>{{ $selected->assignedExecutor?->name ?? __('Belgilanmagan') }}</dd>
                            <dt>{{ __('Davomiyligi') }}</dt>
                            <dd class="ui-mono">{{ $selected->resolutionLabel() }}</dd>
                            <dt>{{ __('Natija') }}</dt>
                            <dd><span class="status-badge status--{{ $resultTone }}">{{ $resultLabel }}</span></dd>
                            @if ($selected->rejection_reason)
                                <dt>{{ __('Rad sababi') }}</dt>
                                <dd>{{ $selected->rejection_reason }}</dd>
                            @endif
                        </dl>
                        <a href="{{ route('admin.dispatch.show', ['ticket' => $selected, 'source' => 'archive']) }}" class="btn btn-secondary mt-3.5">{{ __('Batafsil') }}</a>
                    </div>

                    <div class="ui-card">
                        <h3 class="ui-card__title mb-3">{{ __('Holat tarixi') }}</h3>
                        @if ($selected->histories->isNotEmpty())
                            <ol class="ui-timeline">
                                <li>
                                    <b>{{ __('Yangi') }}</b>
                                    <span>{{ $selected->created_at?->format('d.m H:i') }} · {{ $selected->requester_name }}</span>
                                </li>
                                @foreach ($selected->histories->sortBy('created_at') as $history)
                                    @if ($history->to_status && $history->to_status !== $history->from_status)
                                        <li @class(['is-last' => $loop->last])>
                                            <b>{{ $history->to_status->label() }}</b>
                                            <span>{{ $history->created_at?->format('d.m H:i') }} · {{ $history->user?->name ?? __('Tizim') }}</span>
                                        </li>
                                    @endif
                                @endforeach
                            </ol>
                        @else
                            <p class="text-[13px] text-muted">{{ __('Tarix yozilmagan.') }}</p>
                        @endif
                    </div>
                @else
                    <div class="ui-card py-6 text-center">
                        <p class="ui-card__title">{{ __('Murojaat tanlanmagan') }}</p>
                        <p class="ui-card__sub mt-1">{{ __('Ro\'yxatdan murojaatni bosing.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

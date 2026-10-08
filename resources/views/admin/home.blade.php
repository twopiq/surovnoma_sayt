@php
    $query = request()->except(['ticket', 'page']);
    $slaOf = function ($ticket) {
        $card = new \App\View\Components\TicketCard($ticket);

        return ['pill' => $ticket->deadline_at ? $card->pill : '—', 'late' => $card->tone === 'late', 'done' => in_array($card->tone, ['done', 'rej'], true), 'subject' => $card->subject];
    };
    $assignable = collect($priorities)->map->value->all();
    $defaultPriority = old('priority', $selected && in_array($selected->priority->value, $assignable, true) ? $selected->priority->value : \App\Enums\TicketPriority::Medium->value);
    $overload = session('overload_warning');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2>{{ __('Murojaatlar boshqaruvi') }}</h2>
                <p class="pg-sub">{{ __('Ochiq murojaatlar, muddatlar va ijrochilarga tayinlash.') }}</p>
            </div>
            <form method="GET" action="{{ route('app.home') }}" class="flex flex-wrap gap-2">
                @if ($tab !== 'all')
                    <input type="hidden" name="tab" value="{{ $tab }}">
                @endif
                <label class="sr-only" for="home-search">{{ __('Qidirish') }}</label>
                <input id="home-search" name="q" value="{{ $search }}" placeholder="{{ __('Raqam yoki mavzu bo\'yicha qidirish') }}" class="ui-input w-full sm:w-[260px]">
                <a href="{{ route('admin.dispatch.export', ['format' => 'csv']) }}" class="btn btn-secondary">{{ __('CSV eksport') }}</a>
            </form>
        </div>
    </x-slot>

    <div class="mx-auto max-w-none px-4 pt-6 sm:px-6 lg:px-8">
        <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
            <div class="ui-card">
                <span class="dash-k__label">{{ __('Ochiq murojaatlar') }}</span>
                <span class="dash-k__value">{{ $stats['open'] }}</span>
                <span class="text-xs text-muted">{{ __('faol holatlarda') }}</span>
            </div>
            <div class="ui-card">
                <span class="dash-k__label">{{ __('Belgilanmagan') }}</span>
                <span class="dash-k__value">{{ $stats['unassigned'] }}</span>
                <span class="text-xs text-muted">{{ __('ijrochi kutilmoqda') }}</span>
            </div>
            <div class="ui-card">
                <span class="dash-k__label">{{ __('SLA xavfida') }}</span>
                <span class="dash-k__value">{{ $stats['at_risk'] }}</span>
                <span class="text-xs text-muted">{{ __('2 soatdan kam qoldi') }}</span>
            </div>
            <div class="ui-card">
                <span class="dash-k__label">{{ __('Kechikkan') }}</span>
                <span class="dash-k__value">{{ $stats['overdue'] }}</span>
                @if ($stats['overdue'] > 0)
                    <span class="status-badge status--new">{{ __('Kechikkan') }}</span>
                @else
                    <span class="text-xs text-muted">{{ __('kechikish yo\'q') }}</span>
                @endif
            </div>
        </div>

        <div class="mb-3 flex flex-wrap gap-2">
            @foreach ($tabs as $value => $label)
                <a href="{{ route('app.home', array_filter(['tab' => $value === 'all' ? null : $value, 'q' => $search ?: null])) }}" class="ui-chip" @if ($tab === $value) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
            <a href="{{ route('admin.dispatch.index') }}" class="ml-auto self-center text-[13px] text-muted hover:text-ink">{{ __('Doska ko\'rinishi →') }}</a>
        </div>

        <div class="ui-split" style="--split-side: 320px">
            <div class="ui-card overflow-x-auto !p-0">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>{{ __('Raqam') }}</th>
                            <th>{{ __('Mavzu') }}</th>
                            <th>{{ __('Holat') }}</th>
                            <th class="hidden md:table-cell">{{ __('Ustuvorlik') }}</th>
                            <th class="hidden lg:table-cell">{{ __('Ijrochi') }}</th>
                            <th>SLA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            @php($sla = $slaOf($ticket))
                            <tr @class(['is-selected' => $selected?->is($ticket)])>
                                <td class="ui-mono whitespace-nowrap">
                                    <a href="{{ route('app.home', array_merge(request()->query(), ['ticket' => $ticket->id])) }}" class="hover:text-accent hover:underline">{{ $ticket->reference }}</a>
                                </td>
                                <td class="max-w-[280px]">
                                    <a href="{{ route('app.home', array_merge(request()->query(), ['ticket' => $ticket->id])) }}" class="block truncate" title="{{ $sla['subject'] }}">{{ $sla['subject'] }}</a>
                                </td>
                                <td class="whitespace-nowrap"><x-ticket-status-badge :status="$ticket->status" /></td>
                                <td class="hidden md:table-cell"><x-ui.priority :priority="$ticket->priority" /></td>
                                <td @class(['hidden whitespace-nowrap lg:table-cell', 'text-muted' => ! $ticket->assignedExecutor])>{{ $ticket->assignedExecutor?->name ?? __('Belgilanmagan') }}</td>
                                <td @class(['ui-mono whitespace-nowrap', 'text-red-700' => $sla['late'], 'text-muted' => $sla['done']])>{{ $sla['done'] ? '—' : $sla['pill'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-10 text-center text-muted">{{ __('Murojaat topilmadi.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($tickets->hasPages())
                    <div class="border-t border-line px-3 py-2.5">{{ $tickets->onEachSide(1)->links() }}</div>
                @endif
            </div>

            <aside class="ui-card ui-split__side">
                @if ($selected)
                    <h3 class="font-display text-[17px] font-semibold leading-6">{{ __('Tayinlash') }}</h3>
                    <p class="mb-3 text-xs text-muted">
                        <span class="ui-mono text-xs">{{ $selected->reference }}</span> · {{ $selected->priority->label() }} · {{ $selected->assignedExecutor?->name ?? __('Belgilanmagan') }}
                    </p>

                    @if ($executors->isEmpty())
                        <p class="ui-note">
                            @if (in_array($selected->status, [\App\Enums\TicketStatus::Completed, \App\Enums\TicketStatus::Closed, \App\Enums\TicketStatus::Rejected, \App\Enums\TicketStatus::Cancelled], true))
                                {{ __('Bu murojaat yakunlangan — tayinlash shart emas.') }}
                            @else
                                {{ __('Faol ijrochi topilmadi.') }}
                            @endif
                        </p>
                        <a href="{{ route('admin.dispatch.show', $selected) }}" class="btn btn-secondary mt-3 w-full">{{ __('Murojaatni ochish') }}</a>
                    @else
                        <form method="POST" action="{{ route('admin.dispatch.assign', $selected) }}" class="grid gap-2">
                            @csrf
                            @if ($selected->category_id)
                                <input type="hidden" name="category_id" value="{{ $selected->category_id }}">
                            @endif
                            @if ($selected->assigned_department_id)
                                <input type="hidden" name="assigned_department_id" value="{{ $selected->assigned_department_id }}">
                            @endif

                            <fieldset class="grid min-w-0 gap-2">
                                <legend class="sr-only">{{ __('Ijrochi') }}</legend>
                                @foreach ($executors as $executor)
                                    <label @class(['flex min-w-0 cursor-pointer items-center justify-between gap-3 rounded-md border border-line px-3 py-2.5 has-[:checked]:border-accent has-[:checked]:bg-accent-soft', 'opacity-60' => $executor['vacation']])>
                                        <span class="flex min-w-0 flex-1 items-center gap-2">
                                            <input type="radio" name="assigned_executor_id" value="{{ $executor['id'] }}" class="shrink-0" @checked((string) old('assigned_executor_id', $selected->assigned_executor_id) === (string) $executor['id']) @disabled($executor['vacation']) required>
                                            <span class="min-w-0 flex-1">
                                                <b class="block truncate text-[14px] font-medium">{{ $executor['name'] }}</b>
                                                <span class="block text-xs text-muted">{{ $executor['vacation'] ? __("Ta'tilda") : $executor['used'].' / '.$maxUnits.' '.__('birlik') }}</span>
                                            </span>
                                        </span>
                                        <span class="dash-bar__track w-[72px] shrink-0 !h-1.5" aria-hidden="true"><i style="width: {{ $executor['percent'] }}%; background: rgb(var(--c-{{ $executor['percent'] >= 90 ? 'status-new-dot' : 'accent' }}))"></i></span>
                                    </label>
                                @endforeach
                            </fieldset>
                            <x-input-error :messages="$errors->get('assigned_executor_id')" />

                            <label class="ui-field-label mt-1" for="assign-priority">{{ __('Muhimlik') }}</label>
                            <select id="assign-priority" name="priority" class="ui-input">
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority->value }}" @selected($defaultPriority === $priority->value)>{{ $priority->label() }}</option>
                                @endforeach
                            </select>

                            @if ($overload)
                                <div class="ui-note !bg-orange-50 !text-orange-900">
                                    <span>
                                        <b>{{ __('Yuklama limitdan oshadi.') }}</b>
                                        {{ $overload['executor'] }}: {{ $overload['used_units'] }} + {{ $overload['new_units'] }} = {{ $overload['total_units'] }} / {{ $overload['max_units'] }} {{ __('birlik') }}.
                                    </span>
                                </div>
                                <input type="hidden" name="confirm_overload" value="1">
                                <input type="hidden" name="confirmed_overload_executor_id" value="{{ old('assigned_executor_id') }}">
                                <input type="hidden" name="confirmed_overload_priority" value="{{ old('priority') }}">
                            @endif

                            <div class="mt-2 flex gap-2">
                                <button type="submit" class="btn btn-primary">{{ $overload ? __('Baribir tayinlash') : __('Tayinlash') }}</button>
                                <a href="{{ route('app.home', $query) }}" class="btn btn-secondary">{{ __('Bekor qilish') }}</a>
                            </div>
                            <a href="{{ route('admin.dispatch.show', $selected) }}" class="mt-1 text-[13px] text-muted hover:text-ink">{{ __('Murojaatni to\'liq ochish →') }}</a>
                        </form>
                    @endif
                @else
                    <div class="py-6 text-center">
                        <p class="ui-card__title">{{ __('Murojaat tanlanmagan') }}</p>
                        <p class="ui-card__sub mt-1">{{ __('Jadvaldan murojaatni bosing.') }}</p>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</x-app-layout>

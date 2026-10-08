@php
    use App\Enums\TicketStatus;

    $ticket = $ticket ?? null;
    $steps = ['Yangi', 'Taqsimlandi', 'Jarayonda', 'Bajarildi', 'Yopildi'];

    if ($ticket) {
        $current = match ($ticket->status) {
            TicketStatus::New => 0,
            TicketStatus::Assigned, TicketStatus::Returned => 1,
            TicketStatus::InProgress, TicketStatus::Overdue => 2,
            TicketStatus::Completed => 3,
            TicketStatus::Closed => 5,
            TicketStatus::Rejected, TicketStatus::Cancelled => 1,
        };
        $rejected = in_array($ticket->status, [TicketStatus::Rejected, TicketStatus::Cancelled], true);
        $subject = (new \App\View\Components\TicketCard($ticket))->subject;
    }
@endphp

<x-public-layout :title="__('Holatni kuzatish')">
    <div class="grid items-start gap-6 pt-4 lg:grid-cols-[320px_minmax(0,1fr)]">
        <form method="POST" action="{{ route('guest.lookup') }}" class="rounded-3xl border border-line bg-surface p-5">
            @csrf
            <h1 class="mb-3.5 text-base font-semibold">{{ __('Murojaatni topish') }}</h1>
            <div>
                <label class="ui-field-label" for="reference">{{ __('Ticket ID') }}</label>
                <input id="reference" name="reference" value="{{ old('reference', $ticket?->reference) }}" required placeholder="RTT-20261008-0001" class="pub-input font-mono" autocomplete="off">
                <x-input-error :messages="$errors->get('reference')" class="mt-1" />
            </div>
            <div class="mt-4">
                <label class="ui-field-label" for="tracking_code">{{ __('Tracking code') }}</label>
                <input id="tracking_code" name="tracking_code" type="password" value="{{ old('tracking_code') }}" required class="pub-input font-mono" autocomplete="off">
                <x-input-error :messages="$errors->get('tracking_code')" class="mt-1" />
            </div>
            <button type="submit" class="btn btn-primary mt-4 w-full">{{ __('Kuzatish') }}</button>
            <p class="ui-note mt-4">{{ __('Kodni yo\'qotgan bo\'lsangiz, RTT markaziga murojaat qiling.') }}</p>
            <a href="{{ route('guest.create') }}" class="mt-4 inline-block text-sm font-semibold text-accent-strong hover:underline">{{ __('Yangi murojaat yuborish →') }}</a>
        </form>

        @if ($ticket)
            <div class="grid gap-4">
                <section class="rounded-3xl border border-line bg-surface p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-xs text-muted">{{ __('Ticket ID') }}</div>
                            <div class="font-mono text-xl font-medium leading-7">{{ $ticket->reference }}</div>
                        </div>
                        <x-ticket-status-badge :status="$ticket->status" />
                    </div>
                    <h2 class="mb-1 mt-3.5 text-base font-semibold">{{ $subject }}</h2>
                    <p class="mb-5 text-sm text-muted">
                        {{ $ticket->category?->name ?? __('Kategoriyasiz') }}
                        · {{ __('Yuborilgan') }}: {{ $ticket->created_at?->format('d.m.Y H:i') }}
                        @if ($ticket->deadline_at)
                            · {{ __('Muddat') }}: {{ $ticket->deadline_at->format('d.m.Y H:i') }}
                        @endif
                    </p>

                    <ol class="pub-steps m-0 p-0" aria-label="{{ __('Murojaat bosqichlari') }}">
                        @foreach ($steps as $i => $step)
                            @php
                                $state = match (true) {
                                    $rejected && $i === $current => 'is-stopped',
                                    $i < $current => 'is-done',
                                    $i === $current => 'is-current',
                                    default => '',
                                };
                            @endphp
                            <li class="{{ $state }}" @if ($state === 'is-current') aria-current="step" @endif>
                                <i>{!! $state === 'is-done' ? '&#10003;' : ($state === 'is-stopped' ? '&times;' : $i + 1) !!}</i>
                                {{ $state === 'is-stopped' ? $ticket->status->label() : __($step) }}
                            </li>
                        @endforeach
                    </ol>

                    @if ($ticket->status === TicketStatus::Rejected && $ticket->rejection_reason)
                        <p class="ui-note mt-5 !bg-red-50 !text-red-800"><span><b>{{ __('Rad etish sababi:') }}</b> {{ $ticket->rejection_reason }}</span></p>
                    @endif
                </section>

                <div class="grid gap-4 md:grid-cols-2">
                    <section class="rounded-3xl border border-line bg-surface p-5 sm:p-6">
                        <h3 class="mb-3 text-base font-semibold">{{ __('Ma\'lumotlar') }}</h3>
                        <ul class="pub-list text-sm">
                            <li><span class="w-24 shrink-0 text-muted">{{ __('Kategoriya') }}</span>{{ $ticket->category?->name ?? '—' }}</li>
                            <li><span class="w-24 shrink-0 text-muted">{{ __('Bo\'lim') }}</span>{{ $ticket->assignedDepartment?->name ?? '—' }}</li>
                            <li><span class="w-24 shrink-0 text-muted">{{ __('Ijrochi') }}</span>{{ $ticket->assignedExecutor?->name ?? __('Hali tayinlanmagan') }}</li>
                        </ul>
                    </section>
                    <section class="rounded-3xl border border-line bg-surface p-5 sm:p-6">
                        <h3 class="mb-3.5 text-base font-semibold">{{ __('Izohlar') }}</h3>
                        @if ($ticket->comments->isNotEmpty())
                            <div class="pub-feed">
                                @foreach ($ticket->comments as $comment)
                                    <div>
                                        <b>{{ $comment->user?->display_role ?? __('RTT markazi') }}</b>
                                        {{ \App\Support\StoredText::translate($comment->body) }}
                                        <small>{{ $comment->created_at?->format('d.m H:i') }}</small>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-muted">{{ __('Hozircha izoh yo\'q.') }}</p>
                        @endif
                    </section>
                </div>
            </div>
        @else
            <section class="rounded-3xl border border-dashed border-line-strong p-8 text-center">
                <h2 class="text-base font-semibold">{{ __('Murojaat holatini ko\'rish') }}</h2>
                <p class="mx-auto mt-1 max-w-md text-sm text-muted">{{ __('Murojaat yuborilganda berilgan Ticket ID va maxfiy tracking code\'ni kiriting. Holat, bosqichlar va izohlar shu yerda chiqadi.') }}</p>
            </section>
        @endif
    </div>
</x-public-layout>

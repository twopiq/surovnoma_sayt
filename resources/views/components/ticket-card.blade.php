<article {{ $attributes->class(['tk-card']) }}>
    <div class="tk-card__strip tk-tone--{{ $tone }}">
        <span class="tk-card__pill">
            @if ($tone === 'done')
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.5"/></svg>
            @elseif ($tone === 'rej')
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/></svg>
            @else
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            @endif
            {{ $pill }}
        </span>
        <span class="tk-card__deadline">{{ __('Tugash') }} {{ $ticket->deadline_at ? $ticket->deadlineLabel() : __('belgilanmagan') }}</span>
    </div>

    <div class="tk-card__body">
        <div class="tk-card__row">
            <span class="tk-card__ref">{{ $ticket->reference }}</span>
            <span class="tk-card__chips">
                @if ($showStatus)
                    <x-ticket-status-badge :status="$ticket->status" />
                @endif
                <span class="tk-chip">{{ $ticket->category?->name ?? __('Kategoriyasiz') }}</span>
            </span>
        </div>
        <h3 class="tk-card__subject">{{ $subject }}</h3>
        <span class="tk-card__name">{{ $ticket->requester_name }}</span>
        <span class="tk-card__meta">
            {{ __('Qabul') }} {{ $ticket->receivedAtLabel() }} · {{ __('Muddat') }} {{ $ticket->slaDurationLabel() }}
            @if ($ticket->assignedExecutor)
                · {{ __('Ijrochi') }}: {{ $ticket->assignedExecutor->name }}
            @endif
        </span>
    </div>

    @if ($filesTotal > 0)
        <div class="tk-card__files" title="{{ $filesTitle() }}">
            <span class="tk-card__files-total">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.4 11.6l-9.2 9.2a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5"/></svg>
                {{ __(':n ta fayl', ['n' => $filesTotal]) }}
            </span>
            @foreach ($files as $file)
                <span class="tk-file tk-file--{{ $file['type'] }}">
                    @if ($file['type'] === 'img')
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                    @else
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h6"/></svg>
                    @endif
                    {{ $file['text'] }}
                </span>
            @endforeach
        </div>
    @endif
</article>

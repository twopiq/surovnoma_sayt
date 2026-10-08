@php
    /*
     * Yo'l ko'rsatkich (breadcrumb): faqat ichki sahifalarda — "Ota sahifa › Joriy sahifa".
     * Yuqori darajadagi sahifalarda chiqmaydi (yon menyu yetarli). Ota sahifa doim shu foydalanuvchi
     * kira oladigan route (joriy sahifa bilan bir middleware guruhida), murojaat qayerdan ochilgan bo'lsa — o'sha joy.
     */
    $routeTicket = request()->route('ticket');
    $ticketLabel = $routeTicket instanceof \App\Models\Ticket ? $routeTicket->reference : 'Murojaat';
    $source = request()->query('source');
    $crumbs = [];

    if (request()->routeIs('tickets.show', 'tickets.create')) {
        $crumbs = [
            ['Murojaatlarim', route('tickets.index')],
            [request()->routeIs('tickets.create') ? 'Yangi murojaat' : $ticketLabel, null],
        ];
    } elseif (request()->routeIs('operator.tickets.show', 'operator.tickets.create')) {
        $crumbs = [
            ['Operator murojaatlari', route('operator.tickets.index')],
            [request()->routeIs('operator.tickets.create') ? 'Yangi murojaat' : $ticketLabel, null],
        ];
    } elseif (request()->routeIs('executor.tickets.archive')) {
        $crumbs = [
            ['Mening vazifalarim', route('executor.tickets.index')],
            ['Arxiv', null],
        ];
    } elseif (request()->routeIs('executor.tickets.show')) {
        $crumbs = [
            match ($source) {
                'archive' => ['Arxiv', route('executor.tickets.archive')],
                'home' => ['Bosh sahifa', route('app.home')],
                default => ['Mening vazifalarim', route('executor.tickets.index')],
            },
            [$ticketLabel, null],
        ];
    } elseif (request()->routeIs('admin.dispatch.show')) {
        $crumbs = [
            match ($source) {
                'archive' => ['Arxiv', route('admin.dispatch.archive')],
                'home' => ['Bosh sahifa', route('app.home')],
                'board' => ['Doska', route('admin.dispatch.index')],
                default => ['Murojaatlar', route('admin.dispatch.tickets')],
            },
            [$ticketLabel, null],
        ];
    } elseif (request()->routeIs('admin.dispatch.status')) {
        $status = \App\Enums\TicketStatus::tryFrom((string) request()->route('status'));
        $crumbs = [
            ['Doska', route('admin.dispatch.index')],
            [$status?->label() ?? 'Holat', null],
        ];
    } elseif (request()->routeIs('admin.dispatch.index')) {
        $crumbs = [
            ['Murojaatlar', route('admin.dispatch.tickets')],
            ['Doska', null],
        ];
    }
@endphp

@if ($crumbs !== [])
    <nav class="pg-crumb mb-1" aria-label="Yo'l ko'rsatkich">
        <ol class="flex flex-wrap items-center gap-1.5">
            @foreach ($crumbs as [$label, $url])
                <li class="flex items-center gap-1.5">
                    @if (! $loop->first)
                        <span aria-hidden="true">›</span>
                    @endif
                    @if ($url && ! $loop->last)
                        <a href="{{ $url }}" class="hover:text-ink hover:underline">{{ $label }}</a>
                    @else
                        <span @class(['text-ink' => $loop->last]) @if ($loop->last) aria-current="page"@endif>{{ $label }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif

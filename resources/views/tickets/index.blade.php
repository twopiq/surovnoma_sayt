<x-app-layout>
    @php
        $statusTotal = fn (\App\Enums\TicketStatus ...$statuses): int => collect($statuses)
            ->sum(fn (\App\Enums\TicketStatus $status): int => (int) ($statusCounts[$status->value] ?? 0));
        $summaryCards = [
            [
                'label' => 'Jami',
                'value' => $totalTickets,
                'tone' => 'text-slate-900',
            ],
            [
                'label' => 'Ko\'rib chiqilmoqda',
                'value' => $statusTotal(\App\Enums\TicketStatus::New, \App\Enums\TicketStatus::Assigned),
                'tone' => 'text-cyan-800',
            ],
            [
                'label' => 'Jarayonda',
                'value' => $statusTotal(\App\Enums\TicketStatus::InProgress, \App\Enums\TicketStatus::Returned),
                'tone' => 'text-orange-700',
            ],
            [
                'label' => 'Yakunlangan',
                'value' => $statusTotal(\App\Enums\TicketStatus::Completed, \App\Enums\TicketStatus::Closed),
                'tone' => 'text-emerald-700',
            ],
        ];
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-['Space_Grotesk'] text-2xl font-bold text-slate-900">Murojaatlarim</h2>
                <p class="mt-1 text-sm text-slate-500">Yuborilgan murojaatlar holati va yangi murojaat yaratish.</p>
            </div>
            <a href="{{ route('tickets.create') }}" class="inline-flex items-center justify-center rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-cyan-800">Yangi murojaat</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($summaryCards as $card)
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-slate-500">{{ $card['label'] }}</div>
                    <div class="mt-3 font-['Space_Grotesk'] text-3xl font-bold {{ $card['tone'] }}">{{ $card['value'] }}</div>
                </div>
            @endforeach
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-slate-900">Murojaatlar ro'yxati</h3>
                <span class="text-sm text-slate-500">{{ $tickets->total() }} ta</span>
            </div>

            @forelse ($tickets as $ticket)
                <a href="{{ route('tickets.show', $ticket) }}" class="block">@include('partials.ticket-card', ['ticket' => $ticket])</a>
            @empty
                <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center">
                    <h3 class="font-semibold text-slate-900">Murojaat hali yo'q</h3>
                    <p class="mt-2 text-sm text-slate-500">Yangi murojaat yuborilgandan keyin holati shu yerda ko'rinadi.</p>
                    <a href="{{ route('tickets.create') }}" class="mt-4 inline-flex rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-cyan-800">Yangi murojaat</a>
                </div>
            @endforelse
        </section>

        {{ $tickets->links() }}
    </div>
</x-app-layout>

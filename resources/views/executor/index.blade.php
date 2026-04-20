<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Mening vazifalarim</h2>
            <a href="{{ route('executor.tickets.archive') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Arxiv</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="font-semibold text-slate-900">Joriy yuklama</h3>
            <p class="mt-2 text-sm text-slate-600">
                Ishlatilgan yuklama: {{ $workloadSummary['used_units'] }}/{{ $workloadSummary['max_units'] }} birlik.
                Qolgan yuklama: {{ $workloadSummary['remaining_units'] }} birlik.
            </p>
            @if ($workloadSummary['overload_units'] > 0)
                <div class="mt-4 rounded-2xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-900">
                    <div class="font-semibold">Ortiqcha yuklama bor</div>
                    <p class="mt-1">
                        Sizda limitdan {{ $workloadSummary['overload_units'] }} birlik ko'p faol ish bor.
                        Admin tomonidan tasdiqlangan ortiqcha yuklama shu yerda ko'rinadi.
                    </p>
                </div>
            @endif
            <p class="mt-2 text-xs text-slate-400">
                Limit: 1 ta shoshilinch va 1 ta past, yoki 2 ta yuqori, yoki 3 ta o'rta, yoki 5 ta past topshiriq.
            </p>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-slate-900">Menga biriktirilgan murojaatlar</h3>
                <span class="text-sm text-slate-500">{{ $myTickets->total() }} ta</span>
            </div>

            @forelse ($myTickets as $ticket)
                <a href="{{ route('executor.tickets.show', $ticket) }}" class="block">@include('partials.ticket-card', ['ticket' => $ticket])</a>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">Sizga biriktirilgan murojaatlar hozircha yo'q.</div>
            @endforelse

            {{ $myTickets->links() }}
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-slate-900">Bo'sh murojaatlar</h3>
                <span class="text-sm text-slate-500">{{ $availableTickets->total() }} ta</span>
            </div>

            @forelse ($availableTickets as $ticket)
                <a href="{{ route('executor.tickets.show', $ticket) }}" class="block">
                    <div class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-cyan-100">Tanlash uchun ochiq</div>
                    @include('partials.ticket-card', ['ticket' => $ticket])
                </a>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">Hozircha bo'sh murojaatlar mavjud emas.</div>
            @endforelse

            {{ $availableTickets->links() }}
        </div>
    </div>
</x-app-layout>

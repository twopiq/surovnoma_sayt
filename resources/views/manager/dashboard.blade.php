<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-700">Oylik hisobot</p>
                <h2 class="font-['Space_Grotesk'] text-2xl font-bold text-slate-900">Yakunlangan ishlar</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $monthLabel }} bo'yicha hisobot</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <form method="GET" action="{{ route('manager.dashboard') }}" class="flex flex-wrap items-center gap-2">
                    @if ($activeFilters['priority'] ?? null)
                        <input type="hidden" name="priority" value="{{ $activeFilters['priority'] }}">
                    @endif
                    <input
                        type="month"
                        name="month"
                        value="{{ $monthValue }}"
                        class="rounded-md border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                    />
                    <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                        Ko'rish
                    </button>
                </form>

                <a href="{{ $stats['all']['csv_url'] }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Excel CSV
                </a>
                <a href="{{ $stats['all']['json_url'] }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    JSON
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($stats as $stat)
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</div>
                    <div class="mt-2 font-['Space_Grotesk'] text-3xl font-bold text-slate-950">{{ number_format($stat['value']) }}</div>
                    <p class="mt-3 min-h-10 text-sm leading-5 text-slate-500">{{ $stat['description'] }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ $stat['csv_url'] }}" class="rounded-md bg-cyan-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-cyan-800">
                            CSV
                        </a>
                        <a href="{{ $stat['json_url'] }}" class="rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                            JSON
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="font-['Space_Grotesk'] text-lg font-bold text-slate-900">Prioritet bo'yicha</h3>
                    <p class="mt-1 text-sm text-slate-500">Har bir ustun tanlangan oy uchun alohida yuklab olinadi.</p>
                </div>

                @if ($activeFilters['priority'] ?? null)
                    <a href="{{ route('manager.dashboard', ['month' => $monthValue]) }}" class="text-sm font-semibold text-cyan-700 hover:text-cyan-900">
                        Prioritet filtrini tozalash
                    </a>
                @endif
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($byPriority as $priority)
                    <div class="rounded-lg bg-slate-50 p-4 ring-1 ring-slate-200">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm text-slate-500">{{ $priority['label'] }}</div>
                                <div class="mt-1 text-2xl font-bold text-slate-950">{{ number_format($priority['count']) }}</div>
                            </div>
                            <a href="{{ route('manager.dashboard', ['month' => $monthValue, 'priority' => $priority['value']]) }}" class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 transition hover:bg-white">
                                Filtr
                            </a>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ $priority['csv_url'] }}" class="rounded-md bg-cyan-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-cyan-800">
                                CSV
                            </a>
                            <a href="{{ $priority['json_url'] }}" class="rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white">
                                JSON
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h3 class="font-['Space_Grotesk'] text-lg font-bold text-slate-900">Yakunlangan ishlar ro'yxati</h3>
                <p class="mt-1 text-sm text-slate-500">Standart holatda joriy oy natijalari ko'rinadi.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Raqam</th>
                            <th class="px-6 py-3">Ish</th>
                            <th class="px-6 py-3">Ijrochi</th>
                            <th class="px-6 py-3">Prioritet</th>
                            <th class="px-6 py-3">Holat</th>
                            <th class="px-6 py-3">Yakunlangan vaqt</th>
                            <th class="px-6 py-3">SLA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($completedTickets as $ticket)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 font-semibold text-slate-900">{{ $ticket->reference }}</td>
                                <td class="min-w-72 px-6 py-4">
                                    <div class="font-medium text-slate-900">{{ \Illuminate\Support\Str::limit($ticket->title ?? $ticket->description, 80) }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $ticket->assignedDepartment?->name ?? 'Bo\'lim biriktirilmagan' }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-slate-600">{{ $ticket->assignedExecutor?->name ?? 'Biriktirilmagan' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 font-semibold text-cyan-700">{{ $ticket->priority->label() }}</td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <x-ticket-status-badge :status="$ticket->status" size="xs" />
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-slate-600">{{ $ticket->completed_at?->format('d.m.Y H:i') }}</td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if ($ticket->deadline_at && $ticket->completed_at?->greaterThan($ticket->deadline_at))
                                        <span class="rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700">Kechikkan</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Muddatida</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">
                                    Bu oy uchun yakunlangan ishlar hali yo'q.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($completedTickets->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $completedTickets->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

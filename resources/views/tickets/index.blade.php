<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-['Space_Grotesk'] text-2xl font-bold text-slate-900">Mening murojaatlarim</h2>
            <a href="{{ route('tickets.create') }}" class="rounded-full bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Yangi murojaat</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pt-8 sm:px-6 lg:px-8">
        @forelse ($tickets as $ticket)
            <a href="{{ route('tickets.show', $ticket) }}" class="block">@include('partials.ticket-card', ['ticket' => $ticket])</a>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">Murojaatlar mavjud emas.</div>
        @endforelse

        {{ $tickets->links() }}
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-display text-2xl font-bold">{{ __('Bajarilgan topshiriqlar arxivi') }}</h2>
            <a href="{{ route('executor.tickets.index') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">{{ __('Faol vazifalar') }}</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-none space-y-4 px-4 pt-8 sm:px-6 lg:px-8">
        @forelse ($tickets as $ticket)
            <a href="{{ route('executor.tickets.show', ['ticket' => $ticket, 'source' => 'archive']) }}" class="block">@include('partials.ticket-card', ['ticket' => $ticket])</a>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">{{ __('Bajarilgan topshiriqlar arxivi hozircha bo\'sh.') }}</div>
        @endforelse

        {{ $tickets->links() }}
    </div>
</x-app-layout>

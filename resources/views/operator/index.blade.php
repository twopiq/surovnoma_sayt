<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Operator paneli</h2>
            <a href="{{ route('operator.tickets.create') }}" class="rounded-full bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Boshqa xodim nomidan</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pt-8 sm:px-6 lg:px-8">
        @foreach ($tickets as $ticket)
            <a href="{{ route('operator.tickets.show', $ticket) }}" class="block">@include('partials.ticket-card', ['ticket' => $ticket])</a>
        @endforeach
        {{ $tickets->links() }}
    </div>
</x-app-layout>

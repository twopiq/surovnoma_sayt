<x-app-layout>
    <x-slot name="header">
        <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Mening vazifalarim</h2>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pt-8 sm:px-6 lg:px-8">
        @foreach ($tickets as $ticket)
            <a href="{{ route('executor.tickets.show', $ticket) }}" class="block">@include('partials.ticket-card', ['ticket' => $ticket])</a>
        @endforeach
        {{ $tickets->links() }}
    </div>
</x-app-layout>

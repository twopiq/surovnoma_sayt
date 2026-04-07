<x-app-layout>
    <x-slot name="header">
        <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Operator murojaati</h2>
    </x-slot>
    <div class="mx-auto max-w-5xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        @include('partials.ticket-card', ['ticket' => $ticket])
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <h3 class="font-semibold">Fayllar</h3>
            <div class="mt-4 space-y-2 text-sm text-slate-600">
                @forelse ($ticket->attachments as $attachment)
                    <div>{{ $attachment->original_name }}</div>
                @empty
                    <p class="text-slate-500">Fayl mavjud emas.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>

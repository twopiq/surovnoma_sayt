<x-guest-layout>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="font-['Space_Grotesk'] text-2xl font-bold">Guest kuzatuvi</h1>
            <p class="mt-2 text-sm text-slate-500">Murojaat holati va public izohlarni shu yerda ko'rishingiz mumkin.</p>
        </div>
        <a href="{{ route('home') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Home</a>
    </div>

    <div class="space-y-4">
        @include('partials.ticket-card', ['ticket' => $ticket])
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="font-semibold text-slate-900">Public izohlar</h2>
            <div class="mt-4 space-y-3">
                @forelse ($ticket->comments as $comment)
                    <div class="rounded-xl bg-slate-50 p-3 text-sm text-slate-600">{{ $comment->body }}</div>
                @empty
                    <p class="text-sm text-slate-500">Hozircha public izoh yo'q.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-guest-layout>

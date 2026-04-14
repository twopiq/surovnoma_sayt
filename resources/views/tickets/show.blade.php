<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('tickets.index') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Ortga qaytish</a>
            <h2 class="font-['Space_Grotesk'] text-2xl font-bold text-slate-900">Murojaat kartochkasi</h2>
        </div>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 pt-8 lg:grid-cols-[1.4fr_0.8fr] sm:px-6 lg:px-8">
        <div class="space-y-6">
            @include('partials.ticket-card', ['ticket' => $ticket])
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <h3 class="font-semibold text-slate-900">Public izoh qo‘shish</h3>
                <form method="POST" action="{{ route('tickets.comment', $ticket) }}" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="body" rows="4" class="block w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" required>{{ old('body') }}</textarea>
                    <x-primary-button class="bg-cyan-700 hover:bg-cyan-800">Izoh yuborish</x-primary-button>
                </form>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <h3 class="font-semibold text-slate-900">Public izohlar</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($ticket->comments as $comment)
                        <div class="rounded-xl bg-slate-50 p-3 text-sm text-slate-600">{{ $comment->body }}</div>
                    @empty
                        <p class="text-sm text-slate-500">Hozircha izoh yo‘q.</p>
                    @endforelse
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <h3 class="font-semibold text-slate-900">Biriktirilgan fayllar</h3>
                <div class="mt-4 space-y-2 text-sm text-slate-600">
                    @forelse ($ticket->attachments as $attachment)
                        <div>{{ $attachment->original_name }}</div>
                    @empty
                        <p class="text-slate-500">Fayl biriktirilmagan.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

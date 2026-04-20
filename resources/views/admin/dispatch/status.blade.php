<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-['Space_Grotesk'] text-2xl font-bold">{{ $status->label() }} murojaatlar</h2>
                <p class="mt-1 text-sm text-slate-500">Tanlangan holatdagi barcha murojaatlar.</p>
            </div>
            <a href="{{ route('admin.dispatch.index') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                Doskaga qaytish
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-none space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        @include('admin.dispatch.partials.top-menu')

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('admin.dispatch.status', ['status' => $status->value]) }}" class="grid gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                <select name="priority" class="rounded-md border-slate-300 shadow-sm">
                    <option value="">Barcha muhimliklar</option>
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority->value }}" @selected(request('priority') === $priority->value)>{{ $priority->label() }}</option>
                    @endforeach
                </select>
                <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filtrlash</button>
                <a href="{{ route('admin.dispatch.status', ['status' => $status->value]) }}" class="rounded-md border border-slate-300 px-4 py-2 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Tozalash</a>
            </form>
        </div>

        <section class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="inline-flex rounded-md px-2 py-1 text-sm font-semibold ring-1" style="{{ $status->badgeStyle() }}">{{ $status->label() }}</span>
                    <span class="text-sm font-semibold text-slate-500">{{ $tickets->total() }} ta</span>
                </div>
            </div>

            @forelse ($tickets as $ticket)
                <a href="{{ route('admin.dispatch.show', ['ticket' => $ticket, 'source' => 'board']) }}" class="block">
                    @include('partials.ticket-card', ['ticket' => $ticket])
                </a>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">Bu holatda murojaatlar hozircha yo'q.</div>
            @endforelse

            {{ $tickets->links() }}
        </section>
    </div>
</x-app-layout>

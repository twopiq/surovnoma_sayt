<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Murojaatlar arxivi</h2>
                <p class="mt-1 text-sm text-slate-500">Bajarilgan va yopilgan murojaatlar shu yerda saqlanadi.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.dispatch.export', array_merge(request()->query(), ['archive' => 1, 'format' => 'excel'])) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                    Excel eksport
                </a>
                <a href="{{ route('admin.dispatch.export', array_merge(request()->query(), ['archive' => 1, 'format' => 'csv'])) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                    CSV
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        @include('admin.dispatch.partials.top-menu')

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('admin.dispatch.archive') }}" class="grid gap-3 md:grid-cols-[1fr_1fr_auto_auto] md:items-center">
                <select name="status" class="rounded-md border-slate-300 shadow-sm">
                    <option value="">Barcha arxiv holatlari</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>

                <select name="priority" class="rounded-md border-slate-300 shadow-sm">
                    <option value="">Barcha muhimliklar</option>
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority->value }}" @selected(request('priority') === $priority->value)>{{ $priority->label() }}</option>
                    @endforeach
                </select>

                <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filtrlash</button>
                <a href="{{ route('admin.dispatch.archive') }}" class="rounded-md border border-slate-300 px-4 py-2 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Tozalash</a>
            </form>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-slate-900">Arxivdagi murojaatlar</h3>
                <span class="text-sm text-slate-500">{{ $tickets->total() }} ta</span>
            </div>

            @forelse ($tickets as $ticket)
                <a href="{{ route('admin.dispatch.show', ['ticket' => $ticket, 'source' => 'archive']) }}" class="block">@include('partials.ticket-card', ['ticket' => $ticket])</a>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">Arxivda murojaatlar hozircha yo'q.</div>
            @endforelse

            {{ $tickets->links() }}
        </div>
    </div>
</x-app-layout>

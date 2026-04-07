<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Dispetcher paneli</h2>
            <a href="{{ route('admin.dispatch.export', request()->query()) }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">CSV eksport</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        <livewire:admin.dispatch-board />

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" class="grid gap-3 md:grid-cols-4">
                <input name="status" value="{{ request('status') }}" placeholder="Status" class="rounded-md border-slate-300 shadow-sm" />
                <input name="priority" value="{{ request('priority') }}" placeholder="Priority" class="rounded-md border-slate-300 shadow-sm" />
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="overdue" value="1" @checked(request()->boolean('overdue'))>
                    Faqat kechikkanlar
                </label>
                <button class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filtrlash</button>
            </form>
        </div>

        <div class="space-y-4">
            @foreach ($tickets as $ticket)
                <a href="{{ route('admin.dispatch.show', $ticket) }}" class="block">@include('partials.ticket-card', ['ticket' => $ticket])</a>
            @endforeach
            {{ $tickets->links() }}
        </div>
    </div>
</x-app-layout>

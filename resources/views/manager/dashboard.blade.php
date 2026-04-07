<x-app-layout>
    <x-slot name="header">
        <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Rahbar dashboard</h2>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($summary as $label => $value)
                <div class="rounded-2xl bg-white p-5 shadow-sm">
                    <div class="text-sm text-slate-500">{{ ucfirst($label) }}</div>
                    <div class="mt-2 font-['Space_Grotesk'] text-3xl font-bold">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="font-semibold">Prioritet bo‘yicha taqsimot</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($byPriority as $priority => $count)
                    <div class="rounded-xl bg-slate-50 p-4">
                        <div class="text-sm text-slate-500">{{ $priority }}</div>
                        <div class="mt-1 text-2xl font-bold">{{ $count }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>

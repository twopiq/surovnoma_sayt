<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Deadline sozlamalari</h2>
            <p class="mt-1 text-sm text-slate-500">Muhimlik darajasiga qarab murojaat bajarilish muddatini belgilang.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        @include('admin.dispatch.partials.top-menu')

        <form method="POST" action="{{ route('admin.dispatch.deadlines.update') }}" class="space-y-6">
            @csrf
            @method('PUT')
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-1 border-b border-slate-100 pb-4">
                    <h3 class="font-semibold text-slate-950">Muhimlik bo'yicha deadline</h3>
                    <p class="text-sm text-slate-500">1 ish kuni hozirgi ish kalendari bo'yicha {{ round($workdayMinutes / 60, 2) }} soat deb olinadi.</p>
                </div>

                <div class="mt-4 space-y-3">
                    @foreach ($priorities as $index => $priority)
                        @php($profile = $profiles->firstWhere('priority', $priority->value))
                        @php($deadlineDays = $profile ? round($profile->duration_minutes / $workdayMinutes, 2) : 1)
                        <div class="grid gap-3 rounded-lg border border-slate-100 bg-slate-50 p-3 md:grid-cols-[1.2fr_1fr_1fr_1fr] md:items-center">
                            <input type="hidden" name="profiles[{{ $index }}][priority]" value="{{ $priority->value }}">
                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Nomi</span>
                                <input name="profiles[{{ $index }}][name]" value="{{ $profile->name ?? $priority->label() }}" class="w-full rounded-md border-slate-300 shadow-sm" />
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Deadline kuni</span>
                                <input type="number" step="0.01" min="0.01" name="profiles[{{ $index }}][deadline_days]" value="{{ old("profiles.$index.deadline_days", $deadlineDays) }}" class="w-full rounded-md border-slate-300 shadow-sm" />
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Ogohlantirish (daq.)</span>
                                <input type="number" min="1" name="profiles[{{ $index }}][warning_minutes]" value="{{ $profile->warning_minutes ?? 30 }}" class="w-full rounded-md border-slate-300 shadow-sm" />
                            </label>
                            <div class="text-sm font-semibold text-slate-700">{{ $priority->label() }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex justify-end border-t border-slate-100 pt-4">
                    <button class="rounded-md bg-cyan-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-cyan-800">Saqlash</button>
                </div>
            </section>
        </form>
    </div>
</x-app-layout>

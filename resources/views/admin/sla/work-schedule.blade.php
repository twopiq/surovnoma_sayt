<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Ish kunlari</h2>
                <p class="mt-1 text-sm text-slate-500">Deadline hisobida ishlatiladigan ish kuni va ish vaqtini belgilang.</p>
            </div>
            <form method="POST" action="{{ route('admin.sla.bootstrap') }}">
                @csrf
                <button class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Standart 5/2 kalendar</button>
            </form>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        @include('admin.dispatch.partials.top-menu')

        <form method="POST" action="{{ route('admin.dispatch.work-schedule.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-1 border-b border-slate-100 pb-4">
                    <h3 class="font-semibold text-slate-950">Haftalik ish kalendari</h3>
                    <p class="text-sm text-slate-500">Shanba va yakshanba deadline hisobida dam olish kuni sifatida qabul qilinadi.</p>
                </div>

                <div class="mt-4 space-y-3">
                    @foreach ($schedules as $schedule)
                        <div class="grid gap-3 rounded-lg border border-slate-100 bg-slate-50 p-3 md:grid-cols-[1fr_1fr_1fr] md:items-center">
                            <label class="inline-flex items-center gap-3 text-sm font-semibold text-slate-700">
                                <input type="checkbox" name="schedule[{{ $schedule->weekday }}][is_working_day]" value="1" @checked($schedule->is_working_day) class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-600">
                                {{ $weekdayLabels[$schedule->weekday] ?? "Hafta kuni {$schedule->weekday}" }}
                            </label>

                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Boshlanish</span>
                                <input type="time" name="schedule[{{ $schedule->weekday }}][starts_at]" value="{{ $schedule->starts_at }}" class="w-full rounded-md border-slate-300 shadow-sm" />
                            </label>

                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tugash</span>
                                <input type="time" name="schedule[{{ $schedule->weekday }}][ends_at]" value="{{ $schedule->ends_at }}" class="w-full rounded-md border-slate-300 shadow-sm" />
                            </label>
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

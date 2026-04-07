<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-['Space_Grotesk'] text-2xl font-bold">SLA sozlamalari</h2>
            <form method="POST" action="{{ route('admin.sla.bootstrap') }}">
                @csrf
                <button class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Standart 5/2 kalendar</button>
            </form>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('admin.sla.update') }}" class="space-y-6">
            @csrf
            @method('PUT')
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold">SLA profillar</h3>
                <div class="mt-4 space-y-4">
                    @foreach ($priorities as $index => $priority)
                        @php($profile = $profiles->firstWhere('priority', $priority->value))
                        <div class="grid gap-3 md:grid-cols-4">
                            <input type="hidden" name="profiles[{{ $index }}][priority]" value="{{ $priority->value }}">
                            <input name="profiles[{{ $index }}][name]" value="{{ $profile->name ?? $priority->label() }}" class="rounded-md border-slate-300 shadow-sm" />
                            <input name="profiles[{{ $index }}][duration_minutes]" value="{{ $profile->duration_minutes ?? 60 }}" class="rounded-md border-slate-300 shadow-sm" />
                            <input name="profiles[{{ $index }}][warning_minutes]" value="{{ $profile->warning_minutes ?? 30 }}" class="rounded-md border-slate-300 shadow-sm" />
                            <div class="flex items-center text-sm text-slate-500">{{ $priority->label() }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold">Ish vaqti kalendari</h3>
                <div class="mt-4 space-y-3">
                    @foreach ($schedules as $schedule)
                        <div class="grid gap-3 md:grid-cols-4">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="schedule[{{ $schedule->weekday }}][is_working_day]" value="1" @checked($schedule->is_working_day)>
                                Hafta kuni {{ $schedule->weekday }}
                            </label>
                            <input type="time" name="schedule[{{ $schedule->weekday }}][starts_at]" value="{{ $schedule->starts_at }}" class="rounded-md border-slate-300 shadow-sm" />
                            <input type="time" name="schedule[{{ $schedule->weekday }}][ends_at]" value="{{ $schedule->ends_at }}" class="rounded-md border-slate-300 shadow-sm" />
                        </div>
                    @endforeach
                </div>
            </div>

            <button class="rounded-full bg-cyan-700 px-5 py-3 text-sm font-semibold text-white">Sozlamalarni saqlash</button>
        </form>
    </div>
</x-app-layout>

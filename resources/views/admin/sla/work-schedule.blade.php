@php
    $short = [1 => 'Dush', 2 => 'Sesh', 3 => 'Chor', 4 => 'Pay', 5 => 'Jum', 6 => 'Shan', 7 => 'Yak'];
    $minutesOf = function ($schedule) {
        if (! $schedule->is_working_day || ! $schedule->starts_at || ! $schedule->ends_at) {
            return 0;
        }

        [$sh, $sm] = array_map('intval', explode(':', substr($schedule->starts_at, 0, 5)));
        [$eh, $em] = array_map('intval', explode(':', substr($schedule->ends_at, 0, 5)));

        return max(0, ($eh * 60 + $em) - ($sh * 60 + $sm));
    };
    $days = $schedules->map(fn ($s) => [
        'weekday' => $s->weekday,
        'on' => (bool) $s->is_working_day,
        'start' => $s->starts_at ? substr($s->starts_at, 0, 5) : '09:00',
        'end' => $s->ends_at ? substr($s->ends_at, 0, 5) : '18:00',
    ])->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pg-crumb">Sozlamalar</div>
            <h2>Ish kunlari</h2>
            <p class="pg-sub">Deadline hisobida ishlatiladigan ish kuni va ish vaqtini belgilang.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-none px-4 pt-6 sm:px-6 lg:px-8"
         x-data="{
            days: @js($days),
            dirty: false,
            minutes(d) {
                if (!d.on || !d.start || !d.end) return 0;
                const [sh, sm] = d.start.split(':').map(Number);
                const [eh, em] = d.end.split(':').map(Number);
                return Math.max(0, eh * 60 + em - (sh * 60 + sm));
            },
            hours(m) { return Math.round(m / 6) / 10; },
            get workingDays() { return this.days.filter(d => d.on).length; },
            get weekMinutes() { return this.days.reduce((sum, d) => sum + this.minutes(d), 0); },
            get workdayMinutes() {
                const weekdays = this.days.filter(d => d.weekday <= 5 && d.on);
                return weekdays.length ? weekdays.reduce((sum, d) => sum + this.minutes(d), 0) / weekdays.length : 0;
            },
         }">
        @if ($schedules->isEmpty())
            <div class="ui-card text-center">
                <p class="ui-card__title">Ish kalendari hali yaratilmagan</p>
                <form method="POST" action="{{ route('admin.sla.bootstrap') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-primary">Standart 5/2 kalendarni yaratish</button>
                </form>
            </div>
        @else
            <form id="schedule-form" method="POST" action="{{ route('admin.dispatch.work-schedule.update') }}" @input="dirty = true" @change="dirty = true">
                @csrf
                @method('PUT')

                <div class="mb-3 grid grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-7">
                    <template x-for="(day, i) in days" :key="day.weekday">
                        <div class="ui-card p-3 text-center" :class="!day.on && '!border-dashed !bg-transparent'">
                            <div class="flex items-center justify-between">
                                <b x-text="@js($short)[day.weekday]"></b>
                                <input type="checkbox" class="ui-switch" value="1" :name="`schedule[${day.weekday}][is_working_day]`" x-model="day.on" :aria-label="`${@js($short)[day.weekday]}: ish kuni`">
                            </div>
                            <div class="mb-2.5 mt-1 text-xs text-muted" x-text="day.on ? 'Ish kuni' : 'Dam olish'"></div>
                            <div class="grid gap-1.5" :class="!day.on && 'opacity-40'">
                                <input type="time" class="ui-input ui-mono text-center" :name="`schedule[${day.weekday}][starts_at]`" x-model="day.start" aria-label="Boshlanish">
                                <input type="time" class="ui-input ui-mono text-center" :name="`schedule[${day.weekday}][ends_at]`" x-model="day.end" aria-label="Tugash">
                            </div>
                            <div class="ui-mono mt-2.5 !text-[18px]" x-text="day.on ? hours(minutes(day)) + ' s' : '—'"></div>
                        </div>
                    </template>
                </div>

                <noscript>
                    @foreach ($schedules as $schedule)
                        <div class="ui-card mb-2 grid grid-cols-3 items-center gap-2">
                            <label><input type="checkbox" name="schedule[{{ $schedule->weekday }}][is_working_day]" value="1" @checked($schedule->is_working_day)> {{ $weekdayLabels[$schedule->weekday] }}</label>
                            <input type="time" name="schedule[{{ $schedule->weekday }}][starts_at]" value="{{ $schedule->starts_at }}">
                            <input type="time" name="schedule[{{ $schedule->weekday }}][ends_at]" value="{{ $schedule->ends_at }}">
                        </div>
                    @endforeach
                </noscript>
            </form>

            <div class="grid gap-3 lg:grid-cols-2">
                <div class="ui-card">
                    <h3 class="ui-card__title">Haftalik xulosa</h3>
                    <p class="ui-card__sub mb-3">Deadline hisobi shu qiymatlardan foydalanadi</p>
                    <div class="flex flex-wrap gap-8">
                        <div><span class="dash-k__label">Ish kunlari</span><span class="dash-k__value" x-text="workingDays">{{ $schedules->where('is_working_day', true)->count() }}</span></div>
                        <div><span class="dash-k__label">Haftada soat</span><span class="dash-k__value" x-text="hours(weekMinutes)">{{ round($schedules->sum($minutesOf) / 60, 1) }}</span></div>
                        <div><span class="dash-k__label">1 ish kuni</span><span class="dash-k__value"><span x-text="hours(workdayMinutes)"></span> s</span></div>
                    </div>
                </div>

                <div class="ui-card">
                    <div class="mb-3">
                        <h3 class="ui-card__title">Bayram va istisno kunlar</h3>
                        <p class="ui-card__sub">Dam olish yoki qo'shimcha ish kuni — deadline hisobida inobatga olinadi</p>
                    </div>
                    <form method="POST" action="{{ route('admin.dispatch.holidays.store') }}" class="mb-3 grid gap-2 sm:grid-cols-[150px_1fr_auto_auto] sm:items-center">
                        @csrf
                        <input type="date" name="date" required class="ui-input ui-mono" aria-label="Sana" value="{{ old('date') }}">
                        <input type="text" name="name" required placeholder="Nomi, masalan: Mustaqillik kuni" class="ui-input" aria-label="Nomi" value="{{ old('name') }}">
                        <select name="is_working_override" class="ui-input" aria-label="Turi">
                            <option value="0">Dam</option>
                            <option value="1">Ish kuni</option>
                        </select>
                        <button type="submit" class="btn btn-secondary">Qo'shish</button>
                    </form>
                    <x-input-error :messages="$errors->get('date')" class="mb-2" />
                    <table class="ui-table">
                        <tbody>
                            @forelse ($holidays as $holiday)
                                <tr>
                                    <td class="ui-mono w-[110px]">{{ $holiday->date->format('d.m.Y') }}</td>
                                    <td>{{ $holiday->name }}</td>
                                    <td><span class="ui-role">{{ $holiday->is_working_override ? 'Ish kuni' : 'Dam' }}</span></td>
                                    <td class="w-px text-right">
                                        <form method="POST" action="{{ route('admin.dispatch.holidays.destroy', $holiday) }}" onsubmit="return confirm('Istisno kun o\'chirilsinmi?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-muted hover:text-red-700">O'chirish</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="py-4 text-center text-muted">Istisno kun qo'shilmagan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ui-savebar">
                <span class="text-[13px]">
                    <template x-if="dirty"><span><span class="ui-dirty-dot"></span>Saqlanmagan o'zgarishlar bor</span></template>
                    <template x-if="!dirty"><span class="text-muted">Shanba va yakshanba — odatda dam olish kuni.</span></template>
                </span>
                <span class="flex gap-2">
                    <form method="POST" action="{{ route('admin.sla.bootstrap') }}" onsubmit="return confirm('Standart 5/2 kalendar qo\'llansinmi? Joriy vaqtlar almashtiriladi.')">
                        @csrf
                        <button type="submit" class="btn btn-secondary">Standart 5/2 kalendar</button>
                    </form>
                    <button type="submit" form="schedule-form" class="btn btn-primary">Saqlash</button>
                </span>
            </div>
        @endif
    </div>
</x-app-layout>

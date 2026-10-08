@php
    $workdayHours = round($workdayMinutes / 60, 2);
    $ordered = collect($priorities)->sortByDesc(fn ($priority) => $priority->level())->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pg-crumb">Sozlamalar</div>
            <h2>Deadline sozlamalari</h2>
            <p class="pg-sub">Muhimlik darajasiga qarab murojaat bajarilish muddatini belgilang.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-none px-4 pt-6 sm:px-6 lg:px-8">
        <p class="ui-note mb-4">
            <span>1 ish kuni hozirgi ish kalendari bo'yicha <b>{{ $workdayHours }} soat</b> deb olinadi. Ish vaqtini <a href="{{ route('admin.dispatch.work-schedule') }}">Ish kunlari</a> sahifasida o'zgartirasiz.</span>
        </p>

        <form method="POST" action="{{ route('admin.dispatch.deadlines.update') }}" x-data="{ dirty: false }" @input="dirty = true">
            @csrf
            @method('PUT')

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($ordered as $index => $priority)
                    @php
                        $profile = $profiles->firstWhere('priority', $priority->value);
                        $deadlineDays = old("profiles.$index.deadline_days", $profile ? round($profile->duration_minutes / $workdayMinutes, 2) : 1);
                        $warning = old("profiles.$index.warning_minutes", $profile->warning_minutes ?? 30);
                    @endphp
                    <div class="ui-card flex flex-col gap-3.5"
                         x-data="{ days: {{ (float) $deadlineDays }}, warn: {{ (int) $warning }}, perDay: {{ $workdayMinutes }} }">
                        <input type="hidden" name="profiles[{{ $index }}][priority]" value="{{ $priority->value }}">
                        <div class="flex items-center justify-between">
                            <span class="status-badge status--{{ $priority->tone() }}">{{ $priority->label() }}</span>
                            <x-ui.priority :priority="$priority" :label="false" />
                        </div>

                        <div>
                            <label class="ui-field-label" for="name-{{ $index }}">Nomi</label>
                            <input id="name-{{ $index }}" name="profiles[{{ $index }}][name]" value="{{ old("profiles.$index.name", $profile->name ?? $priority->label()) }}" class="ui-input" required>
                        </div>

                        <div>
                            <label class="ui-field-label" for="days-{{ $index }}">Bajarilish muddati</label>
                            <div class="flex items-center gap-2">
                                <input id="days-{{ $index }}" type="number" step="0.01" min="0.01" max="365" name="profiles[{{ $index }}][deadline_days]" x-model.number="days" value="{{ $deadlineDays }}" class="ui-input ui-mono w-[100px]" required>
                                <span class="text-[13px] text-muted">ish kuni</span>
                            </div>
                            <p class="ui-hint">= <span x-text="(Math.round(days * perDay / 6) / 10).toLocaleString('uz')">{{ round($deadlineDays * $workdayMinutes / 60, 1) }}</span> soat ish vaqti (1 kun = {{ $workdayHours }} soat)</p>
                            <x-input-error :messages="$errors->get("profiles.$index.deadline_days")" class="mt-1" />
                        </div>

                        <div>
                            <label class="ui-field-label" for="warn-{{ $index }}">Ogohlantirish</label>
                            <div class="flex items-center gap-2">
                                <input id="warn-{{ $index }}" type="number" min="1" name="profiles[{{ $index }}][warning_minutes]" x-model.number="warn" value="{{ $warning }}" class="ui-input ui-mono w-[100px]" required>
                                <span class="text-[13px] text-muted">daqiqa oldin</span>
                            </div>
                            <x-input-error :messages="$errors->get("profiles.$index.warning_minutes")" class="mt-1" />
                        </div>

                        <div>
                            <div class="mb-1 text-xs text-muted">Muddat shkalasi</div>
                            @php($warnShare = min(100, round($warning / max(1, $deadlineDays * $workdayMinutes) * 100)))
                            <div class="flex h-3 overflow-hidden rounded-full bg-sunken" aria-hidden="true">
                                <i class="block h-full bg-accent" :style="`width: ${100 - Math.min(100, Math.round(warn / Math.max(1, days * perDay) * 100))}%`" style="width: {{ 100 - $warnShare }}%"></i>
                                <i class="block h-full" :style="`width: ${Math.min(100, Math.round(warn / Math.max(1, days * perDay) * 100))}%; background: rgb(var(--c-status-in-progress-dot))`" style="width: {{ $warnShare }}%; background: rgb(var(--c-status-in-progress-dot))"></i>
                            </div>
                            <div class="dash-legend mt-1.5">
                                <span><i style="background: rgb(var(--c-accent))"></i>Odatiy</span>
                                <span><i style="background: rgb(var(--c-status-in-progress-dot))"></i>Ogohlantirish zonasi</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="ui-savebar">
                <span class="text-[13px]">
                    <template x-if="dirty"><span><span class="ui-dirty-dot"></span>Saqlanmagan o'zgarishlar bor</span></template>
                    <template x-if="!dirty"><span class="text-muted">Barcha o'zgarishlar saqlangan</span></template>
                </span>
                <span class="flex gap-2">
                    <a href="{{ route('admin.dispatch.deadlines') }}" class="btn btn-secondary">Bekor qilish</a>
                    <button type="submit" class="btn btn-primary">Saqlash</button>
                </span>
            </div>
        </form>
    </div>
</x-app-layout>

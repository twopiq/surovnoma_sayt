<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-['Space_Grotesk'] text-2xl font-bold">IT Yordam markazi boshqaruvi</h2>
            <p class="mt-1 text-sm text-slate-500">Murojaatlar va tizim ko'rsatkichlari.</p>
        </div>
    </x-slot>

    @php
        $stats = $dashboardStats;
        $trendMode = $filterOptions['trend'];
        $trendTabs = ['y' => 'Y', 'q' => 'Q', 'm' => 'M', 'w' => 'W', 'd' => 'D'];
        $formatCompact = function (?int $value): string {
            $value = (int) $value;
            if ($value >= 1000000) return round($value / 1000000, 1).'M';
            if ($value >= 1000) return round($value / 1000, 1).'K';
            return (string) $value;
        };
        $slaAngle = max(0, min(100, (float) $stats['within_sla_percent'])) * 3.6;
        $slaColor = $stats['within_sla_percent'] >= 80 ? 'text-emerald-600' : ($stats['within_sla_percent'] >= 60 ? 'text-amber-600' : 'text-rose-600');
    @endphp

    <div class="mx-auto max-w-none px-4 pt-8 pb-12 sm:px-6 lg:px-8">
        @include('app.partials.top-menu')

        {{-- Filtrlar --}}
        <form method="GET" action="{{ route('app.dashboard') }}" data-auto-filter
              class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <div class="flex flex-wrap items-center gap-3">
                @if ($filterOptions['agents']->count() > 1)
                    <select name="agent" class="rounded-md border-slate-300 text-sm shadow-sm">
                        <option value="all">Barcha ijrochilar</option>
                        @foreach ($filterOptions['agents'] as $agent)
                            <option value="{{ data_get($agent, 'id') }}" @selected($filterOptions['agent'] === (int) data_get($agent, 'id'))>
                                {{ data_get($agent, 'name') }}
                            </option>
                        @endforeach
                    </select>
                @endif
                <select name="year" class="rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="all">Barcha yillar</option>
                    @foreach ($filterOptions['years'] as $year)
                        <option value="{{ $year }}" @selected($filterOptions['year'] === $year)>{{ $year }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="trend" value="{{ $trendMode }}">
            </div>
            @if (request()->hasAny(['agent', 'year']))
                <a href="{{ route('app.dashboard', ['trend' => $trendMode]) }}"
                   class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    Tozalash
                </a>
            @endif
        </form>

        {{-- Asosiy ko'rsatkichlar --}}
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @php
                $kpiCards = [
                    [
                        'label' => 'Jami murojaatlar',
                        'value' => $formatCompact($stats['total_tickets']),
                        'sub' => 'Umumiy',
                        'color' => 'text-slate-900',
                        'dot' => 'bg-slate-400',
                    ],
                    [
                        'label' => 'Ochiq',
                        'value' => $formatCompact($stats['open_tickets']),
                        'sub' => 'Faol holatda',
                        'color' => 'text-orange-600',
                        'dot' => 'bg-orange-400',
                    ],
                    [
                        'label' => 'Hal etilgan',
                        'value' => $formatCompact($stats['resolved_total']),
                        'sub' => 'Yopilgan',
                        'color' => 'text-emerald-600',
                        'dot' => 'bg-emerald-500',
                    ],
                    [
                        'label' => "O'rtacha hal etish",
                        'value' => $stats['avg_resolution_hours'] !== null
                            ? number_format($stats['avg_resolution_hours'], 1).' h'
                            : '—',
                        'sub' => $stats['avg_resolution_hours'] !== null ? 'soat (o\'rtacha)' : 'Ma\'lumot yetarli emas',
                        'color' => 'text-slate-900',
                        'dot' => 'bg-blue-400',
                    ],
                    [
                        'label' => 'SLA doirasida',
                        'value' => number_format($stats['within_sla_percent'], 1).'%',
                        'sub' => $stats['within_sla_count'].' / '.$stats['resolved_total'].' yopilgan',
                        'color' => $slaColor,
                        'dot' => $stats['within_sla_percent'] >= 80 ? 'bg-emerald-500' : ($stats['within_sla_percent'] >= 60 ? 'bg-amber-400' : 'bg-rose-500'),
                    ],
                ];
            @endphp
            @foreach ($kpiCards as $card)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-2 w-2 rounded-full {{ $card['dot'] }}"></span>
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</span>
                    </div>
                    <div class="mt-3 text-4xl font-semibold leading-none {{ $card['color'] }}">{{ $card['value'] }}</div>
                    <div class="mt-2 text-xs text-slate-400">{{ $card['sub'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Trend + SLA --}}
        <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_300px]">

            {{-- Trend grafigi --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold text-slate-900">Murojaatlar trendi</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $trendSeries['label'] }}</p>
                    </div>
                    <div class="inline-flex shrink-0 rounded-lg bg-slate-100 p-1">
                        @foreach ($trendTabs as $value => $label)
                            <a
                                href="{{ route('app.dashboard', array_merge(request()->query(), ['trend' => $value])) }}"
                                class="min-w-[36px] rounded-md px-3 py-1.5 text-center text-sm font-bold transition {{ $trendMode === $value ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:bg-white hover:text-slate-800' }}"
                            >
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="mt-5 h-64">
                    <x-dashboard-area-chart :items="$trendSeries['items']" :height="256" />
                </div>
            </section>

            {{-- SLA va tezkor ko'rsatkichlar --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">SLA holati</h3>
                    <span class="rounded-full bg-[#dcf7f3] px-2.5 py-1 text-xs font-bold tracking-wide text-[#228a84]">
                        {{ number_format($stats['within_sla_percent'], 1) }}%
                    </span>
                </div>
                <p class="mt-1 text-xs text-slate-400">Yopilgan murojaatlar asosida</p>

                <div class="mt-5 flex justify-center">
                    <div class="h-36 w-36 rounded-full" style="background: conic-gradient(#38c1bb 0deg {{ $slaAngle }}deg, #e2f8f6 {{ $slaAngle }}deg 360deg);">
                        <div class="flex h-full items-center justify-center rounded-full border-[16px] border-white bg-white text-center">
                            <div>
                                <div class="text-2xl font-bold {{ $slaColor }}">{{ number_format($stats['within_sla_percent'], 1) }}%</div>
                                <div class="mt-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400">SLA</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3 border-t border-slate-100 pt-5">
                    <div class="rounded-xl bg-emerald-50 px-3 py-3 text-center">
                        <div class="text-xl font-bold text-emerald-700">{{ $formatCompact($stats['within_sla_count']) }}</div>
                        <div class="mt-1 text-[11px] text-emerald-600">Vaqtida</div>
                    </div>
                    <div class="rounded-xl bg-rose-50 px-3 py-3 text-center">
                        <div class="text-xl font-bold text-rose-600">{{ $formatCompact($stats['outside_sla_count']) }}</div>
                        <div class="mt-1 text-[11px] text-rose-500">Kechikkan</div>
                    </div>
                    <div class="rounded-xl bg-amber-50 px-3 py-3 text-center">
                        <div class="text-xl font-bold text-amber-700">{{ $formatCompact($stats['urgent_resolved']) }}</div>
                        <div class="mt-1 text-[11px] text-amber-600">Shoshilinch</div>
                    </div>
                    <div class="rounded-xl bg-cyan-50 px-3 py-3 text-center">
                        <div class="text-xl font-bold text-cyan-700">{{ $formatCompact($stats['same_day_resolved']) }}</div>
                        <div class="mt-1 text-[11px] text-cyan-600">Bir kunda</div>
                    </div>
                </div>
            </section>
        </div>

        {{-- Kategoriya + Muhimlik --}}
        <div class="mt-6 grid gap-6 xl:grid-cols-2">

            {{-- Kategoriya bo'yicha --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Kategoriya bo'yicha murojaatlar</h3>
                @if (count($categoryBreakdown['items']) > 0)
                    <div class="mt-5 space-y-4">
                        @foreach ($categoryBreakdown['items'] as $item)
                            @php($pct = $item['total'] > 0 ? round($item['resolved'] / $item['total'] * 100) : 0)
                            <div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="min-w-0 truncate text-sm font-medium text-slate-700">{{ $item['label'] }}</span>
                                    <span class="shrink-0 text-xs text-slate-500">{{ $item['resolved'] }}/{{ $item['total'] }} &nbsp;·&nbsp; {{ $pct }}%</span>
                                </div>
                                <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-[#38c1bb] transition-all" style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
                        Hozircha kategoriyalar bo'yicha ma'lumot yetarli emas.
                    </div>
                @endif
            </section>

            {{-- Muhimlik jadvali --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Muhimlik bo'yicha murojaatlar</h3>
                <div class="app-table-wrap mt-5">
                    <table class="app-data-table app-data-table--auto">
                        <thead>
                            <tr>
                                <th>Priority</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">New</th>
                                <th class="text-right">In Progress</th>
                                <th class="text-right">Resolved</th>
                                <th class="text-right">Returned</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($priorityMatrix['rows'] as $row)
                                @php($intensity = $priorityMatrix['max'] > 0 ? min(1, $row['total'] / $priorityMatrix['max']) : 0)
                                @php($bg = 'background-color: rgba(56, 193, 187, '.(0.08 + ($intensity * 0.42)).');')
                                <tr>
                                    <td class="font-medium text-slate-900">{{ $row['label'] }}</td>
                                    <td class="text-right font-semibold text-slate-900" style="{{ $bg }}">{{ $row['total'] }}</td>
                                    <td class="text-right text-slate-700" style="{{ $row['new'] > 0 ? $bg : '' }}">{{ $row['new'] }}</td>
                                    <td class="text-right text-slate-700" style="{{ $row['in_progress'] > 0 ? $bg : '' }}">{{ $row['in_progress'] }}</td>
                                    <td class="text-right text-slate-700" style="{{ $row['completed'] > 0 ? $bg : '' }}">{{ $row['completed'] }}</td>
                                    <td class="text-right text-slate-700" style="{{ $row['returned'] > 0 ? $bg : '' }}">{{ $row['returned'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

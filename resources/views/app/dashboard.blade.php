<x-app-layout>
    @php
        $stats = $dashboardStats;
        $trendMode = $filterOptions['trend'];
        $trendTabs = ['y' => 'Y', 'q' => 'Q', 'm' => 'M', 'w' => 'W', 'd' => 'D'];
        $formatCompact = function (?int $value): string {
            $value = (int) $value;

            if ($value >= 1000000) {
                return round($value / 1000000, 1).'M';
            }

            if ($value >= 1000) {
                return round($value / 1000, 1).'K';
            }

            return (string) $value;
        };
        $slaAngle = max(0, min(100, (float) $stats['within_sla_percent'])) * 3.6;
        $cardClass = 'rounded-lg border border-slate-200 bg-white shadow-sm';
    @endphp

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="rounded-lg bg-slate-100/80 p-4 sm:p-5 lg:p-6">
            <div class="grid gap-4 xl:grid-cols-[minmax(320px,0.78fr)_minmax(0,1.55fr)]">
                <div class="space-y-4">
                    <section class="{{ $cardClass }} px-6 py-5">
                        <div class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Asosiy menyu</div>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 sm:text-[42px]">
                            IT Helpdesk Tickets Dashboard
                        </h1>
                    </section>

                    <section class="{{ $cardClass }} px-5 py-4">
                        <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">
                            <article class="text-center">
                                <div class="text-sm font-medium text-slate-500">Total Tickets</div>
                                <div class="mt-3 text-[42px] font-normal leading-none text-slate-900">{{ $formatCompact($stats['total_tickets']) }}</div>
                                <div class="mt-2 text-xs text-slate-600">Umumiy murojaatlar</div>
                            </article>

                            <article class="text-center">
                                <div class="text-sm font-medium text-slate-500">Avg Resolution Time</div>
                                <div class="mt-3 text-[42px] font-normal leading-none text-slate-900">
                                    {{ $stats['avg_resolution_hours'] !== null ? number_format($stats['avg_resolution_hours'], 1) : 'Soon' }}
                                </div>
                                <div class="mt-2 text-xs text-slate-600">
                                    {{ $stats['avg_resolution_hours'] !== null ? "O'rtacha yopilish vaqti" : "Ma'lumot to'plangach" }}
                                </div>
                            </article>

                            <article class="text-center sm:col-span-2 2xl:col-span-1">
                                <div class="flex items-center justify-center gap-2 text-sm font-medium text-slate-500">
                                    <span>Customer Satisfaction</span>
                                    <span class="rounded-full bg-slate-900 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-white">Soon</span>
                                </div>
                                <div class="mt-3 text-[42px] font-normal leading-none text-slate-400">--</div>
                                <div class="mx-auto mt-4 h-3 w-full max-w-[220px] overflow-hidden bg-slate-200">
                                    <div class="h-full bg-[#38c1bb]" style="width: 70%;"></div>
                                </div>
                            </article>
                        </div>
                    </section>

                    <div class="grid gap-4">
                        <section class="{{ $cardClass }} grid gap-4 px-5 py-4 sm:grid-cols-2">
                            <article>
                                <div class="text-[42px] leading-none text-slate-900">{{ $formatCompact($stats['urgent_resolved']) }}</div>
                                <div class="mt-3 text-base leading-7 text-slate-500">Urgent &amp; Prioritized Tickets Resolved</div>
                            </article>

                            <article>
                                <div class="text-[42px] leading-none text-slate-900">{{ $formatCompact($stats['same_day_resolved']) }}</div>
                                <div class="mt-3 text-base leading-7 text-slate-500">Same Day Resolved Tickets</div>
                            </article>
                        </section>

                        <section class="{{ $cardClass }} px-5 py-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-mono text-[22px] font-bold text-slate-900">Within SLA</h3>
                                    <p class="mt-2 text-sm text-slate-500">Mavjud yopilgan murojaatlar asosida hisoblandi</p>
                                </div>
                                <span class="rounded-full bg-[#dcf7f3] px-3 py-1 text-xs font-bold uppercase tracking-[0.16em] text-[#228a84]">
                                    {{ number_format($stats['within_sla_percent'], 1) }}%
                                </span>
                            </div>

                            <div class="mt-6 grid gap-5 2xl:grid-cols-[1fr_210px] 2xl:items-center">
                                <div class="grid gap-4 sm:grid-cols-2 sm:items-end">
                                    <div class="border-r border-slate-300 pr-4 sm:pr-8">
                                        <div class="text-[40px] leading-none text-slate-900">{{ $formatCompact($stats['within_sla_count']) }}</div>
                                        <div class="mt-3 font-mono text-sm font-bold uppercase tracking-[0.02em] text-slate-600">Within SLA</div>
                                    </div>
                                    <div class="pl-0 sm:pl-4">
                                        <div class="text-[40px] leading-none text-slate-900">{{ $formatCompact($stats['outside_sla_count']) }}</div>
                                        <div class="mt-3 font-mono text-sm font-bold uppercase tracking-[0.02em] text-slate-600">Outside SLA</div>
                                    </div>

                                    <div class="sm:col-span-2 pt-3">
                                        <div class="text-[36px] leading-none text-slate-900">{{ $formatCompact($stats['resolved_total']) }}</div>
                                        <div class="mt-3 text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Resolved Total</div>
                                    </div>
                                </div>

                                <div class="mx-auto h-[190px] w-[190px] rounded-full" style="background: conic-gradient(#38c1bb 0deg {{ $slaAngle }}deg, #9be9e4 {{ $slaAngle }}deg 360deg);">
                                    <div class="flex h-full items-center justify-center rounded-full border-[22px] border-white bg-white text-center">
                                        <div>
                                            <div class="text-[36px] leading-none text-slate-900">{{ number_format($stats['within_sla_percent'], 1) }}%</div>
                                            <div class="mt-3 font-mono text-base font-bold text-slate-500">SLA %</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <section class="{{ $cardClass }} px-7 py-5">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="font-mono text-[26px] font-bold text-slate-900">Tickets By Issue Category</h3>
                                <span class="font-mono text-[26px] font-bold text-slate-400">|</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-[26px] font-bold text-slate-900">Request</span>
                                    <span class="rounded-full bg-slate-900 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-white">Soon</span>
                                </div>
                            </div>

                            <div class="inline-flex border border-slate-300 bg-white p-1">
                                <span class="bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Total Tickets</span>
                                <span class="px-4 py-2 text-sm font-semibold text-slate-500">Avg Resolution Time</span>
                            </div>
                        </div>

                        @if (count($categoryBreakdown['items']) > 0)
                            <div class="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                                @foreach ($categoryBreakdown['items'] as $item)
                                    @php
                                        $totalHeight = $categoryBreakdown['max'] > 0 ? max(24, ($item['total'] / $categoryBreakdown['max']) * 190) : 24;
                                        $resolvedHeight = $categoryBreakdown['max'] > 0 ? max(24, ($item['resolved'] / $categoryBreakdown['max']) * 190) : 24;
                                    @endphp
                                    <div class="px-3">
                                        <div class="flex items-end justify-center gap-0.5" style="height: 250px;">
                                            <div class="flex flex-1 flex-col items-center justify-end">
                                                <div class="mb-2 text-xl text-slate-500">{{ $item['resolved'] }}</div>
                                                <div class="w-full max-w-[72px] bg-[#38c1bb]" style="height: {{ $resolvedHeight }}px;"></div>
                                            </div>
                                            <div class="flex flex-1 flex-col items-center justify-end">
                                                <div class="mb-2 text-xl text-slate-500">{{ $item['total'] }}</div>
                                                <div class="w-full max-w-[72px] bg-[#8bdfda]" style="height: {{ $totalHeight }}px;"></div>
                                            </div>
                                        </div>
                                        <div class="mt-4 text-center text-[18px] font-medium text-slate-700">{{ $item['label'] }}</div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-6 flex flex-wrap items-center justify-center gap-5 text-sm font-medium text-slate-500">
                                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#38c1bb]"></span>IT Error</span>
                                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-[#8bdfda]"></span>IT Request</span>
                            </div>
                        @else
                            <div class="mt-10 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                                Hozircha kategoriyalar bo'yicha ma'lumot yetarli emas.
                            </div>
                        @endif
                    </section>
                </div>

                <div class="space-y-4">
                    <section class="{{ $cardClass }} px-5 py-5 sm:px-6">
                        <div class="flex flex-col gap-4 2xl:flex-row 2xl:items-start 2xl:justify-between">
                            <div>
                                <h3 class="font-mono text-[28px] font-bold text-slate-900">Tickets Trend</h3>
                                <p class="mt-2 text-sm text-slate-500">{{ $trendSeries['label'] }}</p>
                            </div>

                            <div class="inline-flex w-full rounded-lg bg-slate-100 p-1 sm:w-auto">
                                @foreach ($trendTabs as $value => $label)
                                    <a
                                        href="{{ route('app.dashboard', array_merge(request()->query(), ['trend' => $value])) }}"
                                        class="min-w-0 flex-1 rounded-md px-4 py-2 text-center font-mono text-sm font-bold transition sm:min-w-11 {{ $trendMode === $value ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:bg-white hover:text-slate-800' }}"
                                    >
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <form method="GET" action="{{ route('app.dashboard') }}" data-auto-filter class="mt-5 grid gap-4 border-t border-slate-200 pt-5 sm:grid-cols-2">
                            <label class="block">
                                <span class="mb-2 block text-sm font-medium text-slate-700">Agent</span>
                                <select name="agent" class="block w-full rounded-md border border-slate-300 bg-white px-3 py-3 text-base text-slate-800 shadow-none focus:border-slate-500 focus:ring-0">
                                    <option value="all">All</option>
                                    @foreach ($filterOptions['agents'] as $agent)
                                        <option value="{{ data_get($agent, 'id') }}" @selected($filterOptions['agent'] === (int) data_get($agent, 'id'))>
                                            {{ data_get($agent, 'name') }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="mb-2 block text-sm font-medium text-slate-700">Year</span>
                                <select name="year" class="block w-full rounded-md border border-slate-300 bg-white px-3 py-3 text-base text-slate-800 shadow-none focus:border-slate-500 focus:ring-0">
                                    <option value="all">All</option>
                                    @foreach ($filterOptions['years'] as $year)
                                        <option value="{{ $year }}" @selected($filterOptions['year'] === $year)>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <input type="hidden" name="trend" value="{{ $trendMode }}">
                        </form>

                        <div class="mt-6 h-[560px] border-t border-slate-200 pt-5">
                            <x-dashboard-area-chart :items="$trendSeries['items']" :height="540" />
                        </div>
                    </section>

                    <section class="{{ $cardClass }} px-6 py-5">
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="font-mono text-[28px] font-bold text-slate-900">Tickets By Priority</h3>
                            <span class="font-mono text-[28px] font-bold text-slate-400">|</span>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-[28px] font-bold text-slate-900">Severity</span>
                                <span class="rounded-full bg-slate-900 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-white">Soon</span>
                            </div>
                        </div>

                        <div class="app-table-wrap mt-6">
                            <table class="app-data-table app-data-table--auto text-lg">
                                <thead>
                                    <tr>
                                        <th class="font-mono text-[18px] font-bold">Priority</th>
                                        <th class="font-mono text-[18px] font-bold">Total</th>
                                        <th class="font-mono text-[18px] font-bold">New</th>
                                        <th class="font-mono text-[18px] font-bold">In Progress</th>
                                        <th class="font-mono text-[18px] font-bold">Resolved</th>
                                        <th class="font-mono text-[18px] font-bold">Returned</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($priorityMatrix['rows'] as $row)
                                        @php($intensity = $priorityMatrix['max'] > 0 ? min(1, $row['total'] / $priorityMatrix['max']) : 0)
                                        @php($background = 'background-color: rgba(56, 193, 187, '.(0.12 + ($intensity * 0.48)).');')
                                        <tr>
                                            <td class="font-medium text-slate-900">{{ $row['label'] }}</td>
                                            <td class="text-slate-900" style="{{ $background }}">{{ $row['total'] }}</td>
                                            <td class="text-slate-900" style="{{ $row['new'] > 0 ? $background : '' }}">{{ $row['new'] }}</td>
                                            <td class="text-slate-900" style="{{ $row['in_progress'] > 0 ? $background : '' }}">{{ $row['in_progress'] }}</td>
                                            <td class="text-slate-900" style="{{ $row['completed'] > 0 ? $background : '' }}">{{ $row['completed'] }}</td>
                                            <td class="text-slate-900" style="{{ $row['returned'] > 0 ? $background : '' }}">{{ $row['returned'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

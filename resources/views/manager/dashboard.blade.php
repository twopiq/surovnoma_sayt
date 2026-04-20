<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-['Space_Grotesk'] text-2xl font-bold text-slate-950">Oylik hisobot</h2>
                <p class="mt-1 text-sm text-slate-500">Tanlangan oy bo'yicha natijalar, reyting va shikoyatlar</p>
            </div>

            <div class="inline-flex items-center rounded-md bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm ring-1 ring-slate-200">
                Tanlangan oy: {{ $monthValue }}
            </div>
        </div>
    </x-slot>

    <div>
        <div class="mx-auto max-w-7xl space-y-5 px-4 py-5 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('manager.dashboard') }}" class="flex flex-wrap items-center gap-3">
                @if ($activeFilters['priority'] ?? null)
                    <input type="hidden" name="priority" value="{{ $activeFilters['priority'] }}">
                @endif
                <input type="hidden" name="chart_period" value="{{ $completionChartOptions['period'] }}">
                <input type="hidden" name="chart_scope" value="{{ $completionChartOptions['scope'] }}">
                <input type="hidden" name="chart_date" value="{{ $completionChartOptions['date']->format('Y-m-d') }}">
                @if ($completionChartOptions['executor_id'])
                    <input type="hidden" name="chart_executor_id" value="{{ $completionChartOptions['executor_id'] }}">
                @endif

                <input
                    type="month"
                    name="month"
                    value="{{ $monthValue }}"
                    class="rounded-md border-slate-300 bg-white text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500"
                />
                <button class="theme-primary rounded-md px-5 py-2 text-sm font-semibold shadow-sm transition">
                    Ko'rish
                </button>
            </form>

            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($stats as $stat)
                    <div class="theme-panel rounded-lg border p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md {{ $stat['accent'] }}">
                                @if ($stat['icon'] === 'check')
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.31a1 1 0 0 1-1.42.002L4.29 10.22a1 1 0 1 1 1.42-1.408l3.04 3.075 6.54-6.591a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                                    </svg>
                                @elseif ($stat['icon'] === 'star')
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="m10 1.5 2.43 5.29 5.78.68-4.27 3.95 1.14 5.7L10 14.26l-5.08 2.86 1.14-5.7-4.27-3.95 5.78-.68L10 1.5Z" />
                                    </svg>
                                @else
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 2a1 1 0 0 1 .86.49l7.5 12.5A1 1 0 0 1 17.5 16.5h-15a1 1 0 0 1-.86-1.51l7.5-12.5A1 1 0 0 1 10 2Zm0 4a1 1 0 0 0-1 1v3.5a1 1 0 1 0 2 0V7a1 1 0 0 0-1-1Zm0 8a1.1 1.1 0 1 0 0-2.2A1.1 1.1 0 0 0 10 14Z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </span>

                            <div class="flex flex-wrap justify-end gap-2">
                                <a href="{{ $stat['excel_url'] }}" class="theme-secondary rounded-md border px-3 py-1.5 text-xs font-semibold transition">
                                    Excel
                                </a>
                                <a href="{{ $stat['csv_url'] }}" class="theme-secondary rounded-md border px-3 py-1.5 text-xs font-semibold transition">
                                    CSV
                                </a>
                            </div>
                        </div>

                        <div class="mt-4 font-['Space_Grotesk'] text-3xl font-bold theme-ink">{{ $stat['value'] }}</div>
                        <div class="mt-2 text-xs font-bold uppercase theme-muted">{{ $stat['label'] }}</div>
                    </div>
                @endforeach
            </div>

            <section class="theme-panel rounded-lg border p-5 shadow-sm">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div>
                        <h3 class="font-['Space_Grotesk'] text-lg font-bold theme-ink">Bajarilgan murojaatlar diagrammasi</h3>
                        <p class="mt-1 text-sm theme-muted">
                            {{ $completionChartMeta['executor_label'] }} / {{ $completionChartMeta['period_label'] }} / {{ $completionChartMeta['range_label'] }}
                        </p>
                    </div>

                    <form method="GET" action="{{ route('manager.dashboard') }}" class="grid gap-3 md:grid-cols-[auto_auto_minmax(180px,1fr)] xl:min-w-[620px]">
                        <input type="hidden" name="month" value="{{ $monthValue }}">
                        @if ($activeFilters['priority'] ?? null)
                            <input type="hidden" name="priority" value="{{ $activeFilters['priority'] }}">
                        @endif

                        <input
                            type="date"
                            name="chart_date"
                            value="{{ $completionChartOptions['date']->format('Y-m-d') }}"
                            class="rounded-md border-slate-300 bg-white text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        />

                        <select name="chart_period" class="rounded-md border-slate-300 bg-white text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                            <option value="day" @selected($completionChartOptions['period'] === 'day')>Kunlik</option>
                            <option value="week" @selected($completionChartOptions['period'] === 'week')>Haftalik</option>
                            <option value="month" @selected($completionChartOptions['period'] === 'month')>Oylik</option>
                            <option value="year" @selected($completionChartOptions['period'] === 'year')>Yillik</option>
                        </select>

                        <select name="chart_executor_id" class="rounded-md border-slate-300 bg-white text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                            <option value="">Barcha bajaruvchilar</option>
                            @foreach ($executors as $executor)
                                <option value="{{ $executor->id }}" @selected($completionChartOptions['executor_id'] === $executor->id)>{{ $executor->name }}</option>
                            @endforeach
                        </select>

                        <div class="md:col-span-3 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="inline-flex rounded-md border border-slate-200 bg-white p-1">
                                <label class="cursor-pointer">
                                    <input type="radio" name="chart_scope" value="total" class="peer sr-only" @checked($completionChartOptions['scope'] === 'total')>
                                    <span class="block rounded-md px-3 py-2 text-sm font-semibold text-slate-600 transition peer-checked:bg-violet-700 peer-checked:text-white">
                                        Butun bajaruvchilar kesimida
                                    </span>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="chart_scope" value="employees" class="peer sr-only" @checked($completionChartOptions['scope'] === 'employees')>
                                    <span class="block rounded-md px-3 py-2 text-sm font-semibold text-slate-600 transition peer-checked:bg-violet-700 peer-checked:text-white">
                                        Xodimlar kesimida
                                    </span>
                                </label>
                            </div>

                            <button class="theme-primary rounded-md px-5 py-2 text-sm font-semibold shadow-sm transition">
                                Yangilash
                            </button>
                        </div>
                    </form>
                </div>

                <div class="theme-soft-panel mt-6 h-80 rounded-lg border p-3">
                    <x-dashboard-bar-chart
                        :items="$completionChartItems"
                        :max="$completionChartMax"
                        accent="#7c3aed"
                        empty-text="Tanlangan kesimda bajarilgan murojaatlar yo'q."
                        :min-width="$completionChartOptions['scope'] === 'employees' ? 760 : 920"
                        :height="300"
                        :slot-size="$completionChartOptions['period'] === 'month' && $completionChartOptions['scope'] === 'total' ? 58 : 74"
                        :fit="true"
                    />
                </div>
            </section>

            <div style="display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 1rem; width: 100%;">
                <section class="theme-panel rounded-lg border p-5 shadow-sm" style="min-width: 0;">
                    <div>
                        <h3 class="font-['Space_Grotesk'] text-lg font-bold theme-ink">Xodimlar natijasi</h3>
                        <p class="mt-1 text-sm theme-muted">Topshiriqlarni yakunlash bo'yicha taqsimot</p>
                    </div>

                    <div class="theme-soft-panel mt-6 h-72 rounded-lg border p-3">
                        <x-dashboard-bar-chart
                            :items="$employeeResults"
                            :max="$employeeMax"
                            accent="#3b82f6"
                            empty-text="Bu oy yakunlangan ishlar hali yo'q."
                            :min-width="560"
                            :slot-size="70"
                            :fit="true"
                        />
                    </div>
                </section>

                <section class="theme-panel rounded-lg border p-5 shadow-sm" style="min-width: 0;">
                    <div>
                        <h3 class="font-['Space_Grotesk'] text-lg font-bold theme-ink">Oylik ko'rsatkichlar</h3>
                        <p class="mt-1 text-sm theme-muted">Asosiy indikatorlar taqsimoti</p>
                    </div>

                    <div class="theme-soft-panel mt-6 h-72 rounded-lg border p-3">
                        <x-dashboard-bar-chart
                            :items="$monthlyIndicators"
                            :max="$indicatorMax"
                            accent="#8b5cf6"
                            :min-width="320"
                            :slot-size="80"
                        />
                    </div>
                </section>
            </div>

            <section class="theme-panel overflow-hidden rounded-lg border shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h3 class="font-['Space_Grotesk'] text-lg font-bold theme-ink">Top bajaruvchilar</h3>
                        <p class="mt-1 text-sm theme-muted">Faol murojaatlar yuklamasi bo'yicha saralangan</p>
                    </div>
                    <a href="{{ route('manager.dashboard', array_merge(request()->query(), ['chart_scope' => 'employees'])) }}" class="text-sm font-semibold text-violet-700 transition hover:text-violet-800">
                        Barchasi &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">SL.</th>
                                <th class="px-5 py-3">Image</th>
                                <th class="px-5 py-3">Name</th>
                                <th class="px-5 py-3">Email</th>
                                <th class="px-5 py-3">Phone</th>
                                <th class="px-5 py-3 text-right">Yuklama</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($topExecutorWorkload as $executor)
                                <tr>
                                    <td class="px-5 py-3 text-slate-600">{{ $loop->iteration }}</td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">
                                            {{ mb_substr($executor['name'], 0, 1) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 font-semibold text-slate-900">{{ $executor['name'] }}</td>
                                    <td class="px-5 py-3 text-slate-600">{{ $executor['email'] }}</td>
                                    <td class="px-5 py-3 text-slate-600">{{ $executor['phone'] ?? '-' }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="font-['Space_Grotesk'] text-base font-bold text-slate-900">{{ $executor['workload_units'] }}</div>
                                        <div class="text-xs text-slate-500">{{ $executor['active_count'] }} ta faol murojaat</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-500">Bajaruvchilar yuklamasi hali mavjud emas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="theme-panel rounded-lg border p-5 shadow-sm">
                <div>
                    <h3 class="font-['Space_Grotesk'] text-lg font-bold theme-ink">Faol ishlar kesimi</h3>
                    <p class="mt-1 text-sm theme-muted">Har bir xodimdagi jarayondagi ishlar</p>
                </div>

                <div class="theme-soft-panel mt-6 h-96 rounded-lg border p-3">
                    <x-dashboard-bar-chart
                        :items="$activeWorkload"
                        :max="$activeWorkloadMax"
                        accent="#14b8a6"
                        empty-text="Ijrochilar ro'yxati hali mavjud emas."
                        :min-width="920"
                        :height="360"
                        :slot-size="86"
                    />
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

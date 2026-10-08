@php
    $summary = $report['summary'];
    $w = fn (string $key) => in_array($key, $widgets, true);
    $query = request()->query();
    $modeUrl = fn (?string $view) => route('app.dashboard', $view ? array_merge($query, ['view' => $view]) : \Illuminate\Support\Arr::except($query, ['view']));
    $exportUrl = fn (string $format) => route('app.dashboard.export', array_merge(\Illuminate\Support\Arr::except($query, ['view']), ['format' => $format]));
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h2>Murojaatlar hisoboti</h2>
                <p class="dash-cap mt-1">
                    Davr: {{ $start->format('d.m.Y') }} – {{ $end->format('d.m.Y') }}
                    · {{ $summary['total'] }} ta murojaat, {{ $summary['completed'] }} tasi bajarildi, SLA {{ $summary['sla_percent'] }}%
                    @if ($summary['overdue'] > 0)
                        · {{ $summary['overdue'] }} tasi kechikkan
                    @endif
                </p>
            </div>
            @if ($isAdmin)
                <nav class="dash-mode" aria-label="Ko'rinish rejimi">
                    <a href="{{ $modeUrl(null) }}" @if (! $managerPreview) aria-current="page" @endif>Admin ko'rinishi</a>
                    <a href="{{ $modeUrl('manager') }}" @if ($managerPreview) aria-current="page" @endif>Rahbar ko'rinishi</a>
                </nav>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-none px-4 pt-6 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('app.dashboard') }}" class="mb-4 flex flex-wrap items-center justify-between gap-3" data-auto-filter>
            @if ($managerPreview)
                <input type="hidden" name="view" value="manager">
            @endif
            <div class="flex flex-wrap items-center gap-2">
                <div class="dash-seg" role="radiogroup" aria-label="Davr">
                    @foreach ($periods as $value => $label)
                        <label>
                            <input type="radio" name="period" value="{{ $value }}" class="sr-only" @checked($period === $value)>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>

                @if ($period === 'range')
                    <input type="date" name="from" value="{{ $start->format('Y-m-d') }}" aria-label="Boshlanish sanasi" class="py-1.5 text-[13px]">
                    <input type="date" name="to" value="{{ $end->format('Y-m-d') }}" aria-label="Tugash sanasi" class="py-1.5 text-[13px]">
                @endif

                <select name="department_id" aria-label="Bo'lim" class="py-1.5 pr-8 text-[13px]">
                    <option value="">Bo'lim: Barchasi</option>
                    @foreach ($filterOptions['departments'] as $department)
                        <option value="{{ $department->id }}" @selected(($filters['department_id'] ?? null) === $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                <select name="category_id" aria-label="Kategoriya" class="py-1.5 pr-8 text-[13px]">
                    <option value="">Kategoriya: Barchasi</option>
                    @foreach ($filterOptions['categories'] as $category)
                        <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? null) === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <select name="executor_id" aria-label="Ijrochi" class="py-1.5 pr-8 text-[13px]">
                    <option value="">Ijrochi: Barchasi</option>
                    @foreach ($filterOptions['executors'] as $executor)
                        <option value="{{ $executor->id }}" @selected(($filters['executor_id'] ?? null) === $executor->id)>{{ $executor->name }}</option>
                    @endforeach
                </select>

                <input type="hidden" name="compare" value="0">
                <label class="inline-flex items-center gap-2 text-[13px] font-medium text-muted">
                    <input type="checkbox" name="compare" value="1" class="rounded" @checked($compare)>
                    Avvalgi davr bilan
                </label>
            </div>

            <div class="flex gap-2">
                <a href="{{ $exportUrl('csv') }}" class="btn btn-secondary">CSV</a>
                <a href="{{ $exportUrl('excel') }}" class="btn btn-primary">Excel</a>
            </div>
        </form>

        @if ($managerPreview)
            <p class="mb-4 rounded-md border border-line bg-accent-soft px-3 py-2 text-[13px] text-accent-strong">
                Rahbar shu ko'rinishni ko'radi: faqat «Rahbarda ko'rinadi» belgilangan vidjetlar.
            </p>
        @endif

        @if (count($widgets) === 0)
            <div class="dash-card text-center text-muted">Hozircha sizga ochilgan infografika yo'q. Administratorga murojaat qiling.</div>
        @endif

        {{-- KPI kartalari --}}
        @if ($w('kpi'))
            <div class="mb-2 flex items-center justify-between gap-2">
                <span class="dash-section__num">Asosiy ko'rsatkichlar</span>
                @if ($canManageWidgets)
                    <form method="POST" action="{{ route('app.dashboard.widgets.toggle', 'kpi') }}">
                        @csrf
                        <button type="submit" @class(['dash-vis', 'dash-vis--on' => $visibility['kpi']]) aria-pressed="{{ $visibility['kpi'] ? 'true' : 'false' }}">
                            <i aria-hidden="true"></i>{{ $visibility['kpi'] ? "Rahbarda ko'rinadi" : 'Rahbarda yashirin' }}
                        </button>
                    </form>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ($report['kpi'] as $card)
                    <div @class(['dash-card', 'dash-card--off' => $canManageWidgets && ! $visibility['kpi']])>
                        <div class="dash-card__body">
                            <span class="dash-k__label">{{ $card['label'] }}</span>
                            <span class="dash-k__value">{{ number_format($card['value'], 0, '.', ' ') }}</span>
                            @if ($card['delta'] !== null)
                                <span class="dash-delta dash-delta--{{ $card['tone'] }}">
                                    {{ $card['delta'] > 0 ? '▲' : ($card['delta'] < 0 ? '▼' : '■') }} {{ number_format(abs($card['delta']), 1) }}%
                                </span>
                                <span class="dash-cap">avvalgi davrdan</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- 01 Oqim --}}
        @if ($w('trend') || $w('funnel') || $w('statuses') || $w('channels'))
            <div class="dash-section">
                <div class="dash-section__num">01</div>
                <h2>Oqim</h2>
                <div class="dash-cap">Murojaatlar qancha keladi va qanday yo'l bosadi</div>
            </div>
            <div class="grid gap-3 xl:grid-cols-[2fr_1fr]">
                <div class="grid content-start gap-3">
                    @if ($w('trend'))
                        @php($trend = $report['trend'])
                        <x-dash-widget key="trend" title="Kunlik trend" :subtitle="'Bajarilgan murojaatlar: '.$trend['total'].($compare ? ', punktir — avvalgi davr' : '')" :visibility="$visibility" :can-manage="$canManageWidgets">
                            <svg width="100%" viewBox="0 0 620 180" preserveAspectRatio="xMinYMin meet" role="img" aria-label="Kunlik bajarilgan murojaatlar grafigi">
                                @foreach ($trend['gridY'] as $line)
                                    <line x1="36" x2="612" y1="{{ $line['y'] }}" y2="{{ $line['y'] }}" style="stroke: rgb(var(--c-line))" />
                                    <text class="dash-svg-text" x="30" y="{{ $line['y'] + 4 }}" text-anchor="end">{{ $line['label'] }}</text>
                                @endforeach
                                @foreach ($trend['labels'] as $label)
                                    <text class="dash-svg-text" x="{{ $label['x'] }}" y="174" text-anchor="middle">{{ $label['label'] }}</text>
                                @endforeach
                                @if ($trend['area'])
                                    <polygon points="{{ $trend['area'] }}" style="fill: rgb(var(--c-accent-soft))" />
                                @endif
                                @if ($trend['previous'])
                                    <polyline points="{{ $trend['previous'] }}" fill="none" stroke-width="2" stroke-dasharray="4 4" style="stroke: rgb(var(--c-line-strong))" />
                                @endif
                                <polyline points="{{ $trend['points'] }}" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="stroke: rgb(var(--c-accent))" />
                                @if ($trend['last'])
                                    <circle cx="{{ $trend['last']['x'] }}" cy="{{ $trend['last']['y'] }}" r="4" style="fill: rgb(var(--c-accent))" />
                                @endif
                            </svg>
                        </x-dash-widget>
                    @endif

                    @if ($w('funnel'))
                        <x-dash-widget key="funnel" title="Murojaat yo'li" subtitle="Davrda kelgan murojaatlar qaysi bosqichgacha yetgani" :visibility="$visibility" :can-manage="$canManageWidgets">
                            @foreach ($report['funnel'] as $stage)
                                <div class="dash-bar dash-bar--tall" style="grid-template-columns: 96px 1fr 84px">
                                    <span>{{ $stage['label'] }}</span>
                                    <div class="dash-bar__track"><i style="width: {{ $stage['percent'] }}%; background: rgb(var(--c-accent)); opacity: {{ $stage['opacity'] }}"></i></div>
                                    <span class="dash-mono text-right">{{ $stage['value'] }} · {{ $stage['percent'] }}%</span>
                                </div>
                            @endforeach
                        </x-dash-widget>
                    @endif
                </div>

                <div class="grid content-start gap-3">
                    @if ($w('statuses'))
                        <x-dash-widget key="statuses" title="Holatlar" :visibility="$visibility" :can-manage="$canManageWidgets">
                            <div class="flex flex-wrap items-center gap-4">
                                <svg width="130" height="130" viewBox="0 0 130 130" role="img" aria-label="Holatlar taqsimoti">
                                    @if ($report['statuses']['total'] === 0)
                                        <circle cx="65" cy="65" r="54" fill="none" stroke-width="18" style="stroke: rgb(var(--c-surface-sunken))" />
                                    @endif
                                    @foreach ($report['statuses']['items'] as $segment)
                                        <circle cx="65" cy="65" r="54" fill="none" stroke-width="18" stroke-dasharray="{{ $segment['dash'] }}" stroke-dashoffset="{{ $segment['offset'] }}" transform="rotate(-90 65 65)" style="stroke: rgb(var(--c-status-{{ $segment['tone'] }}-dot))" />
                                    @endforeach
                                    <text x="65" y="67" text-anchor="middle" style="fill: rgb(var(--c-ink)); font: 600 26px var(--font-display)">{{ $report['statuses']['total'] }}</text>
                                    <text x="65" y="83" text-anchor="middle" class="dash-svg-text">jami</text>
                                </svg>
                                <div class="dash-legend m-0 flex-col">
                                    @forelse ($report['statuses']['items'] as $segment)
                                        <span><i style="background: rgb(var(--c-status-{{ $segment['tone'] }}-dot))"></i>{{ $segment['label'] }} <b class="dash-mono text-ink">{{ $segment['value'] }}</b></span>
                                    @empty
                                        <span>Davrda murojaat yo'q</span>
                                    @endforelse
                                </div>
                            </div>
                        </x-dash-widget>
                    @endif

                    @if ($w('channels'))
                        <x-dash-widget key="channels" title="Kanallar" :visibility="$visibility" :can-manage="$canManageWidgets">
                            @forelse ($report['channels'] as $channel)
                                <div class="dash-bar" style="grid-template-columns: 120px 1fr 40px">
                                    <span>{{ $channel['label'] }}</span>
                                    <div class="dash-bar__track"><i style="width: {{ $channel['width'] }}%; background: rgb(var(--c-{{ $channel['color'] }}))"></i></div>
                                    <span class="dash-mono text-right">{{ $channel['value'] }}</span>
                                </div>
                            @empty
                                <p class="dash-cap">Davrda murojaat yo'q</p>
                            @endforelse
                        </x-dash-widget>
                    @endif
                </div>
            </div>
        @endif

        {{-- 02 Sifat va vaqt --}}
        @if ($w('heatmap') || $w('resolution') || $w('at_risk'))
            <div class="dash-section">
                <div class="dash-section__num">02</div>
                <h2>Sifat va vaqt</h2>
                <div class="dash-cap">SLA, kechikishlar va yechim tezligi</div>
            </div>
            <div class="grid gap-3 lg:grid-cols-2 2xl:grid-cols-[1fr_1fr_1.3fr]">
                @if ($w('heatmap'))
                    <x-dash-widget key="heatmap" title="Qaysi vaqtda murojaat keladi" subtitle="Hafta kuni × soat" :visibility="$visibility" :can-manage="$canManageWidgets">
                        <svg width="100%" viewBox="0 0 316 162" role="img" aria-label="Murojaatlar issiqlik xaritasi">
                            @foreach ($report['heatmap']['rows'] as $r => $row)
                                <text class="dash-svg-text" x="0" y="{{ 22 + $r * 20 }}">{{ $row['label'] }}</text>
                                @foreach ($row['cells'] as $c => $cell)
                                    <rect x="{{ 26 + $c * 26 }}" y="{{ 10 + $r * 20 }}" width="23" height="17" rx="3" style="fill: rgb(var(--c-accent)); opacity: {{ $cell['opacity'] }}">
                                        <title>{{ $row['label'] }}, {{ $cell['hour'] }}:00 — {{ $cell['value'] }} ta</title>
                                    </rect>
                                @endforeach
                            @endforeach
                            @foreach (\App\Services\DashboardReportService::HEATMAP_HOURS as $c => $hour)
                                @if ($c % 2 === 0)
                                    <text class="dash-svg-text" x="{{ 37 + $c * 26 }}" y="156" text-anchor="middle">{{ $hour }}</text>
                                @endif
                            @endforeach
                        </svg>
                        <div class="dash-cap">8:00 dan oldingi va 18:00 dan keyingi murojaatlar chetki ustunlarga qo'shilgan</div>
                    </x-dash-widget>
                @endif

                @if ($w('resolution'))
                    @php($resolution = $report['resolution'])
                    <x-dash-widget key="resolution" title="Yechim vaqti taqsimoti" subtitle="Soat bo'yicha, bajarilgan murojaatlar soni" :visibility="$visibility" :can-manage="$canManageWidgets">
                        <svg width="100%" viewBox="0 0 300 150" role="img" aria-label="Yechim vaqti gistogrammasi">
                            @foreach ($resolution['bars'] as $i => $bar)
                                <rect x="{{ 13 + $i * 40 }}" y="{{ 130 - $bar['height'] }}" width="34" height="{{ max($bar['height'], 1) }}" rx="3" style="fill: rgb(var(--c-accent))">
                                    <title>{{ $bar['label'] }} soat — {{ $bar['value'] }} ta</title>
                                </rect>
                                <text class="dash-svg-text" x="{{ 30 + $i * 40 }}" y="144" text-anchor="middle">{{ $bar['label'] }}</text>
                            @endforeach
                        </svg>
                        <div class="dash-cap">
                            @if ($resolution['count'] > 0)
                                Mediana {{ $resolution['median'] }} soat · 90-persentil {{ $resolution['p90'] }} soat · {{ $resolution['count'] }} ta murojaat
                            @else
                                Davrda bajarilgan murojaat yo'q
                            @endif
                        </div>
                    </x-dash-widget>
                @endif

                @if ($w('at_risk'))
                    <x-dash-widget key="at_risk" title="Kechikkan va xavfdagilar" subtitle="Muddati o'tgan yoki 24 soatdan kam qolgan" :visibility="$visibility" :can-manage="$canManageWidgets" class="lg:col-span-2 2xl:col-span-1">
                        @if (count($report['atRisk']) > 0)
                            <table class="w-full text-[13px]">
                                <thead>
                                    <tr class="bg-sunken text-left text-[11px] font-semibold uppercase tracking-[.06em] text-muted">
                                        <th class="px-2 py-1.5">Raqam</th>
                                        <th class="px-2 py-1.5">Mavzu</th>
                                        <th class="px-2 py-1.5">SLA</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['atRisk'] as $row)
                                        <tr class="border-t border-line">
                                            <td class="dash-mono whitespace-nowrap px-2 py-2">
                                                @if ($isAdmin)
                                                    <a href="{{ route('admin.dispatch.show', $row['ticket']) }}" class="text-accent hover:underline">{{ $row['ticket']->reference }}</a>
                                                @else
                                                    {{ $row['ticket']->reference }}
                                                @endif
                                            </td>
                                            <td class="max-w-[220px] truncate px-2 py-2" title="{{ $row['subject'] }}">{{ $row['subject'] }}</td>
                                            <td class="whitespace-nowrap px-2 py-2"><span class="status-badge status--{{ $row['tone'] }}">{{ $row['pill'] }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="dash-cap">Kechikkan yoki muddati yaqin murojaat yo'q.</p>
                        @endif
                    </x-dash-widget>
                @endif
            </div>
        @endif

        {{-- 03 Jamoa --}}
        @if ($w('executors') || $w('score_parts') || $w('departments'))
            <div class="dash-section">
                <div class="dash-section__num">03</div>
                <h2>Jamoa</h2>
                <div class="dash-cap">Ijrochilar, bo'limlar va yuklama</div>
            </div>
            <div class="grid gap-3 lg:grid-cols-2 2xl:grid-cols-[2fr_1fr_1fr]">
                @if ($w('executors'))
                    <x-dash-widget key="executors" title="Ijrochilar KPI reytingi" :visibility="$visibility" :can-manage="$canManageWidgets" class="lg:col-span-2 2xl:col-span-1">
                        @if ($report['executors']->isNotEmpty())
                            <table class="w-full text-[13px]">
                                <thead>
                                    <tr class="bg-sunken text-left text-[11px] font-semibold uppercase tracking-[.06em] text-muted">
                                        <th class="px-2 py-1.5">Ijrochi</th>
                                        <th class="px-2 py-1.5">Jami</th>
                                        <th class="px-2 py-1.5">Bajar.</th>
                                        <th class="px-2 py-1.5">SLA</th>
                                        <th class="px-2 py-1.5">Soat</th>
                                        <th class="px-2 py-1.5">KPI</th>
                                        <th class="px-2 py-1.5">Baho</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report['executors'] as $row)
                                        <tr class="border-t border-line">
                                            <td class="whitespace-nowrap px-2 py-2">{{ $row['name'] }}</td>
                                            <td class="dash-mono px-2 py-2">{{ $row['total'] }}</td>
                                            <td class="dash-mono px-2 py-2">{{ $row['completed'] }}</td>
                                            <td class="dash-mono px-2 py-2">{{ $row['sla_percent'] }}%</td>
                                            <td class="dash-mono px-2 py-2">{{ $row['avg_resolution_hours'] ?? '—' }}</td>
                                            <td class="min-w-[110px] px-2 py-2">
                                                <div class="flex items-center gap-2">
                                                    <div class="dash-bar__track flex-1"><i style="width: {{ min(100, $row['kpi_score']) }}%; background: rgb(var(--c-accent))"></i></div>
                                                    <span class="dash-mono">{{ $row['kpi_score'] }}</span>
                                                </div>
                                            </td>
                                            <td class="px-2 py-2"><span class="dash-grade" title="{{ $row['grade'] }}">{{ \App\Services\DashboardReportService::letterGrade((float) $row['kpi_score']) }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="dash-cap">Ijrochilar topilmadi.</p>
                        @endif
                    </x-dash-widget>
                @endif

                @if ($w('score_parts'))
                    <x-dash-widget key="score_parts" title="KPI ball tarkibi" subtitle="Og'irliklar 40 / 30 / 15 / 10 / 5" :visibility="$visibility" :can-manage="$canManageWidgets">
                        @foreach ($report['scoreParts']['rows'] as $part)
                            <div class="dash-bar" style="grid-template-columns: 130px 1fr 52px">
                                <span>{{ $part['label'] }}</span>
                                <div class="dash-bar__track"><i style="width: {{ $part['width'] }}%; background: rgb(var(--c-{{ $part['color'] }}))"></i></div>
                                <span class="dash-mono text-right">{{ $part['value'] }}/{{ $part['weight'] }}</span>
                            </div>
                        @endforeach
                        <div class="dash-cap mt-1.5">Umumiy KPI: <b class="dash-mono text-ink">{{ $report['scoreParts']['total'] }}</b> / 100</div>
                    </x-dash-widget>
                @endif

                @if ($w('departments'))
                    <x-dash-widget key="departments" title="Bo'limlar" subtitle="Jami va bajarilgan" :visibility="$visibility" :can-manage="$canManageWidgets">
                        @php($deptMax = max(1, (int) $report['departments']->max('total')))
                        @forelse ($report['departments'] as $department)
                            <div class="dash-bar">
                                <span class="truncate" title="{{ $department['label'] }}">{{ $department['label'] }}</span>
                                <div class="dash-bar__track">
                                    <i style="width: {{ round($department['total'] / $deptMax * 100) }}%; background: rgb(var(--c-line-strong)); opacity: .45"></i>
                                    <i style="width: {{ round($department['completed'] / $deptMax * 100) }}%; background: rgb(var(--c-accent))"></i>
                                </div>
                                <span class="dash-mono text-right">{{ $department['completed'] }}/{{ $department['total'] }}</span>
                            </div>
                        @empty
                            <p class="dash-cap">Davrda bo'limlarga biriktirilgan murojaat yo'q.</p>
                        @endforelse
                        <div class="dash-legend">
                            <span><i style="background: rgb(var(--c-accent))"></i>Bajarilgan</span>
                            <span><i style="background: rgb(var(--c-line-strong)); opacity: .45"></i>Jami</span>
                        </div>
                    </x-dash-widget>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>

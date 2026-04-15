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

    <div class="min-h-screen bg-slate-100">
        <div class="mx-auto max-w-7xl space-y-5 px-4 py-5 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('manager.dashboard') }}" class="flex flex-wrap items-center gap-3">
                @if ($activeFilters['priority'] ?? null)
                    <input type="hidden" name="priority" value="{{ $activeFilters['priority'] }}">
                @endif

                <input
                    type="month"
                    name="month"
                    value="{{ $monthValue }}"
                    class="rounded-md border-slate-300 bg-white text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500"
                />
                <button class="rounded-md bg-violet-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">
                    Ko'rish
                </button>
            </form>

            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($stats as $stat)
                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/80">
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

                            <a href="{{ $stat['excel_url'] }}" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                                Excel
                            </a>
                        </div>

                        <div class="mt-4 font-['Space_Grotesk'] text-3xl font-bold text-slate-950">{{ $stat['value'] }}</div>
                        <div class="mt-2 text-xs font-bold uppercase text-slate-400">{{ $stat['label'] }}</div>
                    </div>
                @endforeach
            </div>

            <div style="display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 1rem; width: 100%;">
                <section style="min-width: 0; border-radius: 0.5rem; background: #ffffff; padding: 1.25rem; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08); outline: 1px solid rgba(226, 232, 240, 0.8);">
                    <div>
                        <h3 style="font-family: 'Space Grotesk', sans-serif; font-size: 1.125rem; font-weight: 700; line-height: 1.75rem; color: #020617;">Xodimlar natijasi</h3>
                        <p style="margin-top: 0.25rem; font-size: 0.875rem; line-height: 1.25rem; color: #64748b;">Topshiriqlarni yakunlash bo'yicha taqsimot</p>
                    </div>

                    <div style="margin-top: 1.5rem; height: 18rem; border-radius: 0.5rem; background: linear-gradient(to bottom, #f8fafc, #ffffff); padding: 0.75rem; outline: 1px solid #f1f5f9;">
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

                <section style="min-width: 0; border-radius: 0.5rem; background: #ffffff; padding: 1.25rem; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08); outline: 1px solid rgba(226, 232, 240, 0.8);">
                    <div>
                        <h3 style="font-family: 'Space Grotesk', sans-serif; font-size: 1.125rem; font-weight: 700; line-height: 1.75rem; color: #020617;">Oylik ko'rsatkichlar</h3>
                        <p style="margin-top: 0.25rem; font-size: 0.875rem; line-height: 1.25rem; color: #64748b;">Asosiy indikatorlar taqsimoti</p>
                    </div>

                    <div style="margin-top: 1.5rem; height: 18rem; border-radius: 0.5rem; background: linear-gradient(to bottom, #f8fafc, #ffffff); padding: 0.75rem; outline: 1px solid #f1f5f9;">
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

            <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200/80">
                <div>
                    <h3 class="font-['Space_Grotesk'] text-lg font-bold text-slate-950">Faol ishlar kesimi</h3>
                    <p class="mt-1 text-sm text-slate-500">Har bir xodimdagi jarayondagi ishlar</p>
                </div>

                <div class="mt-6 h-96 rounded-lg bg-gradient-to-b from-slate-50 to-white p-3 ring-1 ring-slate-100">
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

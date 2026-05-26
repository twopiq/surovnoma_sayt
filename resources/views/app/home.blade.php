<x-app-layout>
    @php
        $user = auth()->user();
    @endphp

    @if ($user->hasRole(\App\Enums\UserRole::Admin->value))
        <x-slot name="header">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Dispetcher doskasi</h2>
                    <p class="mt-1 text-sm text-slate-500">Faol murojaatlarning holatlar bo'yicha ko'rinishi</p>
                </div>
            </div>
        </x-slot>

        <div class="mx-auto max-w-7xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
            <livewire:admin.dispatch-board />
        </div>
    @elseif ($user->hasRole(\App\Enums\UserRole::Executor->value))
        @php
            $workloadSummary = $homeData['workloadSummary'];
            $stats = $homeData['stats'];
            $activeTickets = $homeData['activeTickets'];
            $todayTasks = $homeData['todayTasks'];
            $overdueTickets = $homeData['overdueTickets'];
            $availableTickets = $homeData['availableTickets'];
        @endphp

        <x-slot name="header">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Ijrochi bosh sahifasi</h2>
                    <p class="mt-1 text-sm text-slate-500">Joriy ishlar, muddatlar va qabul qilish mumkin bo'lgan murojaatlar.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('executor.tickets.index') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Barcha vazifalar
                    </a>
                    <a href="{{ route('executor.tickets.archive') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Arxiv
                    </a>
                </div>
            </div>
        </x-slot>

        <div class="mx-auto max-w-none space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-slate-500">Menga biriktirilgan</div>
                    <div class="mt-4 font-['Space_Grotesk'] text-4xl font-bold text-slate-900">{{ $stats['active_count'] }}</div>
                    <div class="mt-2 text-sm text-slate-500">Faol ishlar soni</div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-slate-500">Jarayonda</div>
                    <div class="mt-4 font-['Space_Grotesk'] text-4xl font-bold text-slate-900">{{ $stats['in_progress_count'] }}</div>
                    <div class="mt-2 text-sm text-slate-500">Hozir bajarilayotgan vazifalar</div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-slate-500">Bugungi muddat</div>
                    <div class="mt-4 font-['Space_Grotesk'] text-4xl font-bold {{ $stats['due_today_count'] > 0 ? 'text-orange-700' : 'text-slate-900' }}">{{ $stats['due_today_count'] }}</div>
                    <div class="mt-2 text-sm text-slate-500">Bugun tugaydigan ishlar</div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-slate-500">Kechikkan bo'shlar</div>
                    <div class="mt-4 font-['Space_Grotesk'] text-4xl font-bold {{ $stats['overdue_count'] > 0 ? 'text-rose-700' : 'text-slate-900' }}">
                        {{ $stats['overdue_count'] }}
                    </div>
                    <div class="mt-2 text-sm text-slate-500">Qabul qilish mumkin</div>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                    <h3 class="font-['Space_Grotesk'] text-lg font-bold text-slate-900">Joriy yuklama</h3>
                    <p class="mt-1 text-sm text-slate-500">Faol ishlar muhimlik darajasiga qarab hisoblanadi.</p>
                    </div>
                    <div class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
                        {{ $workloadSummary['used_units'] }}/{{ $workloadSummary['max_units'] }} birlik
                    </div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="text-sm text-slate-500">Ishlatilgan yuklama</div>
                        <div class="mt-3 font-['Space_Grotesk'] text-3xl font-bold text-slate-900">{{ $workloadSummary['used_units'] }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="text-sm text-slate-500">Qolgan yuklama</div>
                        <div class="mt-3 font-['Space_Grotesk'] text-3xl font-bold text-emerald-700">{{ $workloadSummary['remaining_units'] }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="text-sm text-slate-500">Ortiqcha yuklama</div>
                        <div class="mt-3 font-['Space_Grotesk'] text-3xl font-bold {{ $workloadSummary['overload_units'] > 0 ? 'text-orange-700' : 'text-slate-900' }}">
                            {{ $workloadSummary['overload_units'] }}
                        </div>
                    </div>
                </div>

                @if ($workloadSummary['overload_units'] > 0)
                    <div class="mt-4 rounded-lg border border-orange-200 bg-orange-50 p-4 text-sm text-orange-900">
                        <div class="font-semibold">Ortiqcha yuklama bor</div>
                        <p class="mt-1">
                            Sizda limitdan {{ $workloadSummary['overload_units'] }} birlik ko'p faol ish bor. Admin tomonidan tasdiqlangan ortiqcha yuklama shu yerda ko'rinadi.
                        </p>
                    </div>
                @endif

                <p class="mt-4 text-xs text-slate-400">
                    Limit: 1 ta shoshilinch va 1 ta past, yoki 2 ta yuqori, yoki 3 ta o'rta, yoki 5 ta past topshiriq.
                </p>
            </section>

            @if ($overdueTickets->isNotEmpty())
                <section class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-rose-800">Kechikkan murojaatlar</h3>
                            <p class="mt-1 text-sm text-slate-500">Ijrochidan yechilgan va qayta qabul qilish mumkin bo'lgan murojaatlar.</p>
                        </div>
                        <span class="rounded-full bg-rose-100 px-3 py-1 text-sm font-semibold text-rose-800">{{ $overdueTickets->count() }} ta</span>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-2">
                        @foreach ($overdueTickets as $ticket)
                            <a href="{{ route('executor.tickets.show', ['ticket' => $ticket, 'source' => 'home']) }}" class="block">
                                @include('partials.ticket-card', ['ticket' => $ticket])
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                <section class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-slate-900">Bugungi vazifalar</h3>
                        <span class="text-sm text-slate-500">{{ $todayTasks->count() }} ta</span>
                    </div>

                    @forelse ($todayTasks as $ticket)
                        <a href="{{ route('executor.tickets.show', ['ticket' => $ticket, 'source' => 'home']) }}" class="block">
                            @include('partials.ticket-card', ['ticket' => $ticket])
                        </a>
                    @empty
                        <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">
                            Bugun uchun alohida ajratilgan vazifalar topilmadi.
                        </div>
                    @endforelse
                </section>

                <section class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-slate-900">Bo'sh murojaatlar</h3>
                        <a href="{{ route('executor.tickets.index') }}" class="text-sm font-semibold text-cyan-700 transition hover:text-cyan-800">
                            {{ $stats['available_count'] }} ta
                        </a>
                    </div>

                    @forelse ($availableTickets as $ticket)
                        <a href="{{ route('executor.tickets.show', ['ticket' => $ticket, 'source' => 'home']) }}" class="block">
                            <div class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">Tanlash uchun ochiq</div>
                            @include('partials.ticket-card', ['ticket' => $ticket])
                        </a>
                    @empty
                        <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">
                            Hozircha bo'sh murojaatlar mavjud emas.
                        </div>
                    @endforelse
                </section>
            </div>

            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">Menga biriktirilgan murojaatlar</h3>
                    <a href="{{ route('executor.tickets.index') }}" class="text-sm font-semibold text-cyan-700 transition hover:text-cyan-800">
                        To'liq ro'yxat
                    </a>
                </div>

                @forelse ($activeTickets as $ticket)
                    <a href="{{ route('executor.tickets.show', ['ticket' => $ticket, 'source' => 'home']) }}" class="block">
                        @include('partials.ticket-card', ['ticket' => $ticket])
                    </a>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">
                        Sizga biriktirilgan murojaatlar hozircha yo'q.
                    </div>
                @endforelse
            </section>
        </div>
    @else
        <x-slot name="header">
            <div>
                <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Home</h2>
                <p class="mt-1 text-sm text-slate-500">Sizning ish maydoningizga mos asosiy yo'nalishlar.</p>
            </div>
        </x-slot>

        <div class="mx-auto max-w-4xl px-4 pt-8 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-slate-200 bg-white p-8 text-center shadow-sm">
                <h3 class="font-['Space_Grotesk'] text-xl font-bold text-slate-900">Sahifa tayyorlanmoqda</h3>
                <p class="mt-3 text-sm leading-6 text-slate-500">
                    Sizning rol uchun Home sahifasi keyingi bosqichda boyitiladi. Hozircha chap menyudagi tegishli bo'limlardan foydalanishingiz mumkin.
                </p>
            </div>
        </div>
    @endif
</x-app-layout>

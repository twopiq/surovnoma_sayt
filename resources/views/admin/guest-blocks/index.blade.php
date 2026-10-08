<x-app-layout>
    <x-slot name="header">
        <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Bloklangan guest qurilmalar</h2>
    </x-slot>

    <div class="mx-auto max-w-none space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        @include('admin.guest-blocks.partials.tabs')

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-600">
                Guest rejimida murojaat limitidan oshgan qurilma, IP manzil yoki email/telefonlar shu yerda ko'rinadi.
                Blokdan chiqarilgach, ular yana murojaat yubora oladi.
            </p>
            <p class="mt-2 text-xs text-slate-500">
                Limitlar: qurilma — soatiga {{ config('guest_limits.device_per_hour') }}, kuniga {{ config('guest_limits.device_per_day') }};
                IP — soatiga {{ config('guest_limits.ip_per_hour') }}, kuniga {{ config('guest_limits.ip_per_day') }};
                email/telefon — kuniga {{ config('guest_limits.contact_per_day') }}.
                <a href="{{ route('admin.guest-blocks.settings') }}" class="font-semibold text-cyan-700 underline">O'zgartirish</a>
            </p>

            <details class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4" @if ($errors->has('value')) open @endif>
                <summary class="cursor-pointer text-sm font-semibold text-slate-800">Qo'lda bloklash (IP, email yoki telefon)</summary>
                <form method="POST" action="{{ route('admin.guest-blocks.store') }}" class="mt-3 grid gap-3 md:grid-cols-[10rem_1fr_1fr_auto] md:items-start">
                    @csrf
                    <select name="type" class="rounded-md border-slate-300 text-sm shadow-sm">
                        <option value="ip" @selected(old('type') === 'ip')>IP manzil</option>
                        <option value="email" @selected(old('type') === 'email')>Email</option>
                        <option value="phone" @selected(old('type') === 'phone')>Telefon</option>
                    </select>
                    <div>
                        <input name="value" value="{{ old('value') }}" required placeholder="192.168.1.10 / user@mail.uz / +998 90 123 45 67" class="w-full rounded-md border-slate-300 text-sm shadow-sm" />
                        <x-input-error :messages="$errors->get('value')" class="mt-1" />
                    </div>
                    <input name="note" value="{{ old('note') }}" placeholder="Izoh (ixtiyoriy)" class="rounded-md border-slate-300 text-sm shadow-sm" />
                    <button class="rounded-md bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">Bloklash</button>
                </form>
                <p class="mt-2 text-xs text-slate-500">Ishonchli IP ro'yxatidagi manzilni IP bo'yicha bloklab bo'lmaydi — avval uni ro'yxatdan olib tashlang.</p>
            </details>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                <div class="inline-flex rounded-md border border-slate-200 p-1 text-sm font-semibold">
                    <a href="{{ route('admin.guest-blocks.index', ['status' => 'active', 'q' => $search ?: null]) }}"
                       class="rounded px-3 py-1.5 {{ $status === 'active' ? 'bg-cyan-700 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                        Faol bloklar ({{ $activeCount }})
                    </a>
                    <a href="{{ route('admin.guest-blocks.index', ['status' => 'unblocked', 'q' => $search ?: null]) }}"
                       class="rounded px-3 py-1.5 {{ $status === 'unblocked' ? 'bg-cyan-700 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                        Tarix
                    </a>
                </div>

                <form method="GET" class="flex gap-2">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input name="q" value="{{ $search }}" placeholder="IP, qurilma ID, email, telefon yoki BLOK-…" class="w-72 rounded-md border-slate-300 text-sm shadow-sm" />
                    <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">Qidirish</button>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Blok</th>
                        <th class="px-4 py-3">Sabab</th>
                        <th class="px-4 py-3">IP manzil</th>
                        <th class="px-4 py-3">Qurilma</th>
                        <th class="px-4 py-3">Email / telefon</th>
                        <th class="px-4 py-3">Urinishlar</th>
                        <th class="px-4 py-3">Vaqt</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($blocks as $block)
                        <tr class="align-top">
                            <td class="px-4 py-3">
                                <div class="font-mono font-bold text-slate-900">BLOK-{{ $block->id }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $block->scopeLabel() }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700">{{ $block->reasonLabel() }}</span>
                                @if ($block->description)
                                    <div class="mt-1 text-xs text-slate-500">{{ $block->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-900">{{ $block->ip ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900">{{ $block->deviceLabel() }}</div>
                                <div class="mt-1 font-mono text-xs text-slate-500" title="{{ $block->device_id }}">ID: {{ \Illuminate\Support\Str::limit($block->device_id, 13, '…') }}</div>
                                @if ($block->user_agent)
                                    <details class="mt-1 text-xs text-slate-500">
                                        <summary class="cursor-pointer">User-Agent</summary>
                                        <div class="mt-1 max-w-xs break-all">{{ $block->user_agent }}</div>
                                    </details>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                <div>{{ $block->email ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $block->phone ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $block->attempts_count }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                <div>Bloklangan: {{ $block->blocked_at->format('d.m.Y H:i') }}</div>
                                @if ($block->last_attempt_at)
                                    <div>Oxirgi urinish: {{ $block->last_attempt_at->format('d.m.Y H:i') }}</div>
                                @endif
                                @if ($block->unblocked_at)
                                    <div class="text-emerald-700">Ochilgan: {{ $block->unblocked_at->format('d.m.Y H:i') }} ({{ $block->unblocker?->name ?? '-' }})</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($block->isActive())
                                    <form method="POST" action="{{ route('admin.guest-blocks.unblock', $block) }}" onsubmit="return confirm('BLOK-{{ $block->id }} blokdan chiqarilsinmi?')">
                                        @csrf
                                        <button class="whitespace-nowrap rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700">
                                            Blokdan ochish
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                                {{ $status === 'active' ? "Hozircha bloklangan qurilma yo'q." : "Tarix bo'sh." }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $blocks->links() }}</div>
    </div>
</x-app-layout>

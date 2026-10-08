<x-guest-layout>
    <div class="rounded-2xl border border-red-200 bg-red-50 p-6">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM5.94 5.94a.75.75 0 0 1 1.06 0L10 8.94l3-3a.75.75 0 1 1 1.06 1.06l-3 3 3 3A.75.75 0 1 1 13 14.06l-3-3-3 3A.75.75 0 0 1 5.94 13l3-3-3-3a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                </svg>
            </div>
            <div>
                <h1 class="font-['Space_Grotesk'] text-2xl font-bold text-red-800">Murojaat yuborish vaqtincha cheklangan</h1>
                <p class="mt-2 text-sm text-red-700">
                    Ushbu qurilma yoki tarmoqdan murojaatlar soni ruxsat etilgan limitdan oshdi.
                    Administrator blokdan chiqarmaguncha yangi murojaat yuborib bo'lmaydi.
                </p>
                <p class="mt-3 text-sm text-red-700">
                    Agar bu xato bo'lsa, RTT markaziga murojaat qiling va quyidagi raqamni ayting:
                </p>
                <div class="mt-2 inline-block rounded-md bg-white px-3 py-1.5 font-mono text-lg font-bold text-red-800 ring-1 ring-red-200">
                    BLOK-{{ $block->id }}
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap gap-3">
        <a href="{{ route('guest.track') }}" class="rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-cyan-800">Mavjud murojaatni kuzatish</a>
        <a href="{{ route('home') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Bosh sahifa</a>
    </div>
</x-guest-layout>

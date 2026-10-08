<x-public-layout title="Cheklangan">
    <div class="grid items-start gap-6 py-8 lg:grid-cols-2">
        <div>
            <span class="status-badge status--new">Cheklangan</span>
            <h1 class="mt-3 font-display text-[30px] font-bold leading-[38px] sm:text-[36px] sm:leading-[42px]">Hozircha yangi murojaat yuborib bo'lmaydi</h1>
            <p class="mb-5 mt-3 text-base text-muted">Qurilmangiz yoki tarmog'ingizdan juda ko'p urinish bo'ldi. Bu xavfsizlik uchun vaqtincha cheklov.</p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('guest.track') }}" class="btn btn-primary btn-lg">Mavjud murojaatni kuzatish</a>
                <a href="{{ route('home') }}" class="btn btn-secondary btn-lg">Bosh sahifa</a>
            </div>
        </div>

        <section class="rounded-3xl border border-line bg-surface p-6">
            <h2 class="mb-2 text-base font-semibold">Nima qilish kerak?</h2>
            <ul class="pub-list">
                <li><span class="pub-num">1</span>Quyidagi blok raqamini yozib oling</li>
                <li><span class="pub-num">2</span>RTT markaziga murojaat qilib, raqamni ayting</li>
                <li><span class="pub-num">3</span>Administrator blokni ko'rib chiqadi</li>
            </ul>
            <div class="mt-3 rounded-md bg-sunken p-4 text-center">
                <div class="text-xs text-muted">Blok raqami</div>
                <div class="select-all font-mono text-[28px] font-medium leading-9">BLOK-{{ $block->id }}</div>
            </div>
        </section>
    </div>
</x-public-layout>

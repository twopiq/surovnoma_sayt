@php
    $check = '<svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 10.5l3.2 3.2L15 7"/></svg>';
@endphp

<x-public-layout footer-note="Murojaatlar tizimi">
    <section class="max-w-[760px] pb-8 pt-8 sm:pt-12">
        <h1 class="font-display text-[34px] font-bold leading-[40px] tracking-[-.01em] sm:text-[44px] sm:leading-[50px]">{{ __('Qaysi yo\'l sizga mos?') }}</h1>
        <p class="mt-3.5 text-[17px] leading-[26px] text-muted">{{ __('RTT Markazi murojaatlari ikki yo\'l bilan qabul qilinadi: tizim xodimi sifatida yoki akkauntsiz.') }}</p>
    </section>

    <div class="grid gap-6 md:grid-cols-2">
        <section class="flex flex-col gap-4 rounded-3xl border border-line bg-surface p-6 sm:p-8">
            <span class="text-xs font-semibold uppercase tracking-[.08em] text-accent-strong">{{ __('Xodim uchun') }}</span>
            <h2 class="font-display text-[28px] font-semibold leading-[34px]">{{ __('Akkaunt orqali ishlang') }}</h2>
            <ul class="pub-list">
                <li><span class="pub-num">{!! $check !!}</span>{{ __('Barcha murojaatlaringiz bir joyda') }}</li>
                <li><span class="pub-num">{!! $check !!}</span>{{ __('Holat o\'zgarsa bildirishnoma keladi') }}</li>
                <li><span class="pub-num">{!! $check !!}</span>{{ __('Ijrochi bilan yozishuv') }}</li>
            </ul>
            <div class="mt-auto flex flex-wrap gap-2">
                @auth
                    <a href="{{ route('app.home') }}" class="btn btn-primary btn-lg">{{ __('Kabinetga o\'tish') }}</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg">{{ __('Kirish') }}</a>
                    <a href="{{ route('register') }}" class="btn btn-secondary btn-lg">{{ __('Akkaunt yaratish') }}</a>
                @endauth
            </div>
        </section>

        <section class="flex flex-col gap-4 rounded-3xl bg-accent-soft p-6 sm:p-8">
            <span class="text-xs font-semibold uppercase tracking-[.08em] text-accent-strong">{{ __('Mehmon uchun') }}</span>
            <h2 class="font-display text-[28px] font-semibold leading-[34px]">{{ __('Akkauntsiz murojaat yuboring') }}</h2>
            <ul class="pub-list">
                <li><span class="pub-num">{!! $check !!}</span>{{ __('F.I.Sh. va aloqa ma\'lumoti yetarli') }}</li>
                <li><span class="pub-num">{!! $check !!}</span>{{ __('Ticket ID va maxfiy kod beriladi') }}</li>
                <li><span class="pub-num">{!! $check !!}</span>{{ __('Holatni istalgan vaqt kuzating') }}</li>
            </ul>
            <div class="mt-auto flex flex-wrap gap-2">
                <a href="{{ route('guest.create') }}" class="btn btn-primary btn-lg">{{ __('Mehmon formasi') }}</a>
                <a href="{{ route('guest.track') }}" class="btn btn-secondary btn-lg">{{ __('Holatni kuzatish') }}</a>
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="ui-card !p-[18px]">
            <span class="text-xs text-muted">{{ __('Jami murojaatlar') }}</span>
            <span class="block font-display text-[34px] font-semibold leading-10">{{ number_format($stats['tickets'], 0, '.', ' ') }}</span>
        </div>
        <div class="ui-card !p-[18px]">
            <span class="text-xs text-muted">{{ __('Faol xodimlar') }}</span>
            <span class="block font-display text-[34px] font-semibold leading-10">{{ number_format($stats['staff'], 0, '.', ' ') }}</span>
        </div>
        <div class="ui-card !p-[18px]">
            <span class="text-xs text-muted">{{ __('Yopilmagan') }}</span>
            <span class="block font-display text-[34px] font-semibold leading-10">{{ number_format($stats['open'], 0, '.', ' ') }}</span>
        </div>
    </div>
</x-public-layout>

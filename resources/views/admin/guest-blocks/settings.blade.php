@php
    $numberField = function (string $name, string $label, $value, string $hint = '', string $step = '1') {
        return ['name' => $name, 'label' => $label, 'value' => old($name, $value), 'hint' => $hint, 'step' => $step];
    };

    $sections = [
        __('Qurilma limiti') => [
            'desc' => __("Bitta brauzer/telefon (cookie bo'yicha) yubora oladigan murojaatlar soni."),
            'fields' => [
                $numberField('device_per_hour', __('Soatiga'), $limits['device_per_hour']),
                $numberField('device_per_day', __('Kuniga'), $limits['device_per_day']),
            ],
        ],
        __('IP limiti') => [
            'desc' => __("Bitta IP manzildan. Institut tarmog'ida ko'p kompyuter bitta IP orqali chiqishi mumkin — shuni hisobga oling."),
            'fields' => [
                $numberField('ip_per_hour', __('Soatiga'), $limits['ip_per_hour']),
                $numberField('ip_per_day', __('Kuniga'), $limits['ip_per_day']),
                $numberField('post_per_minute', __('Daqiqasiga (har qanday POST)'), $limits['post_per_minute'], __("Oshib ketsa 429 xato — blok yozilmaydi")),
            ],
        ],
        __('Email / telefon limiti') => [
            'desc' => __('Bitta email yoki telefon raqamidan.'),
            'fields' => [
                $numberField('contact_per_day', __('Kuniga'), $limits['contact_per_day']),
            ],
        ],
        __('Bot va takroriy murojaat') => [
            'desc' => '',
            'fields' => [
                $numberField('min_fill_seconds', __("Formani to'ldirish uchun minimal vaqt (soniya)"), $limits['min_fill_seconds'], __("0 = o'chirilgan")),
                $numberField('duplicate_window_hours', __('Bir xil tavsifni qayta yuborish taqiqi (soat)'), $limits['duplicate_window_hours'], __("0 = o'chirilgan")),
            ],
        ],
        __('Fayllar') => [
            'desc' => __("Faqat mehmon formasi uchun. Ro'yxatdan o'tgan foydalanuvchilarga ta'sir qilmaydi."),
            'fields' => [
                $numberField('max_files', __('Maksimal fayllar soni'), $limits['max_files'], __("0 = fayl yuklab bo'lmaydi")),
                $numberField('max_file_size_mb', __('Har bir fayl hajmi (MB)'), round($limits['max_file_size_kb'] / 1024, 1), '', '0.5'),
            ],
        ],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl font-bold">{{ __('Mehmon himoyasi sozlamalari') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-none space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        @include('admin.guest-blocks.partials.tabs')

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ __('Saqlanmadi. Iltimos, xatolarni to\'g\'rilang.') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.guest-blocks.settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-6 xl:grid-cols-2">
                @foreach ($sections as $title => $section)
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="font-semibold text-slate-950">{{ $title }}</h3>
                        @if ($section['desc'])
                            <p class="mt-1 text-sm text-slate-500">{{ $section['desc'] }}</p>
                        @endif
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            @foreach ($section['fields'] as $field)
                                <label class="block">
                                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $field['label'] }}</span>
                                    <input type="number" min="0" step="{{ $field['step'] }}" name="{{ $field['name'] }}" value="{{ $field['value'] }}" required class="w-full rounded-md border-slate-300 shadow-sm" />
                                    @if ($field['hint'])
                                        <span class="mt-1 block text-xs text-slate-400">{{ $field['hint'] }}</span>
                                    @endif
                                    <x-input-error :messages="$errors->get($field['name'])" class="mt-1" />
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-semibold text-slate-950">{{ __('Ishonchli IP manzillar') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ __('Bu manzillarga IP limitlari va IP bloklari ta\'sir qilmaydi (qurilma limiti baribir ishlaydi). Har qatorga bitta IP yoki tarmoq:') }} <code>10.0.0.5</code>, <code>192.168.1.0/24</code>.
                    </p>
                    <textarea name="whitelist_ips" rows="6" class="mt-4 w-full rounded-md border-slate-300 font-mono text-sm shadow-sm" placeholder="192.168.1.0/24">{{ old('whitelist_ips', implode("\n", $limits['whitelist_ips'] ?? [])) }}</textarea>
                    <x-input-error :messages="$errors->get('whitelist_ips')" class="mt-1" />
                    <p class="mt-2 text-xs text-slate-500">{{ __('Sizning hozirgi IP manzilingiz:') }} <span class="font-mono font-semibold text-slate-700">{{ $currentIp }}</span></p>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-semibold text-slate-950">{{ __('Telegram ogohlantirish') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Yangi blok paydo bo\'lganda Telegram\'i ulangan adminlarga xabar yuboriladi.') }}</p>
                    @unless ($telegramConfigured)
                        <p class="mt-2 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800">{{ __('Telegram bot sozlanmagan — xabarlar yuborilmaydi.') }}</p>
                    @endunless
                    <label class="mt-4 inline-flex items-center gap-3 text-sm font-semibold text-slate-700">
                        <input type="hidden" name="alert_enabled" value="0">
                        <input type="checkbox" name="alert_enabled" value="1" @checked(old('alert_enabled', $limits['alert_enabled'])) class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-600">
                        {{ __('Ogohlantirish yoqilgan') }}
                    </label>
                    <label class="mt-4 block max-w-xs">
                        <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Soatiga ko\'pi bilan xabar') }}</span>
                        <input type="number" min="1" name="alert_per_hour" value="{{ old('alert_per_hour', $limits['alert_per_hour']) }}" required class="w-full rounded-md border-slate-300 shadow-sm" />
                        <x-input-error :messages="$errors->get('alert_per_hour')" class="mt-1" />
                    </label>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-semibold text-slate-950">{{ __('Cloudflare Turnstile (CAPTCHA)') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ __('Kalitlarni') }} <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" rel="noopener" class="text-cyan-700 underline">{{ __('Cloudflare panelidan') }}</a> {{ __('oling. Site key bo\'sh bo\'lsa CAPTCHA o\'chirilgan.') }}
                    </p>
                    <p class="mt-2 text-xs font-semibold {{ $turnstileSiteKey && $turnstileSecretSet ? 'text-emerald-700' : 'text-slate-500' }}">
                        {{ __('Holat') }}: {{ $turnstileSiteKey && $turnstileSecretSet ? __('Yoqilgan') : __("O'chirilgan") }}
                    </p>
                    <div class="mt-4 grid gap-4">
                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Site key') }}</span>
                            <input name="turnstile_site_key" value="{{ old('turnstile_site_key', $turnstileSiteKey) }}" autocomplete="off" class="w-full rounded-md border-slate-300 font-mono text-sm shadow-sm" />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Secret key') }}</span>
                            <input type="password" name="turnstile_secret_key" autocomplete="new-password" placeholder="{{ $turnstileSecretSet ? __("Saqlangan — o'zgartirish uchun yangisini kiriting") : __('Kiritilmagan') }}" class="w-full rounded-md border-slate-300 font-mono text-sm shadow-sm" />
                        </label>
                        <label class="inline-flex items-center gap-3 text-sm text-slate-600">
                            <input type="checkbox" name="turnstile_clear" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-600">
                            {{ __("Kalitlarni o'chirish (CAPTCHA'ni o'chirish)") }}
                        </label>
                    </div>
                </section>
            </div>

            <div class="flex justify-end">
                <button class="rounded-md bg-cyan-700 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-cyan-800">{{ __('Saqlash') }}</button>
            </div>
        </form>
    </div>
</x-app-layout>

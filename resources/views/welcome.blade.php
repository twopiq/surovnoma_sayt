<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>RTT Markazi Elektron Murojaatlar Tizimi</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600;space-grotesk:500,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="landing-shell-bg text-slate-900">
        <div class="mx-auto flex min-h-screen max-w-7xl flex-col px-4 py-8 sm:px-6 lg:px-8">
            <header class="flex items-center justify-between rounded-full bg-white/10 px-5 py-3 text-white backdrop-blur">
                <div class="flex items-center gap-3">
                    <x-application-logo class="h-10 w-10 fill-current text-cyan-200" />
                    <div>
                        <div class="font-['Space_Grotesk'] text-sm font-bold uppercase tracking-[0.24em]">RTT Markazi</div>
                        <div class="text-sm text-cyan-50/80">Elektron murojaatlar boshqaruvi</div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('guest.create') }}" class="rounded-full border border-white/30 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">Guest forma</a>
                    <a href="{{ route('login') }}" class="rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-cyan-50">Kirish</a>
                </div>
            </header>

            <div class="grid flex-1 items-center gap-12 py-12 lg:grid-cols-[1.1fr_0.9fr]">
                <section class="text-white">
                    <span class="inline-flex rounded-full border border-cyan-300/30 bg-cyan-300/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-cyan-100">Laravel MVP</span>
                    <h1 class="mt-6 max-w-3xl font-['Space_Grotesk'] text-4xl font-bold leading-tight sm:text-5xl">
                        Murojaatlarni yo‘qolmaydigan, kuzatiladigan va SLA bo‘yicha boshqariladigan yagona tizim.
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg text-cyan-50/85">
                        Institut xodimlari, operatorlar, ijrochilar va rahbariyat uchun yagona raqamli dispetcherlik muhiti.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('register') }}" class="rounded-full bg-cyan-300 px-5 py-3 text-sm font-semibold text-slate-950 shadow-lg shadow-cyan-900/20 transition hover:bg-cyan-200">Ro‘yxatdan o‘tish</a>
                        <a href="{{ route('guest.track') }}" class="rounded-full border border-white/25 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">Guest kuzatuvi</a>
                    </div>
                </section>

                <section class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
                    <div class="rounded-3xl bg-white p-6 shadow-xl shadow-slate-300/30">
                        <div class="text-sm text-slate-500">Jami murojaatlar</div>
                        <div class="mt-2 font-['Space_Grotesk'] text-4xl font-bold text-slate-900">{{ $stats['tickets'] }}</div>
                    </div>
                    <div class="rounded-3xl bg-white p-6 shadow-xl shadow-slate-300/30">
                        <div class="text-sm text-slate-500">Faol xodimlar</div>
                        <div class="mt-2 font-['Space_Grotesk'] text-4xl font-bold text-slate-900">{{ $stats['staff'] }}</div>
                    </div>
                    <div class="rounded-3xl bg-slate-900 p-6 text-white shadow-xl shadow-slate-400/20">
                        <div class="text-sm text-slate-300">Yopilmagan murojaatlar</div>
                        <div class="mt-2 font-['Space_Grotesk'] text-4xl font-bold">{{ $stats['open'] }}</div>
                    </div>
                </section>
            </div>
        </div>
    </body>
</html>

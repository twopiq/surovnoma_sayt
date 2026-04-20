<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>RTT Markazi Elektron Murojaatlar Tizimi</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600;space-grotesk:500,700&display=swap" rel="stylesheet" />
        <script>
            document.documentElement.dataset.theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="landing-shell-bg text-slate-900">
        <div class="theme-hero min-h-screen">
            <div class="mx-auto flex min-h-screen max-w-7xl flex-col px-4 py-6 sm:px-6 lg:px-8">
                <header class="theme-panel flex items-center justify-between rounded-lg border px-5 py-3 shadow-sm">
                    <a href="{{ route('home') }}" class="flex items-center gap-3">
                        <x-application-logo class="h-10 w-10 fill-current text-emerald-700" />
                        <div>
                            <div class="font-['Space_Grotesk'] text-sm font-bold uppercase tracking-[0.18em] theme-ink">RTT Markazi</div>
                            <div class="text-sm theme-muted">Elektron murojaatlar boshqaruvi</div>
                        </div>
                    </a>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <x-theme-toggle />
                        <a href="{{ route('guest.create') }}" class="theme-secondary rounded-md border px-4 py-2 text-sm font-semibold transition">Guest forma</a>
                        <a href="{{ route('login') }}" class="theme-primary rounded-md px-4 py-2 text-sm font-semibold transition">Kirish</a>
                    </div>
                </header>

                <div class="grid flex-1 items-center gap-10 py-10 lg:grid-cols-[1fr_0.9fr]">
                    <section>
                        <p class="inline-flex rounded-md border px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] theme-secondary">Murojaatlar markazi</p>
                        <h1 class="mt-6 max-w-3xl font-['Space_Grotesk'] text-4xl font-bold leading-tight theme-ink sm:text-5xl">
                            Murojaat yuboring, javob jarayonini kuzating, natijani yo'qotmang.
                        </h1>
                        <p class="mt-6 max-w-2xl text-lg leading-8 theme-muted">
                            Xodimlar, operatorlar, ijrochilar va rahbariyat uchun yagona tartibli murojaat muhiti. Har bir murojaat Ticket ID bilan saqlanadi va holati aniq ko'rinadi.
                        </p>

                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ route('register') }}" class="theme-primary rounded-md px-5 py-3 text-sm font-semibold shadow-sm transition">Akkaunt yaratish</a>
                            <a href="{{ route('guest.track') }}" class="theme-secondary rounded-md border px-5 py-3 text-sm font-semibold transition">Guest kuzatuvi</a>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="theme-panel rounded-lg border p-6 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-sm font-semibold theme-muted">Bugungi ish ritmi</div>
                                    <div class="mt-2 font-['Space_Grotesk'] text-3xl font-bold theme-ink">Aniq navbat</div>
                                </div>
                                <div class="rounded-md px-3 py-2 text-sm font-bold theme-primary">RTT</div>
                            </div>
                            <div class="mt-6 space-y-3">
                                <div class="theme-soft-panel rounded-md border p-4">
                                    <div class="text-sm font-semibold theme-ink">1. Murojaat qabul qilinadi</div>
                                    <div class="mt-1 text-sm theme-muted">Ticket ID va tracking code yaratiladi.</div>
                                </div>
                                <div class="theme-soft-panel rounded-md border p-4">
                                    <div class="text-sm font-semibold theme-ink">2. Kategoriya bo'yicha ajratiladi</div>
                                    <div class="mt-1 text-sm theme-muted">Muammo turi bo'limdan alohida ko'rinadi.</div>
                                </div>
                                <div class="theme-soft-panel rounded-md border p-4">
                                    <div class="text-sm font-semibold theme-ink">3. Ijrochi bajarilganini tasdiqlaydi</div>
                                    <div class="mt-1 text-sm theme-muted">Holat va izohlar kuzatuv sahifasida saqlanadi.</div>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="theme-panel rounded-lg border p-5 shadow-sm">
                                <div class="text-sm theme-muted">Jami murojaatlar</div>
                                <div class="mt-2 font-['Space_Grotesk'] text-3xl font-bold theme-ink">{{ $stats['tickets'] }}</div>
                            </div>
                            <div class="theme-panel rounded-lg border p-5 shadow-sm">
                                <div class="text-sm theme-muted">Faol xodimlar</div>
                                <div class="mt-2 font-['Space_Grotesk'] text-3xl font-bold theme-ink">{{ $stats['staff'] }}</div>
                            </div>
                            <div class="theme-panel rounded-lg border p-5 shadow-sm">
                                <div class="text-sm theme-muted">Yopilmagan</div>
                                <div class="mt-2 font-['Space_Grotesk'] text-3xl font-bold theme-ink">{{ $stats['open'] }}</div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </body>
</html>

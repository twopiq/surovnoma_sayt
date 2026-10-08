@php
    $statusCode = $statusCode ?? 500;
    $headline = $headline ?? 'Kutilmagan xatolik yuz berdi';
    $eyebrow = $eyebrow ?? 'System notice';
    $lead = $lead ?? "So'rovni bajarishda muammo yuz berdi. Iltimos, birozdan keyin qayta urinib ko'ring.";
    $details = $details ?? [];
    $homeLabel = $homeLabel ?? 'Asosiy sahifaga qaytish';
@endphp

<!DOCTYPE html>
<html lang="uz">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $statusCode }} - {{ $headline }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@500&family=IBM+Plex+Sans+Condensed:wght@600&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="on-dark bg-[#050606] font-sans antialiased" data-role="murojaatchi">
        <main class="relative min-h-screen overflow-hidden">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,rgba(32,88,106,0.38),transparent_26%),radial-gradient(circle_at_80%_12%,rgba(255,255,255,0.08),transparent_22%),linear-gradient(180deg,rgba(255,255,255,0.04),transparent_45%)]"></div>
            <div class="pointer-events-none absolute inset-x-0 top-24 h-px bg-[#fff]/10"></div>

            <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-5 py-6 sm:px-8 lg:px-10">
                <header class="grid items-center gap-4 text-sm text-[#fff]/70 md:grid-cols-[1fr_auto_1fr]">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 justify-self-start transition hover:text-[#fff]">
                        <x-application-logo class="h-8 w-8 fill-current text-[#fff]" />
                        <span class="font-display font-bold uppercase tracking-[0.22em]">RTT</span>
                    </a>

                    <nav class="flex flex-wrap justify-center gap-2 font-semibold tracking-wide">
                        <a href="{{ route('guest.create') }}" class="transition hover:text-[#fff]">[ Guest forma ]</a>
                        <a href="{{ route('guest.track') }}" class="transition hover:text-[#fff]">[ Guest kuzatuvi ]</a>
                        <a href="{{ route('register') }}" class="transition hover:text-[#fff]">[ Ro'yxatdan o'tish ]</a>
                        <a href="{{ route('login') }}" class="transition hover:text-[#fff]">[ Kirish ]</a>
                    </nav>

                    <a href="{{ route('home') }}" class="hidden justify-self-end transition hover:text-[#fff] md:inline-flex">Asosiy sahifa</a>
                </header>

                <section class="grid flex-1 items-end gap-10 pb-12 pt-20 lg:grid-cols-[1.05fr_0.95fr] lg:pb-20">
                    <div class="lg:pb-16">
                        <div class="font-display text-[8rem] font-bold leading-none tracking-[-0.12em] text-[#fff] sm:text-[13rem] lg:text-[17rem]">
                            {{ $statusCode }}
                        </div>
                        <div class="mt-6 max-w-4xl font-display text-6xl font-bold leading-[0.85] tracking-[-0.08em] text-[#fff] sm:text-7xl lg:text-8xl">
                            {{ $headline }}
                        </div>
                    </div>

                    <div class="max-w-2xl border-t border-[#fff]/15 pt-8 lg:mb-20">
                        <div class="text-sm font-semibold text-[#fff]/55">{{ $eyebrow }}</div>
                        <p class="mt-3 font-display text-3xl leading-tight tracking-[-0.04em] text-[#fff]/90 sm:text-4xl">
                            {{ $lead }}
                        </p>

                        @if (count($details))
                            <dl class="mt-8 grid gap-4 border-y border-[#fff]/10 py-6 font-mono text-sm">
                                @foreach ($details as $label => $value)
                                    <div>
                                        <dt class="text-[#fff]/35">&lt;{{ $label }}&gt;</dt>
                                        <dd class="mt-1 text-[#fff]">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif

                        <div class="mt-8 flex flex-wrap gap-4 text-lg font-semibold text-[#fff]/80">
                            <button type="button" onclick="history.back()" class="transition hover:text-[#fff]">[ Ortga qaytish ]</button>
                            <a href="{{ route('home') }}" class="transition hover:text-[#fff]">[ {{ $homeLabel }} ]</a>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>

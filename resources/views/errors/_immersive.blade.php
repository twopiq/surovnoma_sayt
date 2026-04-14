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
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600;space-grotesk:500,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#050606] font-sans text-white antialiased">
        <main class="relative min-h-screen overflow-hidden">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,rgba(32,88,106,0.38),transparent_26%),radial-gradient(circle_at_80%_12%,rgba(255,255,255,0.08),transparent_22%),linear-gradient(180deg,rgba(255,255,255,0.04),transparent_45%)]"></div>
            <div class="pointer-events-none absolute inset-x-0 top-24 h-px bg-white/10"></div>

            <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-5 py-6 sm:px-8 lg:px-10">
                <header class="grid items-center gap-4 text-sm text-white/70 md:grid-cols-[1fr_auto_1fr]">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 justify-self-start transition hover:text-white">
                        <x-application-logo class="h-8 w-8 fill-current text-white" />
                        <span class="font-['Space_Grotesk'] font-bold uppercase tracking-[0.22em]">RTT</span>
                    </a>

                    <nav class="flex flex-wrap justify-center gap-2 font-semibold tracking-wide">
                        <a href="{{ route('guest.create') }}" class="transition hover:text-white">[ Guest forma ]</a>
                        <a href="{{ route('guest.track') }}" class="transition hover:text-white">[ Guest kuzatuvi ]</a>
                        <a href="{{ route('register') }}" class="transition hover:text-white">[ Ro'yxatdan o'tish ]</a>
                        <a href="{{ route('login') }}" class="transition hover:text-white">[ Kirish ]</a>
                    </nav>

                    <a href="{{ route('home') }}" class="hidden justify-self-end transition hover:text-white md:inline-flex">Asosiy sahifa</a>
                </header>

                <section class="grid flex-1 items-end gap-10 pb-12 pt-20 lg:grid-cols-[1.05fr_0.95fr] lg:pb-20">
                    <div class="lg:pb-16">
                        <div class="font-['Space_Grotesk'] text-[8rem] font-bold leading-none tracking-[-0.12em] text-white sm:text-[13rem] lg:text-[17rem]">
                            {{ $statusCode }}
                        </div>
                        <div class="mt-6 max-w-4xl font-['Space_Grotesk'] text-6xl font-bold leading-[0.85] tracking-[-0.08em] text-white sm:text-7xl lg:text-8xl">
                            {{ $headline }}
                        </div>
                    </div>

                    <div class="max-w-2xl border-t border-white/15 pt-8 lg:mb-20">
                        <div class="text-sm font-semibold text-white/55">{{ $eyebrow }}</div>
                        <p class="mt-3 font-['Space_Grotesk'] text-3xl leading-tight tracking-[-0.04em] text-white/90 sm:text-4xl">
                            {{ $lead }}
                        </p>

                        @if (count($details))
                            <dl class="mt-8 grid gap-4 border-y border-white/10 py-6 font-mono text-sm">
                                @foreach ($details as $label => $value)
                                    <div>
                                        <dt class="text-white/35">&lt;{{ $label }}&gt;</dt>
                                        <dd class="mt-1 text-white">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif

                        <div class="mt-8 flex flex-wrap gap-4 text-lg font-semibold text-white/80">
                            <button type="button" onclick="history.back()" class="transition hover:text-white">[ Ortga qaytish ]</button>
                            <a href="{{ route('home') }}" class="transition hover:text-white">[ {{ $homeLabel }} ]</a>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>

@props(['title' => null, 'footerNote' => "Savol bo'lsa: RTT markaziga murojaat qiling"])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ? $title.' · ' : '' }}RTT Markazi Elektron Murojaatlar Tizimi</title>
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@500&family=IBM+Plex+Sans+Condensed:wght@600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
        <script>
            document.documentElement.dataset.theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-canvas font-sans text-ink antialiased" data-role="{{ auth()->user()?->themeRole() ?? 'murojaatchi' }}">
        <div class="mx-auto flex min-h-screen max-w-[1180px] flex-col px-4 sm:px-8">
            <header class="flex items-center justify-between gap-3 py-4">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <x-application-logo class="h-10 w-10 shrink-0" />
                    <span>
                        <b class="block font-display text-[17px] font-bold leading-5 tracking-[.01em]">RTT Markazi</b>
                        <small class="block text-xs text-muted">Elektron murojaatlar tizimi</small>
                    </span>
                </a>
                <div class="flex items-center gap-2">
                    <x-theme-toggle />
                    @auth
                        <a href="{{ route('app.home') }}" class="btn btn-primary">Kabinet</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary">Kirish</a>
                    @endauth
                </div>
            </header>

            <main class="flex-1">
                @include('partials.flash')
                {{ $slot }}
            </main>

            <footer class="mt-12 flex flex-wrap justify-between gap-2 border-t border-line py-7 text-[13px] text-muted">
                <span>© RTT Markazi · Raqamli ta'lim texnologiyalari markazi</span>
                <span>{{ $footerNote }}</span>
            </footer>
        </div>
    </body>
</html>

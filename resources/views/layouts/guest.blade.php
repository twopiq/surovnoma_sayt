<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'RTT Markazi Elektron Murojaatlar Tizimi') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600;space-grotesk:500,700&display=swap" rel="stylesheet" />

        <script>
            document.documentElement.dataset.theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="guest-shell-bg font-sans text-slate-900 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-8">
            <div class="mb-6 flex w-full max-w-xl items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="flex items-center gap-3 text-slate-800">
                    <x-application-logo class="h-12 w-12 fill-current text-cyan-700" />
                    <div>
                        <div class="font-['Space_Grotesk'] text-lg font-bold theme-ink">RTT Markazi</div>
                        <div class="text-sm theme-muted">Elektron murojaatlar tizimi</div>
                    </div>
                </a>
                <div>
                    <x-theme-toggle />
                </div>
            </div>

            <div class="theme-panel w-full max-w-xl overflow-hidden rounded-2xl border px-6 py-6 shadow-xl shadow-slate-200/60 backdrop-blur">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>

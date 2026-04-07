<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'RTT Markazi Elektron Murojaatlar Tizimi') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600;space-grotesk:500,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="guest-shell-bg font-sans text-slate-900 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-8">
            <a href="{{ route('home') }}" class="mb-6 flex items-center gap-3 text-slate-800">
                <x-application-logo class="h-12 w-12 fill-current text-cyan-700" />
                <div>
                    <div class="font-['Space_Grotesk'] text-lg font-bold">RTT Markazi</div>
                    <div class="text-sm text-slate-500">Elektron murojaatlar tizimi</div>
                </div>
            </a>

            <div class="w-full max-w-xl overflow-hidden rounded-2xl border border-white/70 bg-white/90 px-6 py-6 shadow-xl shadow-slate-200/60 backdrop-blur">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>

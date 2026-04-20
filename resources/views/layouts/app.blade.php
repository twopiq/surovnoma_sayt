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
        @livewireStyles
    </head>
    <body class="bg-slate-50 font-sans antialiased text-slate-900">
        <div>
            @include('layouts.navigation')

            <div class="min-w-0 lg:pl-64">
                @isset($header)
                    <header class="border-b border-slate-200/80 bg-white/90 backdrop-blur">
                        <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="app-shell-bg pb-12">
                    @include('partials.flash')
                    {{ $slot }}
                </main>
            </div>

            @unless (request()->routeIs('notifications.*'))
                @include('partials.notification-toasts')
            @endunless
        </div>

        @livewireScripts
    </body>
</html>

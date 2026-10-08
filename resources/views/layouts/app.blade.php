<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'RTT Markazi Elektron Murojaatlar Tizimi') }}</title>

        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@500&family=IBM+Plex+Sans+Condensed:wght@600&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">

        <script>
            document.documentElement.dataset.theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-canvas font-sans text-ink antialiased" data-role="{{ auth()->user()?->themeRole() ?? 'murojaatchi' }}">
        <div>
            @include('layouts.navigation')

            <div class="min-w-0 lg:pl-[232px]">
                <header class="page-header border-b border-line bg-canvas">
                    <div class="px-4 pb-5 pt-6 sm:px-6 lg:px-8">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            @isset($header)
                                <div class="min-w-0 flex-1">
                                    {{ $header }}
                                </div>
                            @else
                                <div class="min-w-0 flex-1"></div>
                            @endisset

                            <div class="shrink-0 lg:pt-0.5">
                                @include('partials.breadcrumbs')
                            </div>
                        </div>
                    </div>
                </header>

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

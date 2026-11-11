{{-- "Ingliz tili" alohida sayt ko'rinishida ochiladi: asosiy saytning yon menyusi va sarlavhasi yo'q, faqat qaytish tugmasi. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __('Ingliz tili') }}</title>

        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@500&family=IBM+Plex+Sans+Condensed:wght@600&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">

        <script>
            document.documentElement.dataset.theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ asset('english-assets/english.css') }}?v={{ filemtime(public_path('english-assets/english.css')) }}">
    </head>
    <body class="bg-canvas font-sans text-ink antialiased" data-role="{{ auth()->user()?->themeRole() ?? 'murojaatchi' }}">
        <div class="myen myen-page">
            <header class="top">
                <a class="brand" href="#/"><i></i>{{ __('Ingliz tili') }}</a>
                <div class="rowf">
                    <a class="btn" href="{{ route('app.home') }}">← {{ __("So'rovnomaga qaytish") }}</a>
                    <x-theme-toggle />
                </div>
            </header>
            <main id="english-app" data-sets-url="{{ route('english.sets.index') }}"></main>
            <div class="toast" id="english-toast" role="status" hidden></div>
        </div>

        <script src="{{ asset('english-assets/english.js') }}?v={{ filemtime(public_path('english-assets/english.js')) }}" defer></script>
    </body>
</html>

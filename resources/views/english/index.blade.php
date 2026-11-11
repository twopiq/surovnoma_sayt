<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl font-bold">{{ __('Ingliz tili') }}</h2>
        <p class="mt-1 text-sm text-muted">{{ __("So'z yodlash kartochkalari") }}</p>
    </x-slot>

    <link rel="stylesheet" href="{{ asset('english-assets/english.css') }}?v={{ filemtime(public_path('english-assets/english.css')) }}">

    <div class="px-4 pt-8 sm:px-6 lg:px-8">
        <div id="english-app" class="myen" data-sets-url="{{ route('english.sets.index') }}"></div>
    </div>
    <div class="myen"><div class="toast" id="english-toast" role="status" hidden></div></div>

    <script src="{{ asset('english-assets/english.js') }}?v={{ filemtime(public_path('english-assets/english.js')) }}" defer></script>
</x-app-layout>

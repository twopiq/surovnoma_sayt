@props(['tone' => 'surface'])

@php
    $buttonClasses = $tone === 'sidebar'
        ? 'border-white/20 bg-white/10 text-cyan-50 hover:bg-white/20'
        : 'border-slate-300 bg-white text-slate-700 hover:bg-emerald-50';
@endphp

<button
    type="button"
    x-data="{ theme: document.documentElement.dataset.theme || 'light' }"
    x-init="window.addEventListener('theme-changed', event => theme = event.detail)"
    x-on:click="window.toggleTheme()"
    {{ $attributes->merge(['class' => "inline-flex h-9 w-9 items-center justify-center rounded-md border shadow-sm transition {$buttonClasses}"]) }}
    aria-label="Mavzuni almashtirish"
>
    <svg x-show="theme === 'dark'" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path d="M10 2a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0V3a1 1 0 0 1 1-1ZM4.22 4.22a1 1 0 0 1 1.42 0l.7.7a1 1 0 0 1-1.42 1.42l-.7-.7a1 1 0 0 1 0-1.42ZM16.78 4.22a1 1 0 0 1 0 1.42l-.7.7a1 1 0 1 1-1.42-1.42l.7-.7a1 1 0 0 1 1.42 0ZM10 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM2 10a1 1 0 0 1 1-1h1a1 1 0 1 1 0 2H3a1 1 0 0 1-1-1ZM15 10a1 1 0 0 1 1-1h1a1 1 0 1 1 0 2h-1a1 1 0 0 1-1-1ZM5.64 13.66a1 1 0 0 1 .7 1.7l-.7.7a1 1 0 1 1-1.42-1.42l.7-.7a1 1 0 0 1 .72-.28ZM14.66 14.66a1 1 0 0 1 1.42 0l.7.7a1 1 0 0 1-1.42 1.42l-.7-.7a1 1 0 0 1 0-1.42ZM10 15a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0v-1a1 1 0 0 1 1-1Z" />
    </svg>
    <svg x-show="theme !== 'dark'" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M14.5 13.88A6.5 6.5 0 0 1 6.12 5.5a6.5 6.5 0 1 0 8.38 8.38Z" clip-rule="evenodd" />
    </svg>
    <span class="sr-only">Mavzuni almashtirish</span>
</button>

@props(['active'])

@php
$classes = 'inline-flex items-center gap-2 rounded-md px-3 py-2 text-[13px] font-medium leading-[18px] transition';
@endphp

@if ($active ?? false)
    <span {{ $attributes->except('href')->merge(['class' => $classes.' cursor-default bg-accent-soft text-accent-strong']) }} aria-current="page">
        {{ $slot }}
    </span>
@else
    <a {{ $attributes->merge(['class' => $classes.' text-muted hover:bg-surface hover:text-ink']) }}>
        {{ $slot }}
    </a>
@endif

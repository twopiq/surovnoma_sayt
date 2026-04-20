@props(['status', 'size' => 'sm'])

@php
    $sizeClasses = match ($size) {
        'xs' => 'px-2 py-0.5 text-[11px]',
        'md' => 'px-3 py-1.5 text-sm',
        default => 'px-3 py-1 text-xs',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full font-semibold ring-1 {$sizeClasses}", 'style' => $status->badgeStyle()]) }}>
    {{ $status->label() }}
</span>

@props(['status', 'size' => 'sm'])

@php
    $sizeClass = match ($size) {
        'xs' => 'status-badge--xs',
        'md' => 'status-badge--md',
        default => '',
    };
@endphp

<span {{ $attributes->class([$status->badgeCssClass(), $sizeClass]) }}>{{ $status->label() }}</span>

@props(['priority', 'label' => true])

<span {{ $attributes->class('inline-flex items-center whitespace-nowrap') }}>
    <span class="ui-pr" aria-hidden="true">
        @for ($i = 1; $i <= 4; $i++)
            <i @class(['on' => $i <= $priority->level()])></i>
        @endfor
    </span>
    @if ($label)
        {{ $priority->label() }}
    @else
        <span class="sr-only">{{ $priority->label() }}</span>
    @endif
</span>

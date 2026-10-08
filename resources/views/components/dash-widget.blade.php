@props(['key', 'title' => null, 'subtitle' => null, 'visibility' => [], 'canManage' => false, 'compact' => false])

@php
    $visible = (bool) ($visibility[$key] ?? false);
@endphp

<section {{ $attributes->class(['dash-card', 'dash-card--off' => $canManage && ! $visible]) }}>
    <div @class(['dash-card__hd', 'mb-0' => $compact])>
        <div class="min-w-0">
            @if ($title)
                <h3>{{ $title }}</h3>
            @endif
            @if ($subtitle)
                <p>{{ $subtitle }}</p>
            @endif
            {{ $head ?? '' }}
        </div>

        @if ($canManage)
            <form method="POST" action="{{ route('app.dashboard.widgets.toggle', $key) }}" class="shrink-0">
                @csrf
                <button type="submit" @class(['dash-vis', 'dash-vis--on' => $visible, 'dash-vis--icon' => $compact])
                    title="{{ $visible ? __('Rahbarda ko\'rinadi — yashirish') : __('Rahbarda yashirin — ko\'rsatish') }}"
                    aria-pressed="{{ $visible ? 'true' : 'false' }}">
                    <i aria-hidden="true"></i>
                    <span @class(['sr-only' => $compact])>{{ $visible ? __("Rahbarda ko'rinadi") : __('Rahbarda yashirin') }}</span>
                </button>
            </form>
        @endif
    </div>

    <div class="dash-card__body">
        {{ $slot }}
    </div>
</section>

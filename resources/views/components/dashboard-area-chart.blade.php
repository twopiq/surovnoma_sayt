@props([
    'items' => [],
    'height' => 260,
    'stroke' => '#36c1ba',
    'fill' => 'rgba(54, 193, 186, 0.24)',
])

@php
    $items = collect($items)->values();
    $width = 760;
    $paddingLeft = 26;
    $paddingRight = 18;
    $paddingTop = 16;
    $paddingBottom = 34;
    $plotWidth = max(1, $width - $paddingLeft - $paddingRight);
    $plotHeight = max(1, $height - $paddingTop - $paddingBottom);
    $maxValue = max(1, (int) $items->max('value'));
    $steps = max(1, $items->count() - 1);
    $points = $items->map(function (array $item, int $index) use ($paddingLeft, $paddingTop, $plotWidth, $plotHeight, $steps, $maxValue): array {
        $x = $paddingLeft + (($plotWidth / $steps) * $index);
        $y = $paddingTop + $plotHeight - (($plotHeight * ($item['value'] ?? 0)) / $maxValue);

        return [
            'x' => round($x, 2),
            'y' => round($y, 2),
            'label' => $item['label'] ?? '',
            'value' => $item['value'] ?? 0,
        ];
    });

    $line = $points->map(fn (array $point) => $point['x'].','.$point['y'])->implode(' ');
    $area = $points->isNotEmpty()
        ? $paddingLeft.','.($paddingTop + $plotHeight).' '.$line.' '.data_get($points->last(), 'x', $paddingLeft).','.($paddingTop + $plotHeight)
        : '';
    $gridValues = collect(range(0, 4))->map(fn (int $index) => round(($maxValue / 4) * (4 - $index)));
@endphp

<div class="h-full w-full overflow-hidden">
    <svg viewBox="0 0 {{ $width }} {{ $height }}" class="h-full w-full" preserveAspectRatio="none" aria-hidden="true">
        @foreach ($gridValues as $index => $value)
            @php($y = $paddingTop + (($plotHeight / 4) * $index))
            <line x1="{{ $paddingLeft }}" y1="{{ $y }}" x2="{{ $width - $paddingRight }}" y2="{{ $y }}" stroke="rgba(148, 163, 184, 0.22)" stroke-dasharray="4 6" />
            <text x="0" y="{{ $y + 4 }}" font-size="12" fill="rgba(100, 116, 139, 0.9)">{{ $value }}</text>
        @endforeach

        @if ($area !== '')
            <polygon points="{{ $area }}" fill="{{ $fill }}" />
            <polyline points="{{ $line }}" fill="none" stroke="{{ $stroke }}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />

            @foreach ($points as $point)
                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3.5" fill="{{ $stroke }}" />
                <text x="{{ $point['x'] }}" y="{{ $height - 10 }}" text-anchor="middle" font-size="12" fill="rgba(71, 85, 105, 0.95)">{{ $point['label'] }}</text>
            @endforeach
        @endif
    </svg>
</div>

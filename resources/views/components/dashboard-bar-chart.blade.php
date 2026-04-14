@props([
    'items',
    'max' => null,
    'accent' => '#3b82f6',
    'emptyText' => "Ma'lumot yo'q.",
    'minWidth' => 760,
    'height' => 320,
])

@php
    $items = collect($items);
    $maxValue = max(1, (int) ($max ?? $items->max('value')));
    $width = max((int) $minWidth, max(1, $items->count()) * 96);
    $height = (int) $height;
    $plotLeft = 48;
    $plotTop = 24;
    $plotBottom = $height - 62;
    $plotRight = $width - 24;
    $plotHeight = $plotBottom - $plotTop;
    $slotWidth = ($plotRight - $plotLeft) / max(1, $items->count());
    $barWidth = min(54, max(22, $slotWidth * 0.56));
    $gradientId = 'chartGradient'.str_replace('.', '', uniqid('', true));
@endphp

@if ($items->isEmpty())
    <div class="flex h-full min-h-64 items-center justify-center rounded-lg bg-slate-50 text-sm text-slate-500">
        {{ $emptyText }}
    </div>
@else
    <div {{ $attributes->merge(['class' => 'h-full overflow-x-auto']) }}>
        <svg
            viewBox="0 0 {{ $width }} {{ $height }}"
            class="h-full min-h-64 w-full"
            style="min-width: {{ $width }}px;"
            role="img"
            aria-label="Dashboard chart"
        >
            <defs>
                <linearGradient id="{{ $gradientId }}" x1="0" x2="0" y1="0" y2="1">
                    <stop offset="0%" stop-color="{{ $accent }}" stop-opacity="0.95" />
                    <stop offset="100%" stop-color="{{ $accent }}" stop-opacity="0.72" />
                </linearGradient>
            </defs>

            @for ($step = 0; $step <= 5; $step++)
                @php
                    $y = $plotBottom - (($plotHeight / 5) * $step);
                    $value = round(($maxValue / 5) * $step, 1);
                @endphp
                <line x1="{{ $plotLeft }}" y1="{{ $y }}" x2="{{ $plotRight }}" y2="{{ $y }}" stroke="#e2e8f0" stroke-width="1" />
                <text x="{{ $plotLeft - 10 }}" y="{{ $y + 4 }}" text-anchor="end" class="fill-slate-400 text-[11px] font-medium">{{ $value }}</text>
            @endfor

            <line x1="{{ $plotLeft }}" y1="{{ $plotTop }}" x2="{{ $plotLeft }}" y2="{{ $plotBottom }}" stroke="#cbd5e1" stroke-width="1" />
            <line x1="{{ $plotLeft }}" y1="{{ $plotBottom }}" x2="{{ $plotRight }}" y2="{{ $plotBottom }}" stroke="#cbd5e1" stroke-width="1" />

            @foreach ($items as $index => $item)
                @php
                    $value = (float) ($item['value'] ?? 0);
                    $label = (string) ($item['label'] ?? '');
                    $barHeight = $value > 0 ? max(4, ($value / $maxValue) * $plotHeight) : 0;
                    $x = $plotLeft + ($slotWidth * $index) + (($slotWidth - $barWidth) / 2);
                    $y = $plotBottom - $barHeight;
                    $fill = $item['hex'] ?? null;
                @endphp

                @if ($barHeight > 0)
                    <rect
                        x="{{ $x }}"
                        y="{{ $y }}"
                        width="{{ $barWidth }}"
                        height="{{ $barHeight }}"
                        rx="8"
                        fill="{{ $fill ?? 'url(#'.$gradientId.')' }}"
                        class="transition duration-150 hover:opacity-80"
                    >
                        <title>{{ $label }}: {{ $value }}</title>
                    </rect>
                @else
                    <circle cx="{{ $x + ($barWidth / 2) }}" cy="{{ $plotBottom }}" r="2" fill="#cbd5e1">
                        <title>{{ $label }}: 0</title>
                    </circle>
                @endif

                <text x="{{ $x + ($barWidth / 2) }}" y="{{ $y - 8 }}" text-anchor="middle" class="fill-slate-600 text-[11px] font-semibold">{{ $value }}</text>
                <text
                    x="{{ $x + ($barWidth / 2) }}"
                    y="{{ $plotBottom + 22 }}"
                    text-anchor="end"
                    transform="rotate(-15 {{ $x + ($barWidth / 2) }} {{ $plotBottom + 22 }})"
                    class="fill-slate-500 text-[11px] font-medium"
                >{{ \Illuminate\Support\Str::limit($label, 18) }}</text>
            @endforeach
        </svg>
    </div>
@endif

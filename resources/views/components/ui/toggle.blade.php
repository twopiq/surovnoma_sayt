@props(['name', 'checked' => false, 'value' => '1', 'title', 'hint' => null, 'boxed' => false])

<label {{ $attributes->class(['ui-switch-row', 'ui-switch-row--boxed' => $boxed]) }}>
    <span class="min-w-0">
        <b class="block text-[14px] font-semibold text-ink">{{ $title }}</b>
        @if ($hint)
            <span class="block text-xs text-muted">{{ $hint }}</span>
        @endif
    </span>
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" class="ui-switch" @checked($checked)>
</label>

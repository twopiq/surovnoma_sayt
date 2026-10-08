@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-[13px] font-medium leading-[18px] text-ink']) }}>
    {{ $value ?? $slot }}
</label>

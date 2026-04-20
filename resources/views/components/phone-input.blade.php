@props([
    'id' => null,
    'name' => 'phone',
    'label' => 'Telefon',
    'value' => '',
    'required' => false,
    'hint' => 'Namuna: +998 99 999 99 99',
])

@php
    $displayId = $id ?: $name.'_display';
    $digits = preg_replace('/\D+/', '', (string) $value);
    $digits = $digits !== '' ? substr($digits, -9) : '';
@endphp

<div
    {{ $attributes }}
    x-data="{
        digits: @js($digits),
        setDigits(value) {
            this.digits = String(value).replace(/\D/g, '').slice(0, 9);
        },
        formatted() {
            const groups = [2, 3, 2, 2];
            let cursor = 0;
            const parts = [];

            for (const size of groups) {
                const chunk = this.digits.slice(cursor, cursor + size);
                if (!chunk) break;
                parts.push(chunk);
                cursor += size;
            }

            return parts.join(' ');
        },
        fullPhone() {
            return this.digits ? `+998 ${this.formatted()}` : '';
        },
        allowOnlyDigits(event) {
            const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];

            if (allowedKeys.includes(event.key) || event.ctrlKey || event.metaKey) {
                return;
            }

            if (!/^\d$/.test(event.key)) {
                event.preventDefault();
            }
        }
    }"
>
    <x-input-label :for="$displayId" :value="$label" />
    <input type="hidden" name="{{ $name }}" :value="fullPhone()">
    <div class="mt-1 flex rounded-md border border-slate-300 bg-white shadow-sm focus-within:border-cyan-500 focus-within:ring-1 focus-within:ring-cyan-500">
        <span class="inline-flex items-center rounded-l-md border-r border-slate-300 bg-slate-50 px-3 text-sm font-semibold text-slate-600">+998</span>
        <input
            id="{{ $displayId }}"
            :value="formatted()"
            @keydown="allowOnlyDigits($event)"
            @input="setDigits($event.target.value)"
            @paste.prevent="setDigits(($event.clipboardData || window.clipboardData).getData('text'))"
            type="text"
            inputmode="numeric"
            autocomplete="tel-national"
            placeholder="99 999 99 99"
            maxlength="12"
            class="block w-full rounded-r-md border-0 bg-transparent px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0"
            @required($required)
        >
    </div>
    @if ($hint)
        <p class="mt-2 text-xs text-slate-400">{{ $hint }}</p>
    @endif
    <x-input-error :messages="$errors->get($name)" class="mt-2" />
</div>

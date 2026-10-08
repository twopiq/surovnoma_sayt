{{-- Til almashtirgich: O'zbekcha / Русский / English. Tanlov sessiyada va foydalanuvchi profilida saqlanadi. --}}
@props(['compact' => false])

@php($current = app()->getLocale())

<div {{ $attributes->class(['locale-switch', 'locale-switch--compact' => $compact]) }} role="group" aria-label="{{ __('Til') }}">
    @foreach (\App\Support\Locales::AVAILABLE as $code => [$name, $short])
        <form method="POST" action="{{ route('locale.switch', $code) }}">
            @csrf
            <button type="submit" lang="{{ $code }}" title="{{ $name }}" @if ($code === $current) aria-current="true" @endif>
                {{ $compact ? $short : $name }}
            </button>
        </form>
    @endforeach
</div>

@props(['messages'])

@php($flatMessages = collect($messages)->flatten()->filter()->all())

@if ($flatMessages)
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 space-y-1']) }}>
        @foreach ($flatMessages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif

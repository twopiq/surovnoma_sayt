@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-md border-line-strong bg-surface px-3 py-2 text-[15px] text-ink shadow-none focus:border-accent focus:ring-accent/35 disabled:bg-sunken disabled:text-muted']) }}>

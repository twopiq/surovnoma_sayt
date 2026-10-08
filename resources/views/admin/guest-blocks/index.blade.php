@php
    $chips = ['active' => __('Faol'), 'unblocked' => __('Olib tashlangan'), 'all' => __('Barchasi')];
    $typeOf = fn ($block) => match (true) {
        $block->scope === \App\Models\GuestBlock::SCOPE_DEVICE => [__('Qurilma'), $block->device_id],
        $block->scope === \App\Models\GuestBlock::SCOPE_IP => ['IP', $block->ip],
        filled($block->email) => ['Email', $block->email],
        filled($block->phone) => [__('Telefon'), $block->phone],
        default => [$block->scopeLabel(), $block->ip ?? $block->device_id],
    };
    $topMax = max(1, (int) collect($topReasons)->max('total'));
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pg-crumb">{{ __('Sozlamalar') }}</div>
            <h2>{{ __('Mehmon himoyasi') }}</h2>
            <p class="pg-sub">{{ __('Spam va suiiste\'molga qarshi bloklangan mehmon qurilmalari.') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-none px-4 pt-6 sm:px-6 lg:px-8">
        <div class="mb-3 flex flex-wrap items-center gap-2">
            @foreach ($chips as $value => $label)
                <a href="{{ route('admin.guest-blocks.index', array_filter(['status' => $value, 'q' => $search ?: null])) }}" class="ui-chip" @if ($status === $value) aria-current="page" @endif>
                    {{ $label }} <b>{{ $counts[$value] }}</b>
                </a>
            @endforeach
            <form method="GET" class="relative w-full sm:ml-auto sm:w-[320px]">
                <input type="hidden" name="status" value="{{ $status }}">
                <label class="sr-only" for="guest-search">{{ __('Qidirish') }}</label>
                <svg class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><circle cx="9" cy="9" r="5.5"/><path d="m13.5 13.5 3 3"/></svg>
                <input id="guest-search" name="q" value="{{ $search }}" placeholder="{{ __('IP, qurilma ID, email, telefon yoki BLOK-…') }}" class="ui-input pl-8">
            </form>
        </div>

        <div class="ui-split" style="--split-side: 340px">
            <div class="ui-card overflow-x-auto px-3 pb-3 pt-2">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>{{ __('Tur') }}</th>
                            <th>{{ __('Qiymat') }}</th>
                            <th>{{ __('Sabab') }}</th>
                            <th>{{ __('Urinishlar') }}</th>
                            <th class="hidden lg:table-cell">{{ __('Vaqt') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($blocks as $block)
                            @php([$type, $value] = $typeOf($block))
                            <tr>
                                <td><span class="ui-tag">{{ $type }}</span></td>
                                <td>
                                    <span class="ui-mono block max-w-[220px] truncate" title="{{ $value }}">{{ $value ?? '—' }}</span>
                                    <span class="block text-xs text-muted">BLOK-{{ $block->id }} · {{ $block->deviceLabel() }}</span>
                                </td>
                                <td>
                                    {{ $block->reasonLabel() }}
                                    @if ($block->description)
                                        <span class="block max-w-[240px] truncate text-xs text-muted" title="{{ \App\Support\StoredText::translate($block->description) }}">{{ \App\Support\StoredText::translate($block->description) }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">
                                    <span class="ui-meter"><i style="width: {{ min(100, round($block->attempts_count / $maxAttempts * 100)) }}%; background: rgb(var(--c-status-new-dot))"></i></span>
                                    <span class="ui-mono">{{ $block->attempts_count ?: '—' }}</span>
                                </td>
                                <td class="ui-mono hidden whitespace-nowrap text-xs text-muted lg:table-cell">
                                    {{ $block->blocked_at->format('d.m.Y H:i') }}
                                    @if ($block->unblocked_at)
                                        <span class="block">{{ __('Ochilgan: :date', ['date' => $block->unblocked_at->format('d.m.Y H:i')]) }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if ($block->isActive())
                                        <form method="POST" action="{{ route('admin.guest-blocks.unblock', $block) }}" onsubmit="return confirm(@js(__(':block blokdan chiqarilsinmi?', ['block' => 'BLOK-'.$block->id])))">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary !px-2.5 !py-1">{{ __('Chiqarish') }}</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-muted" title="{{ $block->unblocker?->name }}">{{ __('Olib tashlangan') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-muted">
                                    {{ $status === 'active' ? __("Hozircha bloklangan qurilma yo'q.") : __("Ro'yxat bo'sh.") }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-1 pt-3">{{ $blocks->links() }}</div>
            </div>

            <div class="ui-split__side grid gap-3">
                <div class="ui-card">
                    <div class="mb-3 flex items-start justify-between gap-2">
                        <div>
                            <h3 class="ui-card__title">{{ __('Himoya holati') }}</h3>
                            <p class="ui-card__sub">{{ __('Sozlamalar sahifasidagi asosiy qiymatlar') }}</p>
                        </div>
                        <a href="{{ route('admin.guest-blocks.settings') }}" class="btn btn-secondary !px-2.5 !py-1">{{ __('Sozlamalar') }}</a>
                    </div>
                    <div class="grid gap-2.5 text-[13px]">
                        <div class="flex items-center justify-between gap-2">
                            <span>{{ __('Cloudflare Turnstile') }}</span>
                            <span class="status-badge {{ $protection['captcha'] ? 'status--completed' : 'status--closed' }}">{{ $protection['captcha'] ? 'Ulangan' : 'Ulanmagan' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span>{{ __('Telegram ogohlantirish') }}</span>
                            <span class="status-badge {{ $protection['alert'] ? 'status--completed' : 'status--closed' }}">{{ $protection['alert'] ? __('Yoqilgan') : __("O'chirilgan") }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span>{{ __('Ishonchli IP manzillar') }}</span>
                            <b class="ui-mono">{{ __(':n ta', ['n' => $protection['whitelist']]) }}</b>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span>{{ __('Hozirgi IP') }}</span>
                            <b class="ui-mono">{{ $protection['currentIp'] }}</b>
                        </div>
                        <p class="ui-hint">
                            {{ __('Limitlar: qurilma — soatiga :dh, kuniga :dd; IP — soatiga :ih, kuniga :id; email/telefon — kuniga :cd.', [
                                'dh' => config('guest_limits.device_per_hour'), 'dd' => config('guest_limits.device_per_day'),
                                'ih' => config('guest_limits.ip_per_hour'), 'id' => config('guest_limits.ip_per_day'), 'cd' => config('guest_limits.contact_per_day'),
                            ]) }}
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.guest-blocks.store') }}" class="ui-card grid gap-2.5">
                    @csrf
                    <h3 class="ui-card__title">{{ __('Qo\'lda bloklash') }}</h3>
                    <div>
                        <label class="ui-field-label" for="block-type">{{ __('Tur') }}</label>
                        <select id="block-type" name="type" class="ui-input">
                            <option value="ip" @selected(old('type') === 'ip')>{{ __('IP manzil') }}</option>
                            <option value="email" @selected(old('type') === 'email')>{{ __('Email') }}</option>
                            <option value="phone" @selected(old('type') === 'phone')>{{ __('Telefon') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="ui-field-label" for="block-value">{{ __('Qiymat') }}</label>
                        <input id="block-value" name="value" value="{{ old('value') }}" required placeholder="192.168.1.10" class="ui-input ui-mono">
                        <x-input-error :messages="$errors->get('value')" class="mt-1" />
                    </div>
                    <div>
                        <label class="ui-field-label" for="block-note">{{ __('Izoh') }}</label>
                        <input id="block-note" name="note" value="{{ old('note') }}" placeholder="{{ __('Ixtiyoriy') }}" class="ui-input">
                    </div>
                    <button type="submit" class="btn btn-danger w-full">{{ __('Bloklash') }}</button>
                    <p class="ui-hint">{{ __('Ishonchli IP ro\'yxatidagi manzilni IP bo\'yicha bloklab bo\'lmaydi.') }}</p>
                </form>

                <div class="ui-card">
                    <h3 class="ui-card__title">{{ __('Eng ko\'p sabablar') }}</h3>
                    <p class="ui-card__sub mb-3">{{ __('Oxirgi 30 kun') }}</p>
                    @forelse ($topReasons as $reason)
                        <div class="dash-bar" style="grid-template-columns: 130px 1fr 40px">
                            <span class="truncate" title="{{ $reason['label'] }}">{{ $reason['label'] }}</span>
                            <div class="dash-bar__track"><i style="width: {{ round($reason['total'] / $topMax * 100) }}%; background: rgb(var(--c-{{ $reason['tone'] }}))"></i></div>
                            <span class="ui-mono text-right">{{ $reason['total'] }}</span>
                        </div>
                    @empty
                        <p class="text-[13px] text-muted">{{ __('Oxirgi 30 kunda blok bo\'lmagan.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

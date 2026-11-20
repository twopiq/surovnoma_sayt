<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-slate-900">{{ __('Parolni tiklash') }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ __('Email yoki loginingizni kiriting va tiklash usulini tanlang.') }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div x-data="{ way: '{{ $telegramEnabled && ($errors->has('login') || old('login')) ? 'telegram' : 'email' }}' }">
        @if ($telegramEnabled)
            <div class="mb-5 grid grid-cols-2 gap-2 rounded-md border border-slate-200 bg-slate-100 p-1 text-sm font-semibold" role="tablist">
                <button type="button" role="tab" @click="way = 'email'" :aria-selected="(way === 'email').toString()" :class="way === 'email' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="rounded px-3 py-2 transition">{{ __('Email orqali') }}</button>
                <button type="button" role="tab" @click="way = 'telegram'" :aria-selected="(way === 'telegram').toString()" :class="way === 'telegram' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="rounded px-3 py-2 transition">{{ __('Telegram orqali') }}</button>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" x-show="way === 'email'">
            @csrf
            <p class="mb-4 text-sm text-slate-500">{{ __("Akkauntingizga biriktirilgan email manzilga yangi parol o'rnatish havolasi yuboriladi.") }}</p>
            <div>
                <x-input-label for="email" :value="__('Email yoki login')" />
                <x-text-input id="email" class="block mt-1 w-full" type="text" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
                <a href="{{ route('login') }}" class="text-sm text-slate-500 underline hover:text-slate-700">{{ __('Kirish sahifasiga qaytish') }}</a>
                <x-primary-button>{{ __('Havolani yuborish') }}</x-primary-button>
            </div>
        </form>

        @if ($telegramEnabled)
            <form method="POST" action="{{ route('password.telegram.send') }}" x-show="way === 'telegram'" style="display: none;">
                @csrf
                <p class="mb-4 text-sm text-slate-500">{{ __("Akkauntingizga ulangan Telegram botga 6 xonali kod yuboriladi. Telegram profil sozlamalarida ulanadi.") }}</p>
                <div>
                    <x-input-label for="tg-login" :value="__('Email yoki login')" />
                    <x-text-input id="tg-login" class="block mt-1 w-full" type="text" name="login" :value="old('login')" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('login')" class="mt-2" />
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
                    <a href="{{ route('login') }}" class="text-sm text-slate-500 underline hover:text-slate-700">{{ __('Kirish sahifasiga qaytish') }}</a>
                    <x-primary-button>{{ __('Kod yuborish') }}</x-primary-button>
                </div>
            </form>
        @endif
    </div>
</x-guest-layout>

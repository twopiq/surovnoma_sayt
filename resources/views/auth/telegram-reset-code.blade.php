<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-slate-900">{{ __('Kodni kiriting') }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ __('Telegram botga yuborilgan 6 xonali kodni kiriting. Kod :n daqiqa amal qiladi.', ['n' => \App\Http\Controllers\Auth\TelegramPasswordResetController::CODE_MINUTES]) }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.telegram.check') }}">
        @csrf
        <input type="hidden" name="login" value="{{ $login }}">
        <div>
            <x-input-label for="code" :value="__('Kod')" />
            <x-text-input id="code" class="block mt-1 w-full font-mono text-lg tracking-[.3em]" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
            <a href="{{ route('password.request') }}" class="text-sm text-slate-500 underline hover:text-slate-700">{{ __("Yangi kod so'rash") }}</a>
            <x-primary-button>{{ __('Davom etish') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>

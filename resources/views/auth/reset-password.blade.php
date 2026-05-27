<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Yangi parol" />
            <x-password-input id="password" name="password" class="block mt-1 w-full" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Parolni tasdiqlang" />
            <x-password-input id="password_confirmation" name="password_confirmation" class="block mt-1 w-full" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
            <a href="{{ route('login') }}" class="text-sm text-slate-500 underline hover:text-slate-700">
                Kirish sahifasiga qaytish
            </a>

            <x-primary-button>
                Parolni yangilash
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Parolingizni unutdingizmi? Email manzilingizni kiriting, biz sizga yangi parol o'rnatish havolasini yuboramiz.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
            <a href="{{ route('login') }}" class="text-sm text-slate-500 underline hover:text-slate-700">
                Kirish sahifasiga qaytish
            </a>

            <x-primary-button>
                Havolani yuborish
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

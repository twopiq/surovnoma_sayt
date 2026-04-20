<section>
    <header>
        <h2 class="text-lg font-bold text-slate-950">
            Pochta manzili
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            Email akkauntga kirish va bildirishnomalar uchun ishlatiladi.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('settings.email.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="settings_email" value="Email" />
            <x-text-input id="settings_email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->updateEmail->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="mt-2 text-sm text-slate-700">
                        Email manzilingiz tasdiqlanmagan.

                        <button form="send-verification" class="rounded-md text-sm font-semibold text-cyan-700 underline transition hover:text-cyan-900 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2">
                            Tasdiqlash xatini qayta yuborish
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-emerald-600">
                            Yangi tasdiqlash xati emailingizga yuborildi.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Saqlash</x-primary-button>

            @if (session('status') === 'email-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm font-medium text-emerald-600"
                >Pochta manzili saqlandi.</p>
            @endif
        </div>
    </form>
</section>

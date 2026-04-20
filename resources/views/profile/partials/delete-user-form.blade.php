<section class="space-y-6">
    <header>
        <h2 class="text-lg font-bold text-red-700">
            Akkauntni o'chirish
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            Akkaunt o'chirilsa, unga tegishli ma'lumotlar qayta tiklanmaydi.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Akkauntni o'chirish</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                Akkauntingizni o'chirishni tasdiqlaysizmi?
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Akkauntni o'chirish uchun parolingizni kiriting.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Parol" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Parol"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Bekor qilish
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    O'chirish
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>

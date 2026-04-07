<x-guest-layout>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="font-['Space_Grotesk'] text-2xl font-bold">Guest forma</h1>
            <p class="mt-2 text-sm text-slate-500">Yuborilgandan keyin ticket ID va maxfiy tracking code beriladi.</p>
        </div>
        <a href="{{ route('home') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Home</a>
    </div>

    <form method="POST" action="{{ route('guest.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                Forma yuborilmadi. Iltimos, xatolarni to'g'rilang.
            </div>
        @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="name" value="F.I.Sh." />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="phone" value="Telefon" />
                <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone')" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" type="email" name="email" class="mt-1 block w-full" :value="old('email')" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="department" value="Bo'lim" />
                <x-text-input id="department" name="department" class="mt-1 block w-full" :value="old('department')" />
                <x-input-error :messages="$errors->get('department')" class="mt-2" />
            </div>
        </div>
        <div>
            <x-input-label for="job_title" value="Lavozim" />
            <x-text-input id="job_title" name="job_title" class="mt-1 block w-full" :value="old('job_title')" />
            <x-input-error :messages="$errors->get('job_title')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="description" value="Tavsif" />
            <textarea id="description" name="description" rows="6" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" required>{{ old('description') }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="attachments" value="Fayllar" />
            <input id="attachments" type="file" name="attachments[]" multiple class="mt-1 block w-full text-sm text-slate-500" />
            <p class="mt-2 text-xs text-slate-400">5 tagacha, har biri 5 MB, JPG/JPEG/PNG/PDF/DOC/DOCX.</p>
            <x-input-error :messages="$errors->get('attachments')" class="mt-2" />
            <x-input-error :messages="$errors->get('attachments.*')" class="mt-2" />
        </div>
        <div class="flex justify-between">
            <a href="{{ route('home') }}" class="text-sm text-slate-500 underline">Asosiy ekran</a>
            <a href="{{ route('guest.track') }}" class="text-sm text-slate-500 underline">Avvalgi murojaatni kuzatish</a>
            <x-primary-button class="bg-cyan-700 hover:bg-cyan-800">Yuborish</x-primary-button>
        </div>
    </form>
</x-guest-layout>

<x-guest-layout>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="font-['Space_Grotesk'] text-2xl font-bold text-slate-900">Ro'yxatdan o'tish</h1>
            <p class="mt-2 text-sm text-slate-500">Hisob yaratiladi, keyin admin tasdiqlagach tizimga to'liq kirish ochiladi.</p>
        </div>
        <a href="{{ route('home') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Home</a>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="name" value="F.I.Sh." />
            <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
            <p class="mt-2 text-xs text-slate-400">Rad etilishga olib kelishi mumkin bo'lgan holatlar: noto'g'ri F.I.Sh., ishlamaydigan email, to'liq bo'lmagan telefon raqami yoki haqiqatga mos kelmaydigan ma'lumotlar.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-phone-input id="phone_display" name="phone" :value="old('phone')" required />
            <div>
                <x-input-label for="job_title" value="Lavozim" />
                <x-text-input id="job_title" class="mt-1 block w-full" type="text" name="job_title" :value="old('job_title')" />
                <x-input-error :messages="$errors->get('job_title')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="department_id" value="Ishlaydigan bo'lim" />
            <select id="department_id" name="department_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500">
                <option value="">Tanlang</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="password" value="Parol" />
                <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password_confirmation" value="Parolni tasdiqlash" />
                <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('home') }}" class="text-sm text-slate-500 underline hover:text-slate-700">Asosiy ekranga qaytish</a>
            <a class="text-sm text-slate-500 underline hover:text-slate-700" href="{{ route('login') }}">
                Avval ro'yxatdan o'tganmisiz?
            </a>

            <x-primary-button class="bg-cyan-700 hover:bg-cyan-800 focus:bg-cyan-800 active:bg-cyan-900">
                Ro'yxatdan o'tish
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

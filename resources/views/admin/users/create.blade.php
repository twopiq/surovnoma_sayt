<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Add user</h2>
                <p class="mt-1 text-sm text-slate-500">Admin tomonidan yangi tasdiqlangan foydalanuvchi yaratish.</p>
            </div>
            <a href="{{ route('admin.users.list') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                User management
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-5 px-4 pt-8 sm:px-6 lg:px-8">
        @include('admin.users.partials.top-menu')

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.users.store') }}" class="grid gap-5">
                @csrf

                <div>
                    <x-input-label for="name" value="F.I.Sh." />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <x-phone-input :value="old('phone')" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="role" value="Role" />
                        <select id="role" name="role" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="department_id" value="Bo'lim" />
                        <select id="department_id" name="department_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="">Tanlanmagan</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="job_title" value="Lavozim" />
                    <x-text-input id="job_title" name="job_title" type="text" class="mt-1 block w-full" :value="old('job_title')" />
                    <x-input-error :messages="$errors->get('job_title')" class="mt-2" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="password" value="Parol" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Parolni tasdiqlash" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <a href="{{ route('admin.users.list') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Bekor qilish
                    </a>
                    <button class="rounded-md bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Yaratish
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>

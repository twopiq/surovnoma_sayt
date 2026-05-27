<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Foydalanuvchi profili</h2>
            <p class="mt-1 text-sm text-slate-500">Foydalanuvchi ma'lumotlari va tizimdagi holati.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-none space-y-5 px-4 pt-8 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="relative z-0 h-44 bg-[#123b2f]">
                    <div class="h-full w-full bg-[linear-gradient(135deg,#123b2f_0%,#0f8f6b_52%,#fda13f_100%)] opacity-90"></div>
                </div>

                <div class="relative z-10 px-5 pb-6">
                    <div class="-mt-14 flex justify-center">
                        <span class="admin-profile-token relative z-20 inline-flex h-28 w-28 items-center justify-center rounded-full border-4 border-white bg-emerald-100 text-4xl font-bold text-emerald-700 shadow-sm">
                            {{ mb_substr($selectedUser->name, 0, 1) }}
                        </span>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-lg border border-slate-200">
                        <div class="grid grid-cols-[0.85fr_1.15fr] border-b border-slate-200 px-4 py-3 text-sm">
                            <div class="text-slate-500">F.I.Sh.</div>
                            <div class="font-semibold text-slate-900">: {{ $selectedUser->name }}</div>
                        </div>
                        <div class="grid grid-cols-[0.85fr_1.15fr] border-b border-slate-200 px-4 py-3 text-sm">
                            <div class="text-slate-500">Email</div>
                            <div class="font-semibold text-slate-900">: {{ $selectedUser->email }}</div>
                        </div>
                        <div class="grid grid-cols-[0.85fr_1.15fr] border-b border-slate-200 px-4 py-3 text-sm">
                            <div class="text-slate-500">Telefon raqami</div>
                            <div class="font-semibold text-slate-900">: {{ $selectedUser->phone ?? '-' }}</div>
                        </div>
                        <div class="grid grid-cols-[0.85fr_1.15fr] border-b border-slate-200 px-4 py-3 text-sm">
                            <div class="text-slate-500">Ro'yxatdan o'tgan sana</div>
                            <div class="font-semibold text-slate-900">: {{ $selectedUser->created_at?->format('d M, Y') }}</div>
                        </div>
                        <div class="grid grid-cols-[0.85fr_1.15fr] px-4 py-3 text-sm">
                            <div class="text-slate-500">Holat</div>
                            <div>
                                @if ($selectedUser->approved_at && $selectedUser->is_active)
                                    <span class="admin-profile-status rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Faol</span>
                                @elseif (! $selectedUser->is_active)
                                    <span class="admin-profile-status rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">Nofaol</span>
                                @else
                                    <span class="admin-profile-status rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-700">Kutilmoqda</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-['Space_Grotesk'] text-lg font-bold text-slate-900">Foydalanuvchi profili</h3>
                </div>

                <div class="p-5">
                    @php
                        $selectedRole = $selectedUser->getRoleNames()->first();
                        $selectedStatus = $selectedUser->approved_at && $selectedUser->is_active
                            ? 'active'
                            : (! $selectedUser->is_active ? 'inactive' : 'pending');
                    @endphp

                    @if ($selectedUser->is(auth()->user()))
                        <div class="space-y-4">
                            <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-center">
                                <label class="text-sm text-slate-600">Ism</label>
                                <input value="{{ $selectedUser->name }}" class="rounded-md border-slate-300 shadow-sm" readonly>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-center">
                                <label class="text-sm text-slate-600">Email</label>
                                <input value="{{ $selectedUser->email }}" class="rounded-md border-slate-300 shadow-sm" readonly>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-center">
                                <label class="text-sm text-slate-600">Login</label>
                                <input value="{{ $selectedUser->login }}" class="rounded-md border-slate-300 shadow-sm" readonly>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-center">
                                <label class="text-sm text-slate-600">Telefon</label>
                                <input value="{{ $selectedUser->phone ?? '-' }}" class="rounded-md border-slate-300 shadow-sm" readonly>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-center">
                                <label class="text-sm text-slate-600">Lavozim</label>
                                <input value="{{ $selectedUser->job_title ?: $selectedUser->display_role }}" class="rounded-md border-slate-300 shadow-sm" readonly>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-center">
                                <label class="text-sm text-slate-600">Bo'lim</label>
                                <input value="{{ $selectedUser->department?->name ?? '-' }}" class="rounded-md border-slate-300 shadow-sm" readonly>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-center">
                                <label class="text-sm text-slate-600">Rol</label>
                                <input value="{{ $selectedUser->display_role }}" class="rounded-md border-slate-300 shadow-sm" readonly>
                            </div>

                            <a href="{{ route('profile.edit') }}" class="inline-flex rounded-md bg-violet-700 px-5 py-2 text-sm font-semibold text-white transition hover:bg-violet-800">
                                Profilni tahrirlash
                            </a>
                        </div>
                    @else
                        <form method="POST" action="{{ route('admin.users.profile.update', $selectedUser) }}" class="space-y-4">
                            @csrf
                            @method('PATCH')

                            <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-start">
                                <label for="name" class="pt-2 text-sm text-slate-600">Ism</label>
                                <div>
                                    <input id="name" name="name" value="{{ old('name', $selectedUser->name) }}" class="w-full rounded-md border-slate-300 shadow-sm">
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-start">
                                <label for="email" class="pt-2 text-sm text-slate-600">Email</label>
                                <div>
                                    <input id="email" name="email" type="email" value="{{ old('email', $selectedUser->email) }}" class="w-full rounded-md border-slate-300 shadow-sm">
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-start">
                                <label for="login" class="pt-2 text-sm text-slate-600">Login</label>
                                <input id="login" value="{{ $selectedUser->login }}" class="w-full rounded-md border-slate-300 bg-slate-50 text-slate-500 shadow-sm" readonly>
                            </div>

                            <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-start">
                                <label for="phone" class="pt-2 text-sm text-slate-600">Telefon</label>
                                <div>
                                    <input id="phone" name="phone" value="{{ old('phone', $selectedUser->phone) }}" placeholder="+998 90 000 00 00" class="w-full rounded-md border-slate-300 shadow-sm">
                                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-start">
                                <label for="job_title" class="pt-2 text-sm text-slate-600">Lavozim</label>
                                <div>
                                    <input id="job_title" name="job_title" value="{{ old('job_title', $selectedUser->job_title) }}" class="w-full rounded-md border-slate-300 shadow-sm">
                                    <x-input-error :messages="$errors->get('job_title')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-start">
                                <label for="department_id" class="pt-2 text-sm text-slate-600">Bo'lim</label>
                                <div>
                                    <select id="department_id" name="department_id" class="w-full rounded-md border-slate-300 shadow-sm">
                                        <option value="">Bo'lim tanlanmagan</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}" @selected((string) old('department_id', $selectedUser->department_id) === (string) $department->id)>{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-start">
                                <label for="role" class="pt-2 text-sm text-slate-600">Rol</label>
                                <div>
                                    <select id="role" name="role" class="w-full rounded-md border-slate-300 shadow-sm">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->value }}" @selected(old('role', $selectedRole) === $role->value)>{{ $role->label() }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-start">
                                <label for="status" class="pt-2 text-sm text-slate-600">Holat</label>
                                <div>
                                    <select id="status" name="status" class="w-full rounded-md border-slate-300 shadow-sm">
                                        @foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}" @selected(old('status', $selectedStatus) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                                </div>
                            </div>

                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <label class="inline-flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        name="can_access_app_dashboard"
                                        value="1"
                                        @checked($errors->any() ? old('can_access_app_dashboard') : $selectedUser->can_access_app_dashboard)
                                        class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                    >
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-700">Dashboardga ruxsat berilsin</span>
                                        <span class="mt-1 block text-sm text-slate-500">Bu ruxsat faqat Rahbar roli tanlanganda amal qiladi.</span>
                                    </span>
                                </label>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-4">
                                <a href="{{ route('admin.users.list') }}" class="inline-flex rounded-md border border-slate-300 px-5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                    Ortga qaytish
                                </a>
                                <button class="inline-flex rounded-md bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                    Saqlash
                                </button>
                                <a href="{{ route('admin.users.profile', ['user' => $selectedUser->id]) }}" class="inline-flex rounded-md border border-slate-300 px-5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                    Bekor qilish
                                </a>
                            </div>
                        </form>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

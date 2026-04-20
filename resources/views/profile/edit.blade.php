<x-app-layout>
    @php
        $roleLabels = $user->getRoleNames()
            ->map(fn ($role) => \App\Enums\UserRole::tryFrom($role)?->label() ?? $role)
            ->implode(', ');

        $profileRows = [
            ['label' => 'Foydalanuvchi ID', 'value' => '#'.$user->id],
            ['label' => 'F.I.O.', 'value' => $user->name],
            ['label' => 'Login', 'value' => $user->login ?: '-'],
            ['label' => 'Email', 'value' => $user->email],
            ['label' => 'Telefon', 'value' => $user->phone ?: '-'],
            ['label' => 'Lavozim', 'value' => $user->job_title ?: '-'],
            ['label' => 'Bo‘lim', 'value' => $user->department?->name ?: '-'],
            ['label' => 'Rol', 'value' => $roleLabels ?: 'Tasdiqlanmagan'],
            ['label' => 'Bandlik holati', 'value' => $user->availability_status?->label() ?? '-'],
            ['label' => 'Akkaunt holati', 'value' => $user->is_active ? 'Faol' : 'Faol emas'],
            ['label' => 'Tasdiqlangan vaqt', 'value' => $user->approved_at?->format('d.m.Y H:i') ?? '-'],
            ['label' => 'Email tasdiqlangan vaqt', 'value' => $user->email_verified_at?->format('d.m.Y H:i') ?? '-'],
            ['label' => 'Ro‘yxatdan o‘tgan vaqt', 'value' => $user->created_at?->format('d.m.Y H:i') ?? '-'],
            ['label' => 'Oxirgi yangilanish', 'value' => $user->updated_at?->format('d.m.Y H:i') ?? '-'],
        ];
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-wide text-cyan-700">Profil</p>
            <h1 class="mt-2 font-['Space_Grotesk'] text-3xl font-bold text-slate-950">Mening ma'lumotlarim</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-600">Shaxsiy, ish va akkaunt ma'lumotlaringiz shu yerda saqlanadi.</p>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(420px,0.72fr)]">
            <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-md bg-cyan-100 text-xl font-bold uppercase text-cyan-800">
                            {{ mb_substr($user->name, 0, 1) }}
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-950">{{ $user->name }}</h2>
                            <p class="text-sm text-slate-500">{{ $user->email }}</p>
                        </div>
                    </div>

                    <span class="inline-flex w-fit items-center rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">
                        {{ $user->is_active ? 'Faol akkaunt' : 'Faol emas' }}
                    </span>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ($profileRows as $row)
                        <div class="rounded-md border border-slate-100 bg-slate-50 px-4 py-3">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $row['label'] }}</div>
                            <div class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $row['value'] }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="space-y-6">
                <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
                    @include('profile.partials.update-profile-information-form')
                </section>
            </div>
        </div>
    </div>
</x-app-layout>

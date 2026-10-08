@php
    $isCreate = $selectedUser === null;
    $isSelf = ! $isCreate && $selectedUser->is(auth()->user());
    $currentRole = old('role', $selectedUser?->getRoleNames()->first() ?? \App\Enums\UserRole::Requester->value);
    $formId = 'user-profile-form';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="pg-crumb"><a href="{{ route('admin.users.list') }}" class="hover:text-ink">Odamlar › Foydalanuvchilar</a></div>
                <h2>{{ $isCreate ? 'Yangi foydalanuvchi' : $selectedUser->name }}</h2>
                <p class="pg-sub">{{ $isCreate ? "Hisob yaratiladi va darhol tasdiqlangan bo'ladi." : 'Profilni tahrirlash' }}</p>
            </div>
            @if (! $isCreate && ! $isSelf)
                <form method="POST" action="{{ route('admin.users.password-reset', $selectedUser) }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Parolni tiklash</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-none px-4 pt-6 sm:px-6 lg:px-8">
        @if ($isSelf)
            <p class="ui-note mb-3">Bu sizning hisobingiz. O'z ma'lumotlaringizni <a href="{{ route('profile.edit') }}">Profil</a> sahifasida o'zgartirasiz.</p>
        @endif

        <form id="{{ $formId }}" method="POST" action="{{ $isCreate ? route('admin.users.store') : route('admin.users.profile.update', $selectedUser) }}" x-data="{ dirty: false }" @input="dirty = true" @change="dirty = true">
            @csrf
            @unless ($isCreate)
                @method('PATCH')
            @endunless

            <fieldset @disabled($isSelf) class="ui-split" style="--split-side: 340px">
                <div class="ui-card">
                    <h3 class="ui-card__title mb-3">Asosiy ma'lumotlar</h3>
                    @include('admin.users.partials.fields', ['user' => $selectedUser, 'compact' => false])

                    <div class="mb-3 mt-5">
                        <h3 class="ui-card__title">Parol</h3>
                        <p class="ui-card__sub">{{ $isCreate ? 'Kamida 8 belgi' : "Bo'sh qoldirsangiz o'zgarmaydi" }}</p>
                    </div>
                    <div class="grid gap-3.5 sm:grid-cols-2">
                        <div>
                            <label class="ui-field-label" for="password">{{ $isCreate ? 'Parol' : 'Yangi parol' }}</label>
                            <x-password-input id="password" name="password" class="ui-input" autocomplete="new-password" :required="$isCreate" />
                            <x-input-error :messages="$errors->get('password')" class="mt-1" />
                        </div>
                        <div>
                            <label class="ui-field-label" for="password_confirmation">Tasdiqlash</label>
                            <x-password-input id="password_confirmation" name="password_confirmation" class="ui-input" autocomplete="new-password" :required="$isCreate" />
                        </div>
                    </div>
                </div>

                <div class="grid gap-3">
                    <div class="ui-card grid gap-3">
                        <h3 class="ui-card__title">Rol va holat</h3>
                        <div>
                            <label class="ui-field-label" for="role">Rol</label>
                            <select id="role" name="role" class="ui-input" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->value }}" @selected($currentRole === $role->value)>{{ $role->label() }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-1" />
                        </div>
                        @include('admin.users.partials.status-fields', ['user' => $selectedUser, 'boxed' => false])
                    </div>

                    @unless ($isCreate)
                        <div class="ui-card">
                            <h3 class="ui-card__title mb-3">Faollik</h3>
                            <dl class="ui-kv">
                                <dt>Ro'yxatdan o'tgan</dt>
                                <dd class="ui-mono">{{ $selectedUser->created_at?->format('d.m.Y') ?? '—' }}</dd>
                                <dt>Tasdiqlangan</dt>
                                <dd class="ui-mono">{{ $selectedUser->approved_at?->format('d.m.Y') ?? '—' }}</dd>
                                <dt>So'nggi faollik</dt>
                                <dd class="ui-mono">{{ isset($lastActivity[$selectedUser->id]) ? $lastActivity[$selectedUser->id]->format('d.m.Y H:i') : '—' }}</dd>
                                <dt>Login</dt>
                                <dd class="ui-mono">{{ $selectedUser->login ?? '—' }}</dd>
                            </dl>
                        </div>
                    @endunless
                </div>
            </fieldset>

            @unless ($isSelf)
                <div class="ui-savebar">
                    <span class="text-[13px]">
                        <template x-if="dirty"><span><span class="ui-dirty-dot"></span>Saqlanmagan o'zgarishlar bor</span></template>
                        <template x-if="!dirty"><span class="text-muted">{{ $isCreate ? 'Yangi foydalanuvchi' : 'Foydalanuvchi profili' }}</span></template>
                    </span>
                    <span class="flex gap-2">
                        <a href="{{ route('admin.users.list', $isCreate ? [] : ['user' => $selectedUser->id]) }}" class="btn btn-secondary">Bekor qilish</a>
                        <button type="submit" class="btn btn-primary">{{ $isCreate ? 'Yaratish' : 'Saqlash' }}</button>
                    </span>
                </div>
            @endunless
        </form>
    </div>
</x-app-layout>

{{-- Foydalanuvchi forma maydonlari. $user — null bo'lsa yaratish rejimi. $compact — yon panel (UsersA). --}}
@php
    $user = $user ?? null;
    $compact = $compact ?? false;
    $currentRole = old('role', $user?->getRoleNames()->first() ?? \App\Enums\UserRole::Requester->value);
    $isPending = $user && $user->is_active && ! $user->approved_at;
    $fieldId = fn (string $name) => ($compact ? 'side_' : '').$name;
@endphp

<div class="grid gap-3.5 sm:grid-cols-2">
    <div @class(['sm:col-span-2' => $compact])>
        <label class="ui-field-label" for="{{ $fieldId('name') }}">F.I.Sh.</label>
        <input id="{{ $fieldId('name') }}" name="name" type="text" class="ui-input" value="{{ old('name', $user?->name) }}" required>
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>
    @unless ($compact)
        <div>
            <label class="ui-field-label" for="{{ $fieldId('job_title') }}">Lavozim</label>
            <input id="{{ $fieldId('job_title') }}" name="job_title" type="text" class="ui-input" value="{{ old('job_title', $user?->job_title) }}">
            <x-input-error :messages="$errors->get('job_title')" class="mt-1" />
        </div>
    @endunless
    <div>
        <label class="ui-field-label" for="{{ $fieldId('email') }}">Email</label>
        <input id="{{ $fieldId('email') }}" name="email" type="email" class="ui-input" value="{{ old('email', $user?->email) }}" required>
        <x-input-error :messages="$errors->get('email')" class="mt-1" />
    </div>
    @if ($user)
        <div>
            <label class="ui-field-label" for="{{ $fieldId('login') }}">Login</label>
            <input id="{{ $fieldId('login') }}" type="text" class="ui-input ui-mono bg-sunken text-muted" value="{{ $user->login }}" disabled>
        </div>
    @else
        <x-login-picker :source="$fieldId('name')" :value="old('login')" />
    @endif
    <div>
        <x-phone-input :id="$fieldId('phone')" :value="old('phone', $user?->phone)" hint="" />
        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
    </div>
    @if ($compact)
        <div>
            <label class="ui-field-label" for="{{ $fieldId('job_title') }}">Lavozim</label>
            <input id="{{ $fieldId('job_title') }}" name="job_title" type="text" class="ui-input" value="{{ old('job_title', $user?->job_title) }}">
        </div>
    @endif
    <div>
        <label class="ui-field-label" for="{{ $fieldId('department_id') }}">Bo'lim</label>
        <select id="{{ $fieldId('department_id') }}" name="department_id" class="ui-input">
            <option value="">Tanlanmagan</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected((string) old('department_id', $user?->department_id) === (string) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('department_id')" class="mt-1" />
    </div>
    @if ($compact)
        <div>
            <label class="ui-field-label" for="{{ $fieldId('role') }}">Rol</label>
            <select id="{{ $fieldId('role') }}" name="role" class="ui-input" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected($currentRole === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('role')" class="mt-1" />
        </div>
    @endif
</div>

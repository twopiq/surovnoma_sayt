<section>
    <header>
        <h2 class="text-lg font-bold text-slate-950">
            Ma'lumotlarni tahrirlash
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            F.I.O., telefon raqami va ish holatini yangilang.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="F.I.O." />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <x-phone-input id="profile_phone_display" name="phone" :value="old('phone', $user->phone)" />

        <div>
            <x-input-label for="job_title" value="Lavozim" />
            <x-text-input id="job_title" name="job_title" type="text" class="mt-1 block w-full" :value="old('job_title', $user->job_title)" placeholder="Masalan: mutaxassis" />
            <x-input-error class="mt-2" :messages="$errors->get('job_title')" />
        </div>

        <div>
            <x-input-label for="department_id" value="Ishlaydigan bo'lim" />
            <select id="department_id" name="department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Bo'lim tanlanmagan</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) old('department_id', $user->department_id) === (string) $department->id)>
                        {{ $department->name }}{{ $department->is_active ? '' : ' - faol emas' }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('department_id')" />
        </div>

        <div>
            <x-input-label for="availability_status" value="Bandlik holati" />
            <select id="availability_status" name="availability_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($availabilityStatuses as $status)
                    <option value="{{ $status->value }}" @selected(old('availability_status', $user->availability_status?->value) === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('availability_status')" />
        </div>

        <div>
            <x-input-label for="login" value="Login" />
            <x-text-input id="login" type="text" class="mt-1 block w-full bg-slate-100 text-slate-500" :value="$user->login" disabled />
            <p class="mt-1 text-xs text-slate-500">Login tizim tomonidan beriladi va o'zgartirilmaydi.</p>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Saqlash</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm font-medium text-emerald-600"
                >Ma'lumotlar saqlandi.</p>
            @endif
        </div>
    </form>
</section>

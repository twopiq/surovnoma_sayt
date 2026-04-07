<x-app-layout>
    <x-slot name="header">
        <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Foydalanuvchilar va tasdiqlash</h2>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="font-semibold">Tasdiq kutayotganlar</h3>
            <div class="mt-4 space-y-4">
                @forelse ($pendingUsers as $user)
                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="grid gap-3 rounded-xl border border-slate-200 p-4 md:grid-cols-[1.2fr_1fr_auto]">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="approved" value="1" />
                        <div>
                            <div class="font-semibold">{{ $user->name }}</div>
                            <div class="text-sm text-slate-500">{{ $user->email }}</div>
                        </div>
                        <select name="role" class="rounded-md border-slate-300 shadow-sm">
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}">{{ $role->label() }}</option>
                            @endforeach
                        </select>
                        <button class="rounded-full bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Tasdiqlash</button>
                    </form>
                @empty
                    <p class="text-sm text-slate-500">Kutilayotgan foydalanuvchi yo‘q.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="font-semibold">So‘nggi tasdiqlanganlar</h3>
            <div class="mt-4 space-y-3">
                @foreach ($approvedUsers as $user)
                    <div class="rounded-xl bg-slate-50 p-4">
                        <div class="font-semibold">{{ $user->name }}</div>
                        <div class="text-sm text-slate-500">{{ $user->email }} • {{ $user->display_role }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>

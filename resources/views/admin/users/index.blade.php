<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Tasdiq kutayotganlar</h2>
            <p class="mt-1 text-sm text-slate-500">Yangi ro'yxatdan o'tgan va tasdiqlash kutayotgan foydalanuvchilar.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-none space-y-5 px-4 pt-8 sm:px-6 lg:px-8">
        @include('admin.users.partials.top-menu')

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="font-semibold text-slate-900">Tasdiq kutayotganlar</h3>
            <div class="mt-4 space-y-4">
                @forelse ($pendingUsers as $user)
                    <div class="grid gap-3 rounded-lg border border-slate-200 p-4 md:grid-cols-[1.2fr_1fr_auto_auto] md:items-center">
                        <div>
                            <div class="font-semibold text-slate-900">{{ $user->name }}</div>
                            <div class="text-sm text-slate-500">{{ $user->email }}</div>
                            <div class="mt-1 text-xs text-slate-400">
                                {{ $user->login }}
                                @if ($user->department)
                                    / {{ $user->department->name }}
                                @endif
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="contents">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="decision" value="approve" />
                            <select name="role" class="rounded-md border-slate-300 shadow-sm">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->value }}">{{ $role->label() }}</option>
                                @endforeach
                            </select>
                            <button class="rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-cyan-800">Tasdiqlash</button>
                        </form>

                        <form method="POST" action="{{ route('admin.users.update', $user) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="decision" value="reject" />
                            <button class="rounded-md border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                                Rad etish
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Kutilayotgan foydalanuvchi yo'q.</p>
                @endforelse
            </div>
        </section>

        @if ($rejectedUsers->isNotEmpty())
            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Rad etilgan so'rovlar</h3>
                <div class="mt-4 space-y-3">
                    @foreach ($rejectedUsers as $user)
                        <div class="rounded-lg border border-rose-100 bg-rose-50 p-4">
                            <div class="font-semibold text-slate-900">{{ $user->name }}</div>
                            <div class="text-sm text-slate-600">{{ $user->email }}</div>
                            <div class="mt-1 text-xs text-rose-700">So'rov rad etilgan</div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>

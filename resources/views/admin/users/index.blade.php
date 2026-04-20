<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-['Space_Grotesk'] text-2xl font-bold">So'nggi ro'yxatdan o'tganlar</h2>
                <p class="mt-1 text-sm text-slate-500">Yangi akkauntlar va tasdiq kutayotgan foydalanuvchilar.</p>
            </div>
            <a href="{{ route('admin.users.list') }}" class="rounded-md px-4 py-2 text-sm font-semibold text-violet-700 transition hover:bg-violet-50">
                Barchasini ko'rish &rsaquo;
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-none space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
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
                            <button class="rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Tasdiqlash</button>
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

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h3 class="font-['Space_Grotesk'] text-lg font-bold text-slate-900">Recent Registered Client</h3>
                <a href="{{ route('admin.users.list') }}" class="text-sm font-semibold text-violet-700">View All &rsaquo;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">SL.</th>
                            <th class="px-5 py-3">Ro'yxatdan o'tgan</th>
                            <th class="px-5 py-3">Tasdiqlangan</th>
                            <th class="px-5 py-3">F.I.Sh.</th>
                            <th class="px-5 py-3">Login</th>
                            <th class="px-5 py-3">Email</th>
                            <th class="px-5 py-3">Telefon</th>
                            <th class="px-5 py-3">Lavozim</th>
                            <th class="px-5 py-3">Bo'lim</th>
                            <th class="px-5 py-3">Rol</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($latestUsers as $user)
                            <tr>
                                <td class="px-5 py-3 text-slate-600">{{ $loop->iteration }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $user->created_at?->format('d.m.Y H:i') }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $user->approved_at?->format('d.m.Y H:i') ?? '-' }}</td>
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.users.profile', ['user' => $user]) }}" class="flex items-center gap-3">
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">
                                            {{ mb_substr($user->name, 0, 1) }}
                                        </span>
                                        <span>
                                            <span class="block font-semibold text-slate-900">{{ $user->name }}</span>
                                            <span class="block text-xs text-slate-500">{{ $user->created_at?->diffForHumans() }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->login }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->email }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->phone ?? '-' }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->job_title ?: '-' }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->department?->name ?? '-' }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->display_role }}</td>
                                <td class="px-5 py-3">
                                    @if ($user->approved_at && $user->is_active)
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Active</span>
                                    @elseif (! $user->is_active)
                                        <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">Inactive</span>
                                    @else
                                        <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-700">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-5 py-8 text-center text-slate-500">Hozircha foydalanuvchi yo'q.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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

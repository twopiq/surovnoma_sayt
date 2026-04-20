<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Recent registrations</h2>
                <p class="mt-1 text-sm text-slate-500">Oxirgi ro'yxatdan o'tgan foydalanuvchilar va tasdiqlanish vaqtlari.</p>
            </div>
            <div class="text-sm text-slate-500">Application / Users / <span class="font-semibold text-cyan-700">Recent registrations</span></div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-none space-y-5 px-4 pt-8 sm:px-6 lg:px-8">
        @include('admin.users.partials.top-menu')

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 class="font-['Space_Grotesk'] text-lg font-bold text-slate-900">Oxirgi ro'yxatdan o'tganlar</h3>
                    <p class="mt-1 text-sm text-slate-500">Foydalanuvchining asosiy ma'lumotlari bilan ro'yxat.</p>
                </div>
                <span class="text-sm font-semibold text-slate-500">{{ $users->total() }} ta</span>
            </div>

            <div class="max-w-full overflow-hidden">
                <table class="w-full table-fixed border-collapse text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="w-12 px-4 py-3">#</th>
                            <th class="w-[18%] px-4 py-3">F.I.Sh.</th>
                            <th class="w-[18%] px-4 py-3">Email</th>
                            <th class="hidden w-[12%] px-4 py-3 md:table-cell">Login</th>
                            <th class="hidden w-[12%] px-4 py-3 lg:table-cell">Telefon</th>
                            <th class="hidden w-[12%] px-4 py-3 lg:table-cell">Bo'lim</th>
                            <th class="w-[10%] px-4 py-3">Rol</th>
                            <th class="hidden w-[12%] px-4 py-3 xl:table-cell">Ro'yxatdan o'tgan</th>
                            <th class="hidden w-[12%] px-4 py-3 xl:table-cell">Tasdiqlangan</th>
                            <th class="w-[92px] px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($users as $user)
                            @php
                                $status = $user->approved_at && $user->is_active
                                    ? ['label' => 'Active', 'class' => 'bg-emerald-100 text-emerald-700']
                                    : (! $user->is_active
                                        ? ['label' => 'Inactive', 'class' => 'bg-slate-200 text-slate-600']
                                        : ['label' => 'Pending', 'class' => 'bg-yellow-100 text-yellow-700']);
                            @endphp
                            <tr class="odd:bg-slate-50/60">
                                <td class="px-4 py-3 text-slate-500">{{ $users->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.users.profile', ['user' => $user]) }}" class="flex min-w-0 items-center gap-3">
                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">
                                            {{ mb_substr($user->name, 0, 1) }}
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block truncate font-semibold text-slate-800">{{ $user->name }}</span>
                                            <span class="block truncate text-xs text-slate-500 xl:hidden">{{ $user->created_at?->format('d.m.Y H:i') }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td class="truncate px-4 py-3 text-slate-600">{{ $user->email }}</td>
                                <td class="hidden truncate px-4 py-3 text-slate-600 md:table-cell">{{ $user->login }}</td>
                                <td class="hidden truncate px-4 py-3 text-slate-600 lg:table-cell">{{ $user->phone ?? '-' }}</td>
                                <td class="hidden truncate px-4 py-3 text-slate-600 lg:table-cell">{{ $user->department?->name ?? '-' }}</td>
                                <td class="truncate px-4 py-3 text-slate-600">{{ $user->display_role }}</td>
                                <td class="hidden whitespace-nowrap px-4 py-3 text-slate-600 xl:table-cell">{{ $user->created_at?->format('d.m.Y H:i') }}</td>
                                <td class="hidden whitespace-nowrap px-4 py-3 text-slate-600 xl:table-cell">{{ $user->approved_at?->format('d.m.Y H:i') ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex max-w-full rounded-full px-3 py-1 text-xs font-semibold {{ $status['class'] }}">
                                        {{ $status['label'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-5 py-8 text-center text-slate-500">Hozircha ro'yxatdan o'tgan foydalanuvchi yo'q.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm font-medium text-slate-600">
                    {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} / {{ $users->total() }} users
                </div>
                <div class="max-w-full overflow-hidden">
                    {{ $users->onEachSide(1)->links() }}
                </div>
            </div>
        </section>
    </div>
</x-app-layout>

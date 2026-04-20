<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-['Space_Grotesk'] text-2xl font-bold">User management</h2>
                <p class="mt-1 text-sm text-slate-500">Foydalanuvchilar, rollar va akkaunt holatini boshqarish.</p>
            </div>
            <div class="text-sm text-slate-500">Application / Users / <span class="font-semibold text-cyan-700">User management</span></div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-none space-y-5 px-4 pt-8 sm:px-6 lg:px-8">
        @include('admin.users.partials.top-menu')

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <form method="GET" action="{{ route('admin.users.list') }}" class="space-y-3 border-b border-slate-200 px-4 py-4">
                <div class="grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-[minmax(180px,1fr)_150px_150px_160px_120px]">
                    <label class="relative block">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.47 9.77l2.63 2.63a1 1 0 0 0 1.42-1.42l-2.63-2.63A5.5 5.5 0 0 0 9 3.5ZM5.5 9a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0Z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <input name="search" value="{{ $filters['search'] }}" placeholder="Search" class="w-full rounded-md border-slate-300 pl-9 text-sm shadow-sm" />
                    </label>

                    <select name="role" class="w-full rounded-md border-slate-300 text-sm shadow-sm">
                        <option value="">Role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected($filters['role'] === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>

                    <select name="status" class="w-full rounded-md border-slate-300 text-sm shadow-sm">
                        <option value="">Status</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <input name="date" type="date" value="{{ $filters['date'] }}" class="w-full rounded-md border-slate-300 text-sm shadow-sm" />

                    <select name="per_page" class="w-full rounded-md border-slate-300 text-sm shadow-sm">
                        @foreach ([10, 25, 50] as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} rows</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-2 sm:grid-cols-2 md:grid-cols-4 lg:flex lg:flex-wrap lg:justify-end">
                    <button class="inline-flex min-w-[104px] items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Filtrlash
                    </button>
                    <a href="{{ route('admin.users.list') }}" class="inline-flex min-w-[104px] items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Tozalash
                    </a>
                    <a href="{{ route('admin.users.export', array_merge(request()->query(), ['format' => 'excel'])) }}" class="inline-flex min-w-[104px] items-center justify-center gap-2 rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.69L6.03 8.22a.75.75 0 0 0-1.06 1.06l4.5 4.5a.75.75 0 0 0 1.06 0l4.5-4.5a.75.75 0 1 0-1.06-1.06l-3.22 3.22V2.75Z" />
                            <path d="M3.5 12.75a.75.75 0 0 1 .75.75v2.25h11.5V13.5a.75.75 0 0 1 1.5 0v2.5A1.25 1.25 0 0 1 16 17.25H4A1.25 1.25 0 0 1 2.75 16v-2.5a.75.75 0 0 1 .75-.75Z" />
                        </svg>
                        Excel
                    </a>
                    <a href="{{ route('admin.users.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="inline-flex min-w-[104px] items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        CSV
                    </a>
                    <a href="{{ route('admin.users.create') }}" class="inline-flex min-w-[104px] items-center justify-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Add user
                    </a>
                </div>
            </form>

            <div class="max-w-full overflow-hidden">
                <table class="admin-users-table w-full table-fixed border-collapse text-sm">
                    <thead class="bg-[#243746] text-left text-xs font-semibold uppercase tracking-wide text-slate-200">
                        <tr>
                            <th class="w-[34%] px-3 py-2 md:w-[23%]">Full Name</th>
                            <th class="w-[34%] px-3 py-2 md:w-[24%]">Email</th>
                            <th class="hidden w-[12%] px-3 py-2 md:table-cell">Username</th>
                            <th class="w-[82px] px-2 py-2 text-center sm:w-[92px]">Status</th>
                            <th class="hidden w-[11%] px-3 py-2 lg:table-cell">Role</th>
                            <th class="hidden w-[13%] px-3 py-2 xl:table-cell">Joined Date</th>
                            <th class="hidden w-[12%] px-3 py-2 xl:table-cell">Last Active</th>
                            <th class="w-[72px] px-3 py-2 text-right">Actions</th>
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
                            <tr class="odd:bg-emerald-50/45 even:bg-white">
                                <td class="px-3 py-2">
                                    <a href="{{ route('admin.users.profile', ['user' => $user]) }}" class="flex min-w-0 items-center gap-3">
                                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">
                                            {{ mb_substr($user->name, 0, 1) }}
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block truncate font-semibold text-slate-800">{{ $user->name }}</span>
                                            <span class="block truncate text-xs text-slate-500 lg:hidden">{{ $user->display_role }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td class="truncate px-3 py-2 text-slate-600">{{ $user->email }}</td>
                                <td class="hidden truncate px-3 py-2 text-slate-600 md:table-cell">{{ $user->login }}</td>
                                <td class="px-2 py-2 text-center">
                                    <span class="inline-flex w-full max-w-[66px] justify-center rounded-full px-2 py-1 text-[11px] font-semibold leading-4 sm:max-w-[76px] sm:text-xs {{ $status['class'] }}">
                                        {{ $status['label'] }}
                                    </span>
                                </td>
                                <td class="hidden truncate px-3 py-2 text-slate-600 lg:table-cell">{{ $user->display_role }}</td>
                                <td class="hidden truncate px-3 py-2 text-slate-600 xl:table-cell">{{ $user->created_at?->format('F j, Y') }}</td>
                                <td class="hidden truncate px-3 py-2 text-slate-600 xl:table-cell">{{ $user->updated_at?->diffForHumans() }}</td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('admin.users.profile', ['user' => $user]) }}" class="inline-flex rounded-md p-1.5 text-slate-600 transition hover:bg-slate-100 hover:text-slate-950" aria-label="Profil">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path d="M13.586 3.586a2 2 0 0 1 2.828 2.828l-7.793 7.793a1 1 0 0 1-.414.242l-3 1a1 1 0 0 1-1.265-1.265l1-3a1 1 0 0 1 .242-.414l7.793-7.793Z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('admin.users.index') }}" class="inline-flex rounded-md p-1.5 text-cyan-700 transition hover:bg-cyan-50" aria-label="Tasdiqlash boshqaruvi">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M10 1.5 3.75 4.25v4.7c0 4.02 2.58 7.58 6.25 8.85 3.67-1.27 6.25-4.83 6.25-8.85v-4.7L10 1.5Zm2.78 6.97a.75.75 0 0 0-1.06-1.06L9.25 9.88 8.28 8.9a.75.75 0 0 0-1.06 1.06l1.5 1.5c.3.3.77.3 1.06 0l3-3Z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-8 text-center text-slate-500">Foydalanuvchi topilmadi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm font-medium text-slate-600">
                    Rows per page {{ $perPage }} · {{ $users->total() }} rows
                </div>
                <div class="max-w-full overflow-hidden">
                    {{ $users->onEachSide(1)->links() }}
                </div>
            </div>
        </section>
    </div>
</x-app-layout>

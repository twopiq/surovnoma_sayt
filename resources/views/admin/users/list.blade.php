<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Users List</h2>
                <p class="mt-1 text-sm text-slate-500">Foydalanuvchilarni qidirish, status va profilini ko'rish.</p>
            </div>
            <div class="text-sm text-slate-500">Application / Users / <span class="font-semibold text-violet-700">Users List</span></div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <form method="GET" action="{{ route('admin.users.list') }}" class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <select name="per_page" class="w-full rounded-md border-slate-300 text-sm shadow-sm sm:w-40" onchange="this.form.submit()">
                        @foreach ([10, 25, 50] as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>Show {{ $option }}</option>
                        @endforeach
                    </select>

                    <div class="flex">
                        <input name="search" value="{{ $search }}" placeholder="Search.." class="w-full rounded-l-md border-slate-300 text-sm shadow-sm sm:w-80" />
                        <button class="rounded-r-md bg-violet-700 px-4 text-white">
                            <span class="sr-only">Qidirish</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.47 9.77l2.63 2.63a1 1 0 0 0 1.42-1.42l-2.63-2.63A5.5 5.5 0 0 0 9 3.5ZM5.5 9a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-md bg-violet-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-800">
                    Tasdiqlash sahifasi
                </a>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3"><input type="checkbox" class="rounded border-slate-300" disabled></th>
                            <th class="px-5 py-3">Serial</th>
                            <th class="px-5 py-3">Registered On</th>
                            <th class="px-5 py-3">User Name</th>
                            <th class="px-5 py-3">Email</th>
                            <th class="px-5 py-3">Phone</th>
                            <th class="px-5 py-3">Position</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-5 py-3"><input type="checkbox" class="rounded border-slate-300" disabled></td>
                                <td class="px-5 py-3 text-slate-600">{{ $users->firstItem() + $loop->index }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->created_at?->format('d M Y') }}</td>
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.users.profile', ['user' => $user]) }}" class="flex items-center gap-3">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700">
                                            {{ mb_substr($user->name, 0, 1) }}
                                        </span>
                                        <span class="font-semibold text-slate-900">{{ $user->name }}</span>
                                    </a>
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->email }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->phone ?? '-' }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->job_title ?: $user->display_role }}</td>
                                <td class="px-5 py-3">
                                    @if ($user->approved_at && $user->is_active)
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Active</span>
                                    @elseif (! $user->is_active)
                                        <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">Inactive</span>
                                    @else
                                        <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-700">Pending</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.users.profile', ['user' => $user]) }}" class="inline-flex rounded-md px-2 py-1 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Profil">
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path d="M10 6.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM11.5 15a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-8 text-center text-slate-500">Foydalanuvchi topilmadi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-slate-600">
                    Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} entries
                </div>
                {{ $users->links() }}
            </div>
        </section>
    </div>
</x-app-layout>

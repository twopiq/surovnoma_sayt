<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-['Space_Grotesk'] text-2xl font-bold">User Profile</h2>
                <p class="mt-1 text-sm text-slate-500">Foydalanuvchi ma'lumotlari va tizimdagi holati.</p>
            </div>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.users.list') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Ortga qaytish
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-5 px-4 pt-8 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="relative z-0 h-44 bg-[#123b2f]">
                    <div class="h-full w-full bg-[linear-gradient(135deg,#123b2f_0%,#0f8f6b_52%,#fda13f_100%)] opacity-90"></div>
                </div>

                <div class="relative z-10 px-5 pb-6">
                    <div class="-mt-14 flex justify-center">
                        <span class="admin-profile-token relative z-20 inline-flex h-28 w-28 items-center justify-center rounded-full border-4 border-white bg-emerald-100 text-4xl font-bold text-emerald-700 shadow-sm">
                            {{ mb_substr($selectedUser->name, 0, 1) }}
                        </span>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-lg border border-slate-200">
                        <div class="grid grid-cols-[0.85fr_1.15fr] border-b border-slate-200 px-4 py-3 text-sm">
                            <div class="text-slate-500">Full Name</div>
                            <div class="font-semibold text-slate-900">: {{ $selectedUser->name }}</div>
                        </div>
                        <div class="grid grid-cols-[0.85fr_1.15fr] border-b border-slate-200 px-4 py-3 text-sm">
                            <div class="text-slate-500">Email</div>
                            <div class="font-semibold text-slate-900">: {{ $selectedUser->email }}</div>
                        </div>
                        <div class="grid grid-cols-[0.85fr_1.15fr] border-b border-slate-200 px-4 py-3 text-sm">
                            <div class="text-slate-500">Phone Number</div>
                            <div class="font-semibold text-slate-900">: {{ $selectedUser->phone ?? '-' }}</div>
                        </div>
                        <div class="grid grid-cols-[0.85fr_1.15fr] border-b border-slate-200 px-4 py-3 text-sm">
                            <div class="text-slate-500">Registration Date</div>
                            <div class="font-semibold text-slate-900">: {{ $selectedUser->created_at?->format('d M, Y') }}</div>
                        </div>
                        <div class="grid grid-cols-[0.85fr_1.15fr] px-4 py-3 text-sm">
                            <div class="text-slate-500">Status</div>
                            <div>
                                @if ($selectedUser->approved_at && $selectedUser->is_active)
                                    <span class="admin-profile-status rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Active</span>
                                @elseif (! $selectedUser->is_active)
                                    <span class="admin-profile-status rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">Inactive</span>
                                @else
                                    <span class="admin-profile-status rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-700">Pending</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-['Space_Grotesk'] text-lg font-bold text-slate-900">User Profile</h3>
                </div>

                <div class="space-y-4 p-5">
                    <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-center">
                        <label class="text-sm text-slate-600">Name</label>
                        <input value="{{ $selectedUser->name }}" class="rounded-md border-slate-300 shadow-sm" readonly>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-center">
                        <label class="text-sm text-slate-600">Email</label>
                        <input value="{{ $selectedUser->email }}" class="rounded-md border-slate-300 shadow-sm" readonly>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-center">
                        <label class="text-sm text-slate-600">Login</label>
                        <input value="{{ $selectedUser->login }}" class="rounded-md border-slate-300 shadow-sm" readonly>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-center">
                        <label class="text-sm text-slate-600">Phone</label>
                        <input value="{{ $selectedUser->phone ?? '-' }}" class="rounded-md border-slate-300 shadow-sm" readonly>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-center">
                        <label class="text-sm text-slate-600">Position</label>
                        <input value="{{ $selectedUser->job_title ?: $selectedUser->display_role }}" class="rounded-md border-slate-300 shadow-sm" readonly>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-center">
                        <label class="text-sm text-slate-600">Department</label>
                        <input value="{{ $selectedUser->department?->name ?? '-' }}" class="rounded-md border-slate-300 shadow-sm" readonly>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-[220px_1fr] sm:items-center">
                        <label class="text-sm text-slate-600">Role</label>
                        <input value="{{ $selectedUser->display_role }}" class="rounded-md border-slate-300 shadow-sm" readonly>
                    </div>

                    @if ($selectedUser->is(auth()->user()))
                        <a href="{{ route('profile.edit') }}" class="inline-flex rounded-md bg-violet-700 px-5 py-2 text-sm font-semibold text-white transition hover:bg-violet-800">
                            Profilni tahrirlash
                        </a>
                    @else
                        <span class="inline-flex rounded-md bg-slate-100 px-5 py-2 text-sm font-semibold text-slate-500">
                            Faqat ko'rish rejimi
                        </span>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

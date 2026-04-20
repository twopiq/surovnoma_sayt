<x-app-layout>
    <x-slot name="header">
        <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Xodimlar bo'limlari</h2>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('admin.departments.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <h3 class="font-semibold">Yangi xodim bo'limi</h3>
            <p class="mt-2 text-sm text-slate-500">Bu bo'limlar xodim yoki murojaatchi qayerda ishlashini bildiradi. Muammo turi alohida kategoriyalarda yuritiladi.</p>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <input name="name" placeholder="Nomi" class="rounded-md border-slate-300 shadow-sm" />
                <input name="code" placeholder="Kodi" class="rounded-md border-slate-300 shadow-sm" />
                <input name="description" placeholder="Tavsif" class="rounded-md border-slate-300 shadow-sm" />
            </div>
            <button class="mt-4 rounded-full bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Qo‘shish</button>
        </form>

        <div class="space-y-4">
            @foreach ($departments as $department)
                <form method="POST" action="{{ route('admin.departments.update', $department) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    @csrf
                    @method('PATCH')
                    <div class="grid gap-3 md:grid-cols-4">
                        <input name="name" value="{{ $department->name }}" class="rounded-md border-slate-300 shadow-sm" />
                        <input name="code" value="{{ $department->code }}" class="rounded-md border-slate-300 shadow-sm" />
                        <input name="description" value="{{ $department->description }}" class="rounded-md border-slate-300 shadow-sm" />
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="is_active" value="1" @checked($department->is_active)>
                            Faol
                        </label>
                    </div>
                    <button class="mt-4 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Yangilash</button>
                </form>
            @endforeach
        </div>
    </div>
</x-app-layout>

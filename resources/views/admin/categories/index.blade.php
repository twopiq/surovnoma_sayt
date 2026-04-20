<x-app-layout>
    <x-slot name="header">
        <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Muammo kategoriyalari</h2>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('admin.categories.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <h3 class="font-semibold">Yangi kategoriya</h3>
            <p class="mt-2 text-sm text-slate-500">Bu ro'yxat murojaatdagi muammo turini bildiradi. Bo'limlar esa xodim yoki murojaatchi ishlaydigan bo'lim sifatida qoladi.</p>
            <div class="mt-4 grid gap-3 md:grid-cols-[1fr_1fr_220px]">
                <input name="name" value="{{ old('name') }}" placeholder="Kategoriya nomi" class="rounded-md border-slate-300 shadow-sm" />
                <input name="description" value="{{ old('description') }}" placeholder="Tavsif" class="rounded-md border-slate-300 shadow-sm" />
                <select name="default_priority" class="rounded-md border-slate-300 shadow-sm">
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority->value }}" @selected(old('default_priority', \App\Enums\TicketPriority::Medium->value) === $priority->value)>{{ $priority->label() }}</option>
                    @endforeach
                </select>
            </div>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
            <x-input-error :messages="$errors->get('default_priority')" class="mt-2" />
            <button class="mt-4 rounded-full bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Qo'shish</button>
        </form>

        <div class="space-y-4">
            @forelse ($categories as $category)
                <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    @csrf
                    @method('PATCH')
                    <div class="grid gap-3 md:grid-cols-[1fr_1fr_220px_120px]">
                        <input name="name" value="{{ old('name', $category->name) }}" class="rounded-md border-slate-300 shadow-sm" />
                        <input name="description" value="{{ old('description', $category->description) }}" class="rounded-md border-slate-300 shadow-sm" />
                        <select name="default_priority" class="rounded-md border-slate-300 shadow-sm">
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}" @selected(old('default_priority', $category->default_priority->value) === $priority->value)>{{ $priority->label() }}</option>
                            @endforeach
                        </select>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="is_active" value="1" @checked($category->is_active)>
                            Faol
                        </label>
                    </div>
                    <button class="mt-4 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Yangilash</button>
                </form>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500">
                    Hozircha kategoriya yo'q.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('tickets.index') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Ortga qaytish</a>
            <h2 class="font-['Space_Grotesk'] text-2xl font-bold text-slate-900">Yangi murojaat</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 pt-8 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    Forma yuborilmadi. Iltimos, xatolarni to'g'rilang.
                </div>
            @endif
            <div>
                <x-input-label for="category_id" value="Muammo kategoriyasi" />
                <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" required>
                    <option value="">Tanlang</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="description" value="Tavsif" />
                <textarea id="description" name="description" rows="7" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" required>{{ old('description') }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="attachments" value="Fayl biriktirish" />
                <x-file-upload-input id="attachments" name="attachments[]" class="mt-1" />
                <x-input-error :messages="$errors->get('attachments')" class="mt-2" />
                <x-input-error :messages="$errors->get('attachments.*')" class="mt-2" />
            </div>
            <div class="flex justify-end">
                <x-primary-button class="bg-cyan-700 hover:bg-cyan-800">Yuborish</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>

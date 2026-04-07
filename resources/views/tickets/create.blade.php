<x-app-layout>
    <x-slot name="header">
        <h2 class="font-['Space_Grotesk'] text-2xl font-bold text-slate-900">Yangi murojaat</h2>
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
                <x-input-label for="description" value="Tavsif" />
                <textarea id="description" name="description" rows="7" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" required>{{ old('description') }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="attachments" value="Fayl biriktirish" />
                <input id="attachments" type="file" name="attachments[]" multiple class="mt-1 block w-full text-sm text-slate-500" />
                <p class="mt-2 text-xs text-slate-400">5 tagacha, har biri 5 MB, JPG/JPEG/PNG/PDF/DOC/DOCX.</p>
                <x-input-error :messages="$errors->get('attachments')" class="mt-2" />
                <x-input-error :messages="$errors->get('attachments.*')" class="mt-2" />
            </div>
            <div class="flex justify-end">
                <x-primary-button class="bg-cyan-700 hover:bg-cyan-800">Yuborish</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>

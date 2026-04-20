<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('operator.tickets.index') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Ortga qaytish</a>
            <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Operator orqali murojaat yaratish</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 pt-8 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('operator.tickets.store') }}" enctype="multipart/form-data" class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    Forma yuborilmadi. Iltimos, xatolarni to'g'rilang.
                </div>
            @endif
            <div class="grid gap-4 sm:grid-cols-2">
                <x-text-input name="name" :value="old('name')" placeholder="F.I.Sh." />
                <x-phone-input id="operator_phone_display" name="phone" :value="old('phone')" label="Telefon" :hint="null" />
                <x-text-input name="email" :value="old('email')" placeholder="Email" />
                <x-text-input name="department" :value="old('department')" placeholder="Ishlaydigan bo'lim" />
            </div>
            <div>
                <x-text-input name="job_title" :value="old('job_title')" placeholder="Lavozim" />
            </div>
            <div>
                <select id="category_id" name="category_id" class="block w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" required>
                    <option value="">Muammo kategoriyasini tanlang</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
            </div>
            <div>
                <textarea name="description" rows="7" class="block w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" placeholder="Muammo tavsifi">{{ old('description') }}</textarea>
            </div>
            <div>
                <x-file-upload-input id="attachments" name="attachments[]" />
                <x-input-error :messages="$errors->get('attachments')" class="mt-2" />
                <x-input-error :messages="$errors->get('attachments.*')" class="mt-2" />
            </div>
            <div class="flex justify-end">
                <x-primary-button class="bg-cyan-700 hover:bg-cyan-800">Saqlash</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>

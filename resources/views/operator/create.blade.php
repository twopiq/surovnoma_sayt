<x-app-layout>
    <x-slot name="header">
        <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Operator orqali murojaat yaratish</h2>
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
                <x-text-input name="phone" :value="old('phone')" placeholder="Telefon" />
                <x-text-input name="email" :value="old('email')" placeholder="Email" />
                <x-text-input name="department" :value="old('department')" placeholder="Bo'lim" />
            </div>
            <div>
                <x-text-input name="job_title" :value="old('job_title')" placeholder="Lavozim" />
            </div>
            <div>
                <textarea name="description" rows="7" class="block w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" placeholder="Muammo tavsifi">{{ old('description') }}</textarea>
            </div>
            <div>
                <input type="file" name="attachments[]" multiple class="block w-full text-sm text-slate-500" />
                <p class="mt-2 text-xs text-slate-400">5 tagacha, har biri 5 MB, JPG/JPEG/PNG/PDF/DOC/DOCX.</p>
                <x-input-error :messages="$errors->get('attachments')" class="mt-2" />
                <x-input-error :messages="$errors->get('attachments.*')" class="mt-2" />
            </div>
            <div class="flex justify-end">
                <x-primary-button class="bg-cyan-700 hover:bg-cyan-800">Saqlash</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>

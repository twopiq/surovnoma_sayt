<x-guest-layout>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="font-['Space_Grotesk'] text-2xl font-bold">Guest kuzatuvi</h1>
            <p class="mt-2 text-sm text-slate-500">Ticket ID va maxfiy tracking code orqali statusni ko‘ring.</p>
        </div>
        <a href="{{ route('home') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Home</a>
    </div>

    <form method="POST" action="{{ route('guest.lookup') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="reference" value="Ticket ID" />
            <x-text-input id="reference" name="reference" class="mt-1 block w-full" :value="old('reference')" required />
            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="tracking_code" value="Tracking code" />
            <x-text-input id="tracking_code" name="tracking_code" class="mt-1 block w-full" :value="old('tracking_code')" required />
            <x-input-error :messages="$errors->get('tracking_code')" class="mt-2" />
        </div>
        <div style="align-items: center;" class="flex justify-between">
            <a href="{{ route('home') }}" class="text-sm text-slate-500 underline">Asosiy ekran</a>
            <a href="{{ route('guest.create') }}" class="text-sm text-slate-500 underline">Yangi murojaat yuborish</a>
            <x-primary-button class="bg-cyan-700 hover:bg-cyan-800">Kuzatish</x-primary-button>
        </div>
    </form>
</x-guest-layout>

<x-guest-layout>
    <div class="rounded-2xl bg-emerald-50 p-5">
        <h1 class="font-['Space_Grotesk'] text-2xl font-bold text-slate-900">Murojaat qabul qilindi</h1>
        <p class="mt-3 text-sm text-slate-600">Quyidagi ma’lumotlarni saqlab qo‘ying. Tracking code keyin qayta ko‘rsatilmaydi.</p>
    </div>

    <div class="mt-6 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5">
        <div>
            <div class="text-sm text-slate-500">Ticket ID</div>
            <div class="font-['Space_Grotesk'] text-2xl font-bold text-slate-900">{{ $ticket->reference }}</div>
        </div>
        <div>
            <div class="text-sm text-slate-500">Maxfiy tracking code</div>
            <div class="font-['Space_Grotesk'] text-2xl font-bold text-cyan-700">{{ $trackingCode }}</div>
        </div>
    </div>

    <div class="mt-6 flex justify-between">
        <a href="{{ route('guest.create') }}" class="text-sm text-slate-500 underline">Yana murojaat yuborish</a>
        <a href="{{ route('guest.track') }}" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Kuzatishga o‘tish</a>
    </div>
</x-guest-layout>

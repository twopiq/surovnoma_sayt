@php
    $downloadFileName = 'murojaat-'.$ticket->reference.'.txt';
    $downloadContent = implode("\n", [
        "Murojaat ma'lumotlari",
        '',
        'Ticket ID: '.$ticket->reference,
        'Maxfiy tracking code: '.$trackingCode,
        'Sana: '.($ticket->created_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s')),
        'Kuzatish: '.route('guest.track'),
        '',
        "Ushbu ma'lumotlarni saqlab qo'ying. Tracking code keyin qayta ko'rsatilmaydi.",
    ]);
@endphp

<x-public-layout title="Murojaat yuborildi">
    <div class="grid items-start gap-6 py-6 lg:grid-cols-[1.1fr_1fr]">
        <div>
            <span class="status-badge status--completed">Qabul qilindi</span>
            <h1 class="mt-3 font-display text-[32px] font-bold leading-[40px] sm:text-[38px] sm:leading-[44px]">Murojaatingiz yuborildi</h1>
            <p class="mb-5 mt-3 text-base text-muted">Kodni hozir saqlang. U keyin qayta ko'rsatilmaydi.</p>
            <div class="flex flex-wrap gap-2">
                <a href="data:text/plain;charset=utf-8,{{ rawurlencode($downloadContent) }}" download="{{ $downloadFileName }}" class="btn btn-primary btn-lg">Faylga yuklab olish</a>
                <a href="{{ route('guest.tickets.show', $ticket) }}" class="btn btn-secondary btn-lg">Kuzatishga o'tish</a>
            </div>
            <a href="{{ route('guest.create') }}" class="mt-5 inline-block text-sm font-semibold text-accent-strong hover:underline">Yana murojaat yuborish</a>
        </div>

        <div class="overflow-hidden rounded-3xl border border-line bg-surface">
            <div class="border-b-2 border-dashed border-line-strong p-6">
                <div class="text-xs text-muted">Ticket ID</div>
                <div class="font-mono text-[22px] font-medium leading-[34px] sm:text-[26px]">{{ $ticket->reference }}</div>
                <div class="mt-3.5 text-xs text-muted">Maxfiy tracking code</div>
                <div class="select-all font-mono text-[22px] font-medium leading-[34px] text-accent-strong sm:text-[26px]">{{ $trackingCode }}</div>
            </div>
            <div class="px-6 py-5">
                <div class="mb-2.5 text-xs text-muted">Keyingi qadamlar</div>
                <ol class="pub-steps m-0 p-0">
                    <li class="is-current"><i>1</i>Yangi</li>
                    <li><i>2</i>Taqsimlandi</li>
                    <li><i>3</i>Jarayonda</li>
                    <li><i>4</i>Yopildi</li>
                </ol>
            </div>
        </div>
    </div>
</x-public-layout>

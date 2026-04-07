<x-app-layout>
    <x-slot name="header">
        <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Ijrochi kartochkasi</h2>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 pt-8 lg:grid-cols-[1.3fr_0.9fr] sm:px-6 lg:px-8">
        <div class="space-y-6">
            @include('partials.ticket-card', ['ticket' => $ticket])
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <h3 class="font-semibold">Izohlar</h3>
                <div class="mt-4 space-y-3">
                    @foreach ($ticket->comments as $comment)
                        <div class="rounded-xl {{ $comment->is_public ? 'bg-cyan-50' : 'bg-amber-50' }} p-3 text-sm text-slate-700">
                            <div class="mb-1 font-semibold">{{ $comment->user?->name ?? 'Tizim' }}</div>
                            {{ $comment->body }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-6">
            @php
                $canClaim = $ticket->canExecutorClaim();
                $claimLabel = $ticket->executorClaimLabel();
                $hasPendingReturnRequest = $ticket->hasPendingReturnRequest();
            @endphp

            <form method="POST" action="{{ route('executor.tickets.comment', $ticket) }}" class="rounded-2xl border border-slate-200 bg-white p-6">
                @csrf
                <h3 class="font-semibold">Izoh qoldirish</h3>
                <textarea name="body" rows="3" class="mt-4 block w-full rounded-md border-slate-300 shadow-sm" placeholder="Izoh" required></textarea>
                <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="is_public" value="1">
                    Murojaatchiga ko'rinsin
                </label>
                <button class="mt-4 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Izoh yuborish</button>
            </form>

            <form method="POST" action="{{ route('executor.tickets.start', $ticket) }}" class="rounded-2xl border border-slate-200 bg-white p-6">
                @csrf
                <h3 class="font-semibold">Ishga olish</h3>
                <button
                    @disabled(! $canClaim)
                    class="mt-4 rounded-full px-4 py-2 text-sm font-semibold text-white {{ $canClaim ? 'bg-cyan-700 hover:bg-cyan-800' : 'cursor-not-allowed bg-slate-300 text-slate-600' }}"
                >
                    {{ $claimLabel }}
                </button>
                <p class="mt-3 text-sm text-slate-500">
                    @if ($ticket->status === \App\Enums\TicketStatus::Returned)
                        Qaytarilgan murojaatni yana ishga olishingiz mumkin.
                    @elseif ($ticket->status === \App\Enums\TicketStatus::InProgress)
                        Murojaat allaqachon qabul qilingan.
                    @else
                        Tugma murojaat holatiga qarab avtomatik yangilanadi.
                    @endif
                </p>
            </form>

            <form method="POST" action="{{ route('executor.tickets.complete', $ticket) }}" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-6">
                @csrf
                <h3 class="font-semibold">Bajarildi deb yuborish</h3>
                <textarea name="note" rows="3" class="mt-4 block w-full rounded-md border-slate-300 shadow-sm" placeholder="Izoh"></textarea>
                <input type="file" name="proofs[]" multiple class="mt-4 block w-full text-sm text-slate-500" required />
                <x-input-error :messages="$errors->get('proofs')" class="mt-2" />
                <x-input-error :messages="$errors->get('proofs.*')" class="mt-2" />
                <button class="mt-4 rounded-full bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Bajarildi</button>
            </form>

            <form method="POST" action="{{ route('executor.tickets.return', $ticket) }}" class="rounded-2xl border border-slate-200 bg-white p-6">
                @csrf
                <h3 class="font-semibold">Qaytarish so'rovi</h3>
                <textarea
                    name="reason"
                    rows="3"
                    class="mt-4 block w-full rounded-md border-slate-300 shadow-sm"
                    placeholder="Sabab"
                    @disabled($hasPendingReturnRequest)
                    required
                ></textarea>
                <button
                    @disabled($hasPendingReturnRequest)
                    class="mt-4 rounded-full px-4 py-2 text-sm font-semibold text-white {{ $hasPendingReturnRequest ? 'cursor-not-allowed bg-slate-300 text-slate-600' : 'bg-amber-600' }}"
                >
                    {{ $hasPendingReturnRequest ? "So'rov yuborildi" : 'Adminga qaytarish' }}
                </button>
                <p class="mt-3 text-sm text-slate-500">
                    {{ $hasPendingReturnRequest ? "Admindan javob kelmaguncha qayta so'rov yuborib bo'lmaydi." : "Agar murojaatni qaytarish kerak bo'lsa, sababini yozib yuboring." }}
                </p>
            </form>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('tickets.index') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">{{ __('Ortga qaytish') }}</a>
            <h2 class="font-display text-2xl font-bold text-slate-900">{{ __('Murojaat kartochkasi') }}</h2>
        </div>
    </x-slot>

    <div class="mx-auto grid max-w-none gap-6 px-4 pt-8 lg:grid-cols-[1.4fr_0.8fr] sm:px-6 lg:px-8">
        <div class="space-y-6">
            @include('partials.ticket-card', ['ticket' => $ticket])
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <h3 class="font-semibold text-slate-900">{{ __('Public izoh qo‘shish') }}</h3>
                <form method="POST" action="{{ route('tickets.comment', $ticket) }}" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="body" rows="4" class="block w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" required>{{ old('body') }}</textarea>
                    <x-primary-button class="bg-cyan-700 hover:bg-cyan-800">{{ __('Izoh yuborish') }}</x-primary-button>
                </form>
            </div>

            @if ($ticket->canBeCancelledBy(auth()->user()))
                <div class="rounded-2xl border border-red-200 bg-white p-6" x-data="{ open: {{ $errors->has('reason') ? 'true' : 'false' }} }">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-slate-900">{{ __('Murojaatni bekor qilish') }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ __("Muammo hal bo'lgan yoki murojaat endi kerak bo'lmasa, uni bekor qiling — ijrochi ishni to'xtatadi.") }}</p>
                        </div>
                        <button type="button" class="btn btn-danger" x-show="! open" @click="open = true">{{ __('Bekor qilish') }}</button>
                    </div>

                    <form method="POST" action="{{ route('tickets.cancel', $ticket) }}" class="mt-4 space-y-3" x-show="open" x-cloak>
                        @csrf
                        <label for="cancel-reason" class="block text-sm font-medium text-slate-700">{{ __('Sabab (ixtiyoriy)') }}</label>
                        <textarea id="cancel-reason" name="reason" rows="2" maxlength="500" class="block w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500">{{ old('reason') }}</textarea>
                        <x-input-error :messages="$errors->get('reason')" />
                        <p class="text-sm text-red-700">{{ __('Bekor qilingan murojaatni qayta tiklab bo\'lmaydi.') }}</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-danger">{{ __('Ha, bekor qilish') }}</button>
                            <button type="button" class="btn btn-secondary" @click="open = false">{{ __('Yo\'q') }}</button>
                        </div>
                    </form>
                </div>
            @elseif ($ticket->status === \App\Enums\TicketStatus::Cancelled)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">
                    {{ __('Bu murojaat siz tomondan bekor qilingan.') }}
                    @if ($ticket->metadata['cancel_reason'] ?? null)
                        <span class="block mt-1">{{ __('Sabab') }}: {{ $ticket->metadata['cancel_reason'] }}</span>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <h3 class="font-semibold text-slate-900">{{ __('Public izohlar') }}</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($ticket->comments as $comment)
                        <div class="rounded-xl bg-slate-50 p-3 text-sm text-slate-600">{{ \App\Support\StoredText::translate($comment->body) }}</div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('Hozircha izoh yo‘q.') }}</p>
                    @endforelse
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <h3 class="font-semibold text-slate-900">{{ __('Biriktirilgan fayllar') }}</h3>
                <div class="mt-4 space-y-2 text-sm text-slate-600">
                    @forelse ($ticket->attachments as $attachment)
                        <a href="{{ route('attachments.download', $attachment) }}" class="block font-medium text-sky-700 hover:underline">{{ $attachment->original_name }}</a>
                    @empty
                        <p class="text-slate-500">{{ __('Fayl biriktirilmagan.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

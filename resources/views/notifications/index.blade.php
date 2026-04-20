<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-cyan-700">Bildirishnomalar</p>
                <h1 class="mt-2 font-['Space_Grotesk'] text-3xl font-bold text-slate-950">Barcha xabarlar</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">
                    Sizga yuborilgan tizim xabarlari shu yerda saqlanadi. Xabarni bosib unga bog'liq sahifaga o'ting.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="rounded-md border border-cyan-200 bg-cyan-50 px-4 py-2 text-sm font-semibold text-cyan-800 transition hover:bg-cyan-100">
                            Hammasini o'qildi qilish
                        </button>
                    </form>
                @endif

                @if ($notifications->total() > 0)
                    <form method="POST" action="{{ route('notifications.clear-all') }}">
                        @csrf
                        <button type="submit" class="rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                            Tozalash
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <section class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="text-sm font-semibold text-slate-800">
                    O'qilmagan xabarlar: <span class="text-cyan-700">{{ $unreadCount }}</span>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($notifications as $notification)
                    <div class="flex items-start gap-4 px-5 py-4 {{ is_null($notification->read_at) ? 'bg-cyan-50/55' : 'bg-white' }}">
                        <a href="{{ route('notifications.show', $notification->id) }}" class="flex min-w-0 flex-1 items-start gap-4 transition hover:opacity-90">
                            <span class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full {{ is_null($notification->read_at) ? 'bg-cyan-600' : 'bg-slate-300' }}"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-base font-bold text-slate-950">{{ $notification->data['title'] ?? 'Bildirishnoma' }}</span>
                                <span class="mt-1 block text-sm leading-6 text-slate-600">{{ $notification->data['body'] ?? '' }}</span>
                                <span class="mt-3 flex flex-wrap items-center gap-3 text-xs font-semibold text-slate-500">
                                    <span>{{ $notification->created_at?->format('d.m.Y H:i') }}</span>
                                    @if (! empty($notification->data['url']))
                                        <span class="text-cyan-700">Bog'liq sahifani ochish</span>
                                    @endif
                                </span>
                            </span>
                        </a>

                        <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}" class="shrink-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-md p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600" aria-label="Bildirishnomani o'chirish">
                                <svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="px-5 py-12 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-md bg-slate-100 text-slate-500">
                            <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M4 8a6 6 0 1 1 12 0v3.586l.707.707A1 1 0 0 1 16 14H4a1 1 0 0 1-.707-1.707L4 11.586V8Z" />
                                <path d="M8 15a2 2 0 1 0 4 0H8Z" />
                            </svg>
                        </div>
                        <h2 class="mt-4 text-lg font-bold text-slate-950">Bildirishnoma yo'q</h2>
                        <p class="mt-1 text-sm text-slate-500">Yangi tizim xabarlari kelganda shu yerda ko'rinadi.</p>
                    </div>
                @endforelse
            </div>
        </section>

        @if ($notifications->hasPages())
            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-app-layout>

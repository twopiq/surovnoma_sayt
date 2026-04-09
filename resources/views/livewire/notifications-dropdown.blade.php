<div x-data="{ open: @js((bool) session('notifications_open')) }" class="relative">
    <button
        type="button"
        @click="open = !open"
        class="flex cursor-pointer items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm"
    >
        <span>Bildirishnomalar</span>
        <span class="rounded-full bg-cyan-100 px-2 py-0.5 text-xs font-semibold text-cyan-700">{{ $unreadCount }}</span>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top.right
        @click.outside="open = false"
        @keydown.escape.window="open = false"
        class="absolute right-0 z-20 mt-3 w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
    >
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <div class="text-sm font-semibold text-slate-800">So'nggi bildirishnomalar</div>
            <div class="flex items-center gap-3">
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-cyan-700 transition hover:text-cyan-800">
                            O'qildi
                        </button>
                    </form>
                @endif
                @if ($notifications->isNotEmpty())
                    <form method="POST" action="{{ route('notifications.clear-all') }}">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-rose-700 transition hover:text-rose-800">
                            Tozalash
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="max-h-80 overflow-y-auto">
            @forelse ($notifications as $notification)
                <div class="flex items-start gap-3 border-b border-slate-100 px-4 py-3 {{ is_null($notification->read_at) ? 'bg-cyan-50/70' : 'bg-white' }}">
                    <a href="{{ route('notifications.show', $notification->id) }}" class="flex min-w-0 flex-1 items-start gap-3 transition hover:opacity-90">
                        <span class="mt-1 h-2.5 w-2.5 rounded-full {{ is_null($notification->read_at) ? 'bg-cyan-500' : 'bg-slate-300' }}"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-800">{{ $notification->data['title'] ?? 'Bildirishnoma' }}</span>
                            <span class="mt-1 block text-sm text-slate-500">{{ $notification->data['body'] ?? '' }}</span>
                            @if (! empty($notification->data['url']))
                                <span class="mt-2 block text-xs font-medium text-cyan-700">Ochish</span>
                            @endif
                        </span>
                    </a>
                    <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}" class="shrink-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-rose-600" aria-label="Bildirishnomani o'chirish">
                            <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </form>
                </div>
            @empty
                <div class="px-4 py-6 text-sm text-slate-500">Bildirishnoma yo'q.</div>
            @endforelse
        </div>
    </div>
</div>

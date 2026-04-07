<div class="relative">
    <details class="group">
        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm">
            <span>Bildirishnomalar</span>
            <span class="rounded-full bg-cyan-100 px-2 py-0.5 text-xs font-semibold text-cyan-700">{{ $unreadCount }}</span>
        </summary>
        <div class="absolute right-0 z-20 mt-3 w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <div class="text-sm font-semibold text-slate-800">So'nggi bildirishnomalar</div>
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-cyan-700 transition hover:text-cyan-800">
                            Barchasini o'qildi deb belgilash
                        </button>
                    </form>
                @endif
            </div>
            <div class="max-h-80 overflow-y-auto">
                @forelse ($notifications as $notification)
                    <a
                        href="{{ route('notifications.show', $notification->id) }}"
                        class="block w-full border-b border-slate-100 px-4 py-3 text-left transition hover:bg-slate-50 {{ is_null($notification->read_at) ? 'bg-cyan-50/70' : 'bg-white' }}"
                    >
                        <div class="flex items-start gap-3">
                            <span class="mt-1 h-2.5 w-2.5 rounded-full {{ is_null($notification->read_at) ? 'bg-cyan-500' : 'bg-slate-300' }}"></span>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-slate-800">{{ $notification->data['title'] ?? 'Bildirishnoma' }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ $notification->data['body'] ?? '' }}</div>
                                @if (! empty($notification->data['url']))
                                    <div class="mt-2 text-xs font-medium text-cyan-700">Ochish</div>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-6 text-sm text-slate-500">Bildirishnoma yo'q.</div>
                @endforelse
            </div>
        </div>
    </details>
</div>

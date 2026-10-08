@php
    $levelTone = fn (string $level) => match ($level) {
        'emergency', 'alert', 'critical', 'error' => 'new',
        'warning' => 'assigned',
        'notice', 'info' => 'completed',
        default => 'closed',
    };
    $formatSize = fn (int $bytes) => $bytes >= 1048576 ? round($bytes / 1048576, 1).' MB' : max(1, round($bytes / 1024)).' KB';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pg-crumb">{{ __('Tizim') }}</div>
            <h2>{{ __('Loglar') }}</h2>
            <p class="pg-sub">{{ __('Kim nima qilgani (audit) va server xatolari.') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-none px-4 pt-6 sm:px-6 lg:px-8">
        <nav class="ui-tabs" aria-label="{{ __('Log turlari') }}">
            <a href="{{ route('admin.system.logs') }}" @if ($tab === 'audit') aria-current="page" @endif>{{ __('Audit jurnali') }}</a>
            <a href="{{ route('admin.system.logs', ['tab' => 'server']) }}" @if ($tab === 'server') aria-current="page" @endif>{{ __('Server loglari') }}</a>
        </nav>

        @if ($tab === 'audit')
            <form method="GET" action="{{ route('admin.system.logs') }}" class="mb-3 flex flex-wrap items-center gap-2" data-auto-filter>
                <label class="sr-only" for="audit-q">{{ __('Qidirish') }}</label>
                <input id="audit-q" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Tavsif bo\'yicha qidirish') }}" class="ui-input w-full sm:w-[260px]">
                <select name="event" aria-label="{{ __('Hodisa') }}" class="py-1.5 pr-8 text-[13px]">
                    <option value="">{{ __('Hodisa: Barchasi') }}</option>
                    @foreach ($events as $event)
                        <option value="{{ $event }}" @selected($filters['event'] === $event)>{{ $event }}</option>
                    @endforeach
                </select>
                <select name="user_id" aria-label="{{ __('Foydalanuvchi') }}" class="py-1.5 pr-8 text-[13px]">
                    <option value="">{{ __('Kim: Barchasi') }}</option>
                    @foreach ($actors as $actor)
                        <option value="{{ $actor->id }}" @selected($filters['user_id'] === $actor->id)>{{ $actor->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" value="{{ $filters['from'] }}" aria-label="{{ __('Dan') }}" class="py-1.5 text-[13px]">
                <input type="date" name="to" value="{{ $filters['to'] }}" aria-label="{{ __('Gacha') }}" class="py-1.5 text-[13px]">
                @if (array_filter($filters))
                    <a href="{{ route('admin.system.logs') }}" class="text-[13px] text-muted hover:text-ink">{{ __('Filtrlarni tozalash') }}</a>
                @endif
            </form>

            <div class="ui-card overflow-x-auto px-3 pb-3 pt-2">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>{{ __('Vaqt') }}</th>
                            <th>{{ __('Kim') }}</th>
                            <th>{{ __('Hodisa') }}</th>
                            <th>{{ __('Tavsif') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr x-data="{ open: false }" class="align-top">
                                <td class="ui-mono whitespace-nowrap text-muted">{{ $log->created_at?->format('d.m.Y H:i:s') }}</td>
                                <td class="whitespace-nowrap">{{ $log->user_id ? ($users[$log->user_id] ?? "#{$log->user_id} (".__("o'chirilgan").')') : __('Tizim') }}</td>
                                <td><span class="ui-tag font-mono">{{ $log->event }}</span></td>
                                <td class="min-w-[260px]">
                                    {{ \App\Support\StoredText::translate($log->description) }}
                                    @if (! empty($log->properties))
                                        <button type="button" class="ml-1 text-xs text-accent hover:underline" @click="open = ! open" x-text="open ? 'yashirish' : 'tafsilot'">{{ __('tafsilot') }}</button>
                                        <pre x-show="open" x-cloak class="ui-mono mt-1.5 max-w-[640px] overflow-x-auto whitespace-pre-wrap break-all rounded-md bg-sunken p-2 text-xs">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-10 text-center text-muted">{{ __('Yozuv topilmadi.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-1 pt-3">{{ $logs->onEachSide(1)->links() }}</div>
            </div>
        @else
            <form method="GET" action="{{ route('admin.system.logs') }}" class="mb-3 flex flex-wrap items-center gap-2" data-auto-filter>
                <input type="hidden" name="tab" value="server">
                <select name="file" aria-label="{{ __('Fayl') }}" class="py-1.5 pr-8 text-[13px]">
                    @foreach ($files as $item)
                        <option value="{{ $item['name'] }}" @selected($file === $item['name'])>{{ $item['name'] }} · {{ $formatSize($item['size']) }}</option>
                    @endforeach
                </select>
                <select name="level" aria-label="{{ __('Daraja') }}" class="py-1.5 pr-8 text-[13px]">
                    <option value="">{{ __('Daraja: Barchasi') }}</option>
                    @foreach ($levels as $item)
                        <option value="{{ $item }}" @selected($level === $item)>{{ ucfirst($item) }}</option>
                    @endforeach
                </select>
                <label class="sr-only" for="server-q">{{ __('Qidirish') }}</label>
                <input id="server-q" name="q" value="{{ $search }}" placeholder="{{ __('Matn bo\'yicha qidirish') }}" class="ui-input w-full sm:w-[260px]">
                @if ($file !== '')
                    <a href="{{ route('admin.system.logs.download', $file) }}" class="btn btn-secondary sm:ml-auto">{{ __('Faylni yuklab olish') }}</a>
                @endif
            </form>
            <p class="ui-hint mb-3">{{ __('Eng so\'nggi 200 ta yozuv (fayl oxiridagi 2 MB dan). Tokenlar va parollar avtomatik yashiriladi.') }}</p>

            <div class="ui-card overflow-hidden !p-0">
                @forelse ($entries as $entry)
                    <div class="border-t border-line px-4 py-2.5 first:border-t-0" x-data="{ open: false }">
                        <div class="flex flex-wrap items-start gap-x-3 gap-y-1">
                            <span class="ui-mono whitespace-nowrap text-xs text-muted">{{ $entry['time']?->format('d.m.Y H:i:s') ?? '—' }}</span>
                            <span class="status-badge status--{{ $levelTone($entry['level']) }} status-badge--xs">{{ strtoupper($entry['level']) }}</span>
                            <span class="min-w-0 flex-1 break-words text-[13px]">{{ \Illuminate\Support\Str::limit($entry['message'], 400) }}</span>
                            @if ($entry['context'] !== '')
                                <button type="button" class="text-xs text-accent hover:underline" @click="open = ! open" x-text="open ? 'yashirish' : 'tafsilot'">{{ __('tafsilot') }}</button>
                            @endif
                        </div>
                        @if ($entry['context'] !== '')
                            <pre x-show="open" x-cloak class="ui-mono mt-2 max-h-[360px] overflow-auto whitespace-pre-wrap break-all rounded-md bg-sunken p-2 text-xs">{{ \Illuminate\Support\Str::limit($entry['context'], 8000) }}</pre>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-10 text-center text-muted">{{ $files->isEmpty() ? __("Log fayllari hali yo'q.") : __('Yozuv topilmadi.') }}</div>
                @endforelse
            </div>
        @endif
    </div>
</x-app-layout>

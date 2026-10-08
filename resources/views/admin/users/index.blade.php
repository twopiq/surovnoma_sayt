@php
    $initials = fn (string $name) => collect(preg_split('/\s+/u', trim($name)))->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('');
    $ago = function ($date) {
        if (! $date) {
            return '—';
        }

        return match (true) {
            $date->isToday() => 'bugun '.$date->format('H:i'),
            $date->isYesterday() => 'kecha '.$date->format('H:i'),
            $date->gt(now()->subDays(7)) => (int) $date->copy()->startOfDay()->diffInDays(now()->startOfDay()).' kun oldin',
            default => $date->format('d.m.Y'),
        };
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pg-crumb">Odamlar</div>
            <h2>{{ $tab === 'pending' ? 'Tasdiq kutayotganlar' : 'Rad etilganlar' }}</h2>
            <p class="pg-sub">Yangi ro'yxatdan o'tganlarni tasdiqlang yoki rad eting.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-none px-4 pt-6 sm:px-6 lg:px-8">
        <nav class="ui-tabs" aria-label="Foydalanuvchilar bo'limlari">
            <a href="{{ route('admin.users.list') }}">Foydalanuvchilar <span class="ui-count">{{ $counts['all'] }}</span></a>
            <a href="{{ route('admin.users.index') }}" @if ($tab === 'pending') aria-current="page" @endif>Tasdiq kutayotganlar <span @class(['ui-count', 'ui-count--alert' => $counts['pending'] > 0])>{{ $counts['pending'] }}</span></a>
            <a href="{{ route('admin.users.index', ['tab' => 'rejected']) }}" @if ($tab === 'rejected') aria-current="page" @endif>Rad etilganlar <span class="ui-count">{{ $counts['rejected'] }}</span></a>
        </nav>

        <div class="grid gap-3">
            @forelse ($users as $user)
                <div class="ui-card grid items-center gap-3.5 md:grid-cols-[48px_minmax(0,1.2fr)_150px_200px_auto]">
                    <span class="ui-avatar h-11 w-11">{{ $initials($user->name) }}</span>
                    <div class="min-w-0">
                        <b class="block truncate text-[14px] font-medium">{{ $user->name }}</b>
                        <span class="block truncate text-xs text-muted">{{ $user->email }} · {{ $user->login }}</span>
                        <span class="block text-xs text-muted">Ariza: {{ $ago($user->created_at) }}@if ($user->phone) · <span class="ui-mono text-xs">{{ $user->phone }}</span>@endif</span>
                    </div>
                    <div class="text-[13px]">
                        <span class="block text-xs text-muted">Bo'lim</span>
                        {{ $user->department?->name ?? '—' }}
                    </div>

                    @if ($tab === 'pending')
                        <form method="POST" action="{{ route('admin.users.update', $user) }}" id="approve-{{ $user->id }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="decision" value="approve">
                            <label class="block text-xs text-muted" for="role-{{ $user->id }}">Rol (siz tanlaysiz)</label>
                            <select id="role-{{ $user->id }}" name="role" class="ui-input py-1.5">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->value }}" @selected($role === \App\Enums\UserRole::Requester)>{{ $role->label() }}</option>
                                @endforeach
                            </select>
                        </form>
                        <div class="flex flex-wrap gap-2 md:justify-end">
                            <button type="submit" form="approve-{{ $user->id }}" class="btn btn-primary">Tasdiqlash</button>
                            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="decision" value="reject">
                                <button type="submit" class="btn btn-secondary !text-red-700">Rad etish</button>
                            </form>
                        </div>
                    @else
                        <div><span class="status-badge status--closed">So'rov rad etilgan</span></div>
                        <div class="flex flex-wrap gap-2 md:justify-end">
                            <a href="{{ route('admin.users.profile', ['user' => $user->id]) }}" class="btn btn-secondary">Profilni ochish</a>
                        </div>
                    @endif
                </div>
            @empty
                <div class="ui-card py-8 text-center text-muted">
                    {{ $tab === 'pending' ? "Tasdiq kutayotgan foydalanuvchi yo'q." : "Rad etilgan so'rov yo'q." }}
                </div>
            @endforelse
        </div>

        @if ($tab === 'pending')
            <p class="ui-note mt-3">Ro'yxatdan o'tishda rol so'ralmaydi: rolni tasdiqlash vaqtida siz tanlaysiz.</p>
        @endif
    </div>
</x-app-layout>

@php
    $statusMeta = [
        'ok' => ['Yaxshi', 'completed'],
        'warn' => ['Ogohlantirish', 'assigned'],
        'fail' => ['Xato', 'new'],
        'info' => ['Ma\'lumot', 'closed'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="pg-crumb">Tizim</div>
                <h2>Tizim holati</h2>
                <p class="pg-sub">Server, baza, fon jarayonlari va integratsiyalar tekshiruvi · {{ $checkedAt->format('d.m.Y H:i:s') }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.system.logs', ['tab' => 'server', 'level' => 'error']) }}" class="btn btn-secondary">Xatolarni ko'rish</a>
                <a href="{{ route('admin.system.health') }}" class="btn btn-primary">Qayta tekshirish</a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-none px-4 pt-6 sm:px-6 lg:px-8">
        <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
            @foreach (['fail', 'warn', 'ok', 'info'] as $key)
                <div class="ui-card">
                    <span class="dash-k__label">{{ $statusMeta[$key][0] }}</span>
                    <span class="dash-k__value">{{ $summary[$key] ?? 0 }}</span>
                    <span class="status-badge status--{{ $statusMeta[$key][1] }}">{{ $key === 'fail' ? 'darhol tuzating' : ($key === 'warn' ? "e'tibor bering" : ($key === 'ok' ? 'joyida' : 'tekshiruvsiz')) }}</span>
                </div>
            @endforeach
        </div>

        <div class="grid gap-3 xl:grid-cols-2">
            @foreach ($groups as $group => $checks)
                <section class="ui-card overflow-x-auto !p-0">
                    <h3 class="ui-card__title border-b border-line px-4 py-3">{{ $group }}</h3>
                    <table class="ui-table">
                        <tbody>
                            @foreach ($checks as $check)
                                <tr>
                                    <td class="w-[42%]">
                                        {{ $check['label'] }}
                                        @if ($check['hint'])
                                            <span class="mt-0.5 block text-xs text-muted">{{ $check['hint'] }}</span>
                                        @endif
                                    </td>
                                    <td class="ui-mono break-all">{{ $check['value'] }}</td>
                                    <td class="w-px whitespace-nowrap text-right"><span class="status-badge status--{{ $statusMeta[$check['status']][1] }}">{{ $statusMeta[$check['status']][0] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>

@php
    $formatSize = fn (int $bytes) => $bytes >= 1048576 ? round($bytes / 1048576, 1).' MB' : max(1, round($bytes / 1024)).' KB';
    $typeOf = fn (string $name) => match (true) {
        str_starts_with($name, 'scheduled-') => ['Avtomatik', 'completed'],
        str_starts_with($name, 'pre-restore-') => ['Tiklashdan oldin', 'assigned'],
        default => ["Qo'lda", 'closed'],
    };
    $healthText = [
        'ok' => ['Ishlayapti', 'completed', "Oxirgi avtomatik zahira o'z vaqtida olingan."],
        'late' => ['Kechikyapti', 'new', "Avtomatik zahira kutilgan vaqtda olinmagan — serverda cron (schedule:run) ishlayotganini tekshiring."],
        'never' => ['Hali olinmagan', 'assigned', "Avtomatik zahira hali bir marta ham olinmagan. Serverda cron sozlanganini tekshiring."],
        'off' => ["O'chirilgan", 'closed', 'Avtomatik zahira o\'chirilgan — faqat qo\'lda olinadi.'],
    ][$health];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="pg-crumb">Sozlamalar</div>
                <h2>Zahira nusxalar</h2>
                <p class="pg-sub">Ma'lumotlar bazasidan zahira olish, tiklash va avtomatik zahira jadvali.</p>
            </div>
            @if ($supported)
                <form method="POST" action="{{ route('admin.backups.store') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Hozir zahira olish</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-none px-4 pt-6 sm:px-6 lg:px-8">
        @foreach (['backup', 'restore'] as $errorKey)
            @error($errorKey)
                <p class="ui-note mb-3 !bg-red-50 !text-red-800">{{ $message }}</p>
            @enderror
        @endforeach

        @unless ($supported)
            <p class="ui-note mb-4 !bg-orange-50 !text-orange-900">
                <span>Joriy baza drayveri <b class="ui-mono">{{ $driver }}</b>. Saytdan zahira olish hozircha faqat SQLite uchun ishlaydi —
                PostgreSQL/MySQL uchun serverda <span class="ui-mono">pg_dump</span> / <span class="ui-mono">mysqldump</span> bilan cron sozlang.</span>
            </p>
        @endunless

        <div class="mb-4 grid grid-cols-2 gap-3 xl:grid-cols-4">
            <div class="ui-card">
                <span class="dash-k__label">Zahiralar soni</span>
                <span class="dash-k__value">{{ $backups->count() }}</span>
                <span class="text-xs text-muted">jami {{ $formatSize($totalSize) }}</span>
            </div>
            <div class="ui-card">
                <span class="dash-k__label">Oxirgi zahira</span>
                <span class="dash-k__value !text-[22px] !leading-[38px]">{{ $backups->first() ? \Illuminate\Support\Carbon::parse($backups->first()['modified_at'])->format('d.m H:i') : '—' }}</span>
                <span class="text-xs text-muted">{{ $backups->first() ? \Illuminate\Support\Carbon::parse($backups->first()['modified_at'])->diffForHumans() : "zahira yo'q" }}</span>
            </div>
            <div class="ui-card">
                <span class="dash-k__label">Avtomatik zahira</span>
                <span class="mt-2 block"><span class="status-badge status--{{ $healthText[1] }}">{{ $healthText[0] }}</span></span>
                <span class="mt-1.5 block text-xs text-muted">{{ $frequencies[$settings['frequency']] }}{{ $settings['frequency'] === 'daily' ? ', '.$settings['daily_at'] : '' }}</span>
            </div>
            <div class="ui-card">
                <span class="dash-k__label">Saqlanadi</span>
                <span class="dash-k__value">{{ $settings['keep'] }}</span>
                <span class="text-xs text-muted">ta oxirgi nusxa</span>
            </div>
        </div>

        <div class="ui-split" style="--split-side: 360px">
            <div class="ui-card overflow-x-auto px-3 pb-3 pt-2">
                <table class="ui-table">
                    <thead>
                        <tr>
                            <th>Fayl</th>
                            <th>Turi</th>
                            <th>Hajmi</th>
                            <th>Sana</th>
                            <th class="w-px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($backups as $backup)
                            @php([$typeLabel, $typeTone] = $typeOf($backup['name']))
                            <tr x-data="{ restore: false }">
                                <td class="ui-mono max-w-[260px] truncate" title="{{ $backup['name'] }}">{{ $backup['name'] }}</td>
                                <td><span class="status-badge status--{{ $typeTone }}">{{ $typeLabel }}</span></td>
                                <td class="ui-mono whitespace-nowrap">{{ $formatSize($backup['size']) }}</td>
                                <td class="ui-mono whitespace-nowrap text-muted">{{ \Illuminate\Support\Carbon::parse($backup['modified_at'])->format('d.m.Y H:i') }}</td>
                                <td class="whitespace-nowrap text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <a href="{{ route('admin.backups.download', $backup['name']) }}" class="btn btn-secondary !px-2.5 !py-1">Yuklab olish</a>
                                        <button type="button" class="btn btn-secondary !px-2.5 !py-1" @click="restore = ! restore" :aria-expanded="restore.toString()">Tiklash</button>
                                        <form method="POST" action="{{ route('admin.backups.destroy', $backup['name']) }}" onsubmit="return confirm('{{ $backup['name'] }} o\'chirilsinmi?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-secondary !px-2.5 !py-1 !text-red-700" aria-label="O'chirish">O'chirish</button>
                                        </form>
                                    </div>
                                    <form x-show="restore" x-cloak method="POST" action="{{ route('admin.backups.restore', $backup['name']) }}"
                                          class="mt-2 grid gap-2 rounded-md border border-red-300 bg-red-50 p-3 text-left whitespace-normal"
                                          onsubmit="return confirm('Joriy baza shu nusxa bilan almashtiriladi. Davom etilsinmi?')">
                                        @csrf
                                        <p class="text-[13px] text-red-800">
                                            Joriy baza <b>{{ $backup['name'] }}</b> holatiga qaytadi. Undan keyingi o'zgarishlar yo'qoladi.
                                            Avval joriy bazaning xavfsizlik nusxasi avtomatik olinadi. Tiklangach, qaytadan kirasiz.
                                        </p>
                                        <label class="ui-field-label !text-red-800" for="restore-password-{{ $loop->index }}">Tasdiqlash uchun parolingiz</label>
                                        <input id="restore-password-{{ $loop->index }}" type="password" name="password" required autocomplete="current-password" class="ui-input">
                                        <button type="submit" class="btn btn-danger">Bazani tiklash</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-10 text-center text-muted">Hozircha zahira nusxa yo'q.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="ui-split__side grid gap-3">
                <form method="POST" action="{{ route('admin.backups.settings') }}" class="ui-card grid gap-3" x-data="{ frequency: @js($settings['frequency']) }">
                    @csrf
                    @method('PUT')
                    <div>
                        <h3 class="ui-card__title">Avtomatik zahira</h3>
                        <p class="ui-card__sub">Server rejalashtiruvchisi (cron) shu jadval bo'yicha zahira oladi</p>
                    </div>
                    <x-ui.toggle name="enabled" :checked="$settings['enabled']" title="Yoqilgan" hint="O'chirilsa, faqat qo'lda zahira olinadi" boxed />
                    <div>
                        <label class="ui-field-label" for="frequency">Chastota</label>
                        <select id="frequency" name="frequency" class="ui-input" x-model="frequency">
                            @foreach ($frequencies as $value => $label)
                                <option value="{{ $value }}" @selected($settings['frequency'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="frequency === 'daily'" x-cloak>
                        <label class="ui-field-label" for="daily_at">Vaqti</label>
                        <input id="daily_at" type="time" name="daily_at" value="{{ $settings['daily_at'] }}" class="ui-input ui-mono">
                        <p class="ui-hint">Kam yuklama vaqtini tanlang, masalan 02:00.</p>
                    </div>
                    <div>
                        <label class="ui-field-label" for="keep">Nechta nusxa saqlansin</label>
                        <input id="keep" type="number" min="1" max="365" name="keep" value="{{ $settings['keep'] }}" class="ui-input ui-mono" required>
                        <p class="ui-hint">Eskilari avtomatik o'chiriladi.</p>
                        <x-input-error :messages="$errors->get('keep')" class="mt-1" />
                    </div>
                    <button type="submit" class="btn btn-primary">Saqlash</button>
                </form>

                <div class="ui-card">
                    <h3 class="ui-card__title mb-2">Holat</h3>
                    <p class="text-[13px]">{{ $healthText[2] }}</p>
                    <dl class="ui-kv mt-3">
                        <dt>Oxirgi avtomatik</dt>
                        <dd class="ui-mono">{{ $lastScheduledAt?->format('d.m.Y H:i') ?? '—' }}</dd>
                        <dt>Papka</dt>
                        <dd class="ui-mono break-all text-xs">{{ $backupPath }}</dd>
                    </dl>
                </div>

                <details class="ui-card text-[13px]">
                    <summary class="cursor-pointer font-semibold">Serverda avtomatik zahira qanday ishlaydi?</summary>
                    <div class="mt-2 grid gap-2 text-muted">
                        <p>Avtomatik zahira Laravel rejalashtiruvchisi orqali olinadi. Serverda cron'ga bitta qator qo'shilgan bo'lishi shart:</p>
                        <pre class="ui-mono overflow-x-auto rounded-md bg-sunken p-2 text-xs text-ink">* * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1</pre>
                        <p>Terminaldan ham ishlaydi: <span class="ui-mono">php artisan db:backup</span>, <span class="ui-mono">php artisan db:backup-list</span>, <span class="ui-mono">php artisan db:restore latest</span>.</p>
                        <p>Muhim: nusxalar shu serverda saqlanadi. Server diski ishdan chiqsa, ular ham yo'qoladi — vaqti-vaqti bilan nusxani yuklab olib, boshqa joyda (O'zbekiston hududidagi serverda) saqlang.</p>
                    </div>
                </details>
            </div>
        </div>
    </div>
</x-app-layout>

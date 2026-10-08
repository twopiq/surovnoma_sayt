{{-- Holat va dashboard ruxsati almashtirgichlari. $user — tahrirlanayotgan foydalanuvchi (null — yaratish). --}}
@php
    $user = $user ?? null;
    $boxed = $boxed ?? false;
    $isPending = $user && $user->is_active && ! $user->approved_at;
    $statusValue = old('status', match (true) {
        $user === null, $user->approved_at && $user->is_active => 'active',
        $isPending => 'pending',
        default => 'inactive',
    });
@endphp

@if ($user)
    @if ($isPending)
        <div>
            <label class="ui-field-label" for="status_{{ $boxed ? 'side' : 'page' }}">Holat</label>
            <select id="status_{{ $boxed ? 'side' : 'page' }}" name="status" class="ui-input">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($statusValue === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="ui-hint">Ro'yxatdan o'tish so'rovi hali tasdiqlanmagan.</p>
        </div>
    @else
        <label @class(['ui-switch-row', 'ui-switch-row--boxed' => $boxed])>
            <span>
                <b class="block text-[14px] font-semibold text-ink">Faol</b>
                <span class="block text-xs text-muted">Tizimga kira oladi</span>
            </span>
            <input type="hidden" name="status" value="inactive">
            <input type="checkbox" name="status" value="active" class="ui-switch" @checked($statusValue === 'active')>
        </label>
    @endif
@endif

<x-ui.toggle name="can_access_app_dashboard" :checked="(bool) old('can_access_app_dashboard', $user?->can_access_app_dashboard)" title="Dashboardga ruxsat" hint="Faqat rahbar roli uchun: ruxsat berilgan vidjetlarni ko'radi" :boxed="$boxed" />

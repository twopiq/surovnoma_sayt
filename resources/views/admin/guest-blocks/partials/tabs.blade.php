<nav class="ui-tabs" aria-label="{{ __('Mehmon himoyasi bo\'limlari') }}">
    <a href="{{ route('admin.guest-blocks.index') }}" @if (request()->routeIs('admin.guest-blocks.index')) aria-current="page" @endif>{{ __('Bloklar') }}</a>
    <a href="{{ route('admin.guest-blocks.settings') }}" @if (request()->routeIs('admin.guest-blocks.settings')) aria-current="page" @endif>{{ __('Sozlamalar') }}</a>
</nav>

<div class="inline-flex rounded-md border border-slate-200 bg-white p-1 text-sm font-semibold shadow-sm">
    <a href="{{ route('admin.guest-blocks.index') }}"
       class="rounded px-4 py-1.5 {{ request()->routeIs('admin.guest-blocks.index') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
        Bloklar
    </a>
    <a href="{{ route('admin.guest-blocks.settings') }}"
       class="rounded px-4 py-1.5 {{ request()->routeIs('admin.guest-blocks.settings') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
        Sozlamalar
    </a>
</div>

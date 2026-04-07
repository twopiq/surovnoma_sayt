@if (session('status'))
    <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
            {{ session('status') }}
        </div>
    </div>
@endif

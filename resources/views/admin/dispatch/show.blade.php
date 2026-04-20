<x-app-layout>
    <x-slot name="header">
        @php
            $backUrl = match (request('source')) {
                'archive' => route('admin.dispatch.archive'),
                'home' => route('app.home'),
                'board' => route('admin.dispatch.index'),
                default => route('admin.dispatch.tickets'),
            };
        @endphp

        <div class="flex items-center gap-3">
            <a href="{{ $backUrl }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Ortga qaytish</a>
            <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Dispetcher kartochkasi</h2>
        </div>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 pt-8 lg:grid-cols-[1.35fr_0.95fr] sm:px-6 lg:px-8">
        <div class="space-y-6">
            @include('partials.ticket-card', ['ticket' => $ticket])
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <h3 class="font-semibold">Tarix</h3>
                <div class="mt-4 space-y-3">
                    @foreach ($ticket->histories as $history)
                        <div class="rounded-xl bg-slate-50 p-3 text-sm">
                            <div class="font-semibold text-slate-700">{{ $history->user?->name ?? 'Tizim' }}</div>
                            <div class="text-slate-600">
                                @if ($history->from_status)
                                    <span class="font-semibold" style="{{ $history->from_status->textStyle() }}">{{ $history->from_status->label() }}</span>
                                @else
                                    <span class="font-semibold text-slate-400">Boshlanish</span>
                                @endif
                                <span class="px-1 text-slate-400">-&gt;</span>
                                <span class="font-semibold" style="{{ $history->to_status->textStyle() }}">{{ $history->to_status->label() }}</span>
                            </div>
                            @if ($history->note)
                                <div class="mt-1 text-slate-500">{{ $history->note }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.dispatch.comment', $ticket) }}" class="rounded-2xl border border-slate-200 bg-white p-6">
                @csrf
                <h3 class="font-semibold">Izoh qoldirish</h3>
                <textarea name="body" rows="3" class="mt-4 block w-full rounded-md border-slate-300 shadow-sm" placeholder="Izoh" required></textarea>
                <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="is_public" value="1">
                    Public izoh
                </label>
                <button class="mt-4 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Saqlash</button>
            </form>

            <form method="POST" action="{{ route('admin.dispatch.assign', $ticket) }}" class="rounded-2xl border border-slate-200 bg-white p-6">
                @csrf
                <h3 class="font-semibold">Taqsimlash</h3>
                <div class="mt-4 space-y-4">
                    <select name="assigned_department_id" class="block w-full rounded-md border-slate-300 shadow-sm">
                        <option value="">Mas'ul bo'lim</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((int) old('assigned_department_id', $ticket->assigned_department_id) === $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                    <select name="assigned_executor_id" class="block w-full rounded-md border-slate-300 shadow-sm">
                        <option value="" @selected(old('assigned_executor_id', $ticket->assigned_executor_id) === null)>Ijrochi tanlanmagan, hamma ko'rsin</option>
                        @foreach ($executors as $executor)
                            @php
                                $workload = $executor->executorWorkloadSummary($ticket->id);
                            @endphp
                            <option value="{{ $executor->id }}" @selected((int) old('assigned_executor_id', $ticket->assigned_executor_id) === $executor->id)>
                                {{ $executor->name }} - {{ $availabilityLabels[$executor->availability_status->value] ?? $executor->availability_status->value }}
                                ({{ $workload['used_units'] }}/{{ $workload['max_units'] }} birlik)
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-400">
                        Agar ijrochi murojaatni bajara olmasa, bu maydonni bo'sh qoldirib saqlang. Shunda murojaat yana barcha ijrochilar ko'ra oladigan umumiy ro'yxatga qaytadi.
                    </p>
                    <select name="category_id" class="block w-full rounded-md border-slate-300 shadow-sm">
                        <option value="">Muammo kategoriyasi</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('category_id', $ticket->category_id) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <select name="priority" class="block w-full rounded-md border-slate-300 shadow-sm">
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority->value }}" @selected(old('priority', $ticket->priority->value) === $priority->value)>{{ $priority->label() }}</option>
                        @endforeach
                    </select>
                    <textarea name="note" rows="3" class="block w-full rounded-md border-slate-300 shadow-sm" placeholder="Izoh">{{ old('note') }}</textarea>
                </div>
                @if (session('overload_warning'))
                    @php($warning = session('overload_warning'))
                    <div class="mt-4 rounded-2xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-900">
                        <div class="font-semibold">Ijrochi yuklamasi limitdan oshadi</div>
                        <p class="mt-1">
                            {{ $warning['executor'] }} uchun joriy yuklama {{ $warning['used_units'] }} birlik.
                            Bu murojaat yana {{ $warning['new_units'] }} birlik qo'shadi va jami {{ $warning['total_units'] }}/{{ $warning['max_units'] }} birlik bo'ladi.
                            Ortiqcha yuklama: {{ $warning['overload_units'] }} birlik.
                        </p>
                        <input type="hidden" name="confirm_overload" value="1">
                        <input type="hidden" name="confirmed_overload_executor_id" value="{{ old('assigned_executor_id') }}">
                        <input type="hidden" name="confirmed_overload_priority" value="{{ old('priority') }}">
                    </div>
                    <button class="mt-4 rounded-full bg-orange-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-700">Baribir tasdiqlash</button>
                @else
                    <button class="mt-4 rounded-full bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Saqlash</button>
                @endif
            </form>

            <form method="POST" action="{{ route('admin.dispatch.reject', $ticket) }}" class="rounded-2xl border border-slate-200 bg-white p-6">
                @csrf
                <h3 class="font-semibold">Rad etish</h3>
                <textarea name="reason" rows="3" class="mt-4 block w-full rounded-md border-slate-300 shadow-sm" placeholder="Sabab" required></textarea>
                <button class="mt-4 rounded-full bg-rose-700 px-4 py-2 text-sm font-semibold text-white">Rad etish</button>
            </form>

            <form method="POST" action="{{ route('admin.dispatch.close', $ticket) }}" class="rounded-2xl border border-slate-200 bg-white p-6">
                @csrf
                <h3 class="font-semibold">Yakuniy yopish</h3>
                <textarea name="note" rows="3" class="mt-4 block w-full rounded-md border-slate-300 shadow-sm" placeholder="Yakuniy izoh"></textarea>
                <button class="mt-4 rounded-full bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Yopish</button>
            </form>
        </div>
    </div>
</x-app-layout>

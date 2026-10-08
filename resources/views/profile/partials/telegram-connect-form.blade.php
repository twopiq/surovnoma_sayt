@php
    $telegramAccounts = $telegramAccounts ?? collect();
    $canLinkMultipleTelegram = $canLinkMultipleTelegram ?? false;
    $isLinked = $telegramAccounts->isNotEmpty();
    $showConnect = $telegramSchemaReady && (! $isLinked || $canLinkMultipleTelegram);
    $telegramIcon = '<path d="M21.94 4.3a1.5 1.5 0 0 0-2.02-1.08L2.9 10.04c-1.17.47-1.1 2.15.1 2.52l4.16 1.29 1.6 5.04c.3.93 1.48 1.2 2.15.48l2.3-2.43 4.23 3.12c.86.63 2.08.16 2.3-.88L21.94 4.3ZM9.6 13.9l8.1-6.2-6.6 7.36-.26 2.9-1.24-4.06Z" />';
@endphp

<section>
    <header>
        <h2 class="text-lg font-bold text-slate-950">Telegram ulanishi</h2>
        <p class="mt-1 text-sm text-slate-600">
            Botni oching va akkauntni bitta bosishda ulang. Bot ichida profilni ko'rish va xabarnomalarni yoqish yoki o'chirish mumkin.
            @if ($canLinkMultipleTelegram)
                Administrator sifatida bir nechta Telegram akkaunt ulashingiz mumkin — xabarlar hammasiga yuboriladi.
            @endif
        </p>
    </header>

    @if (! $telegramSchemaReady)
        <div class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            Telegram ulanishi uchun baza ustunlari hali yaratilmagan. Terminalda <span class="font-mono">php artisan migrate</span> buyrug'ini ishga tushiring.
        </div>
    @endif

    @if (! $telegramBotConfigured)
        <div class="mt-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Telegram bot tokeni sozlanmagan. `.env` fayliga `TELEGRAM_BOT_TOKEN` qo'shing va konfiguratsiya keshini yangilang.
        </div>
    @endif

    @if ($telegramSchemaReady && $isLinked)
        <div class="mt-5">
            <div class="mb-2 flex items-center justify-between gap-2">
                <span class="text-sm font-semibold text-slate-900">Ulangan akkauntlar ({{ $telegramAccounts->count() }})</span>
                <span class="text-xs text-slate-500">Xabarnomalar: {{ $user->telegram_notifications_enabled !== false ? 'yoqilgan' : "o'chirilgan" }}</span>
            </div>
            <ul class="divide-y divide-slate-200 rounded-md border border-slate-200">
                @foreach ($telegramAccounts as $account)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#229ED9] text-[#fff]">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">{!! $telegramIcon !!}</svg>
                            </span>
                            <div class="min-w-0 text-sm">
                                <div class="font-semibold text-slate-900">
                                    {{ $account->username ? '@'.$account->username : 'Username yo\'q' }}
                                    @if ($account->primary)
                                        <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold text-slate-600">Asosiy</span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500">
                                    Chat ID: <span class="font-mono">{{ $account->chat_id }}</span>
                                    · Ulangan: {{ $account->linked_at?->format('d.m.Y H:i') ?? '—' }}
                                </div>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('settings.telegram.disconnect-chat', $account->chat_id) }}" onsubmit="return confirm('Bu Telegram akkaunt uzilsinmi?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger !px-3 !py-1.5">Uzish</button>
                        </form>
                    </li>
                @endforeach
            </ul>

            <p class="mt-3 text-sm text-slate-600">Xabarnomalarni yoqish yoki o'chirish uchun Telegram botdagi tugmalardan foydalaning.</p>

            @if ($telegramAccounts->count() > 1)
                <form method="POST" action="{{ route('settings.telegram.disconnect') }}" class="mt-3" onsubmit="return confirm('Barcha Telegram akkauntlar uzilsinmi?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-semibold text-red-700 hover:underline">Hammasini uzish</button>
                </form>
            @endif
        </div>
    @endif

    @if ($showConnect)
        <div class="mt-5 space-y-4">
            @if (is_string($telegramBotUsername) && $telegramBotUsername !== '')
                @php
                    $telegramStartUrl = 'https://t.me/'.ltrim($telegramBotUsername, '@').'?start='.$user->telegram_link_token;
                @endphp

                <div class="rounded-xl border border-slate-200 bg-cyan-50 p-5">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#229ED9] text-[#fff]">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">{!! $telegramIcon !!}</svg>
                        </div>
                        <div class="min-w-0">
                            <div class="text-base font-bold text-slate-900">{{ $isLinked ? 'Yana Telegram akkaunt ulash' : 'Bot orqali ulash' }}</div>
                            <p class="mt-1 text-sm text-slate-600">
                                @if ($isLinked)
                                    Boshqa Telegram akkauntda (masalan, ikkinchi telefonda) shu havolani oching va «Start» ni bosing. Avval ulanganlar uzilmaydi.
                                @else
                                    Tugmani bosing, Telegram ochiladi. Botda <span class="font-semibold">«Start»</span> tugmasini bossangiz, akkauntingiz avtomatik ulanadi.
                                @endif
                            </p>
                        </div>
                    </div>

                    <a href="{{ $telegramStartUrl }}" target="_blank" rel="noreferrer"
                       class="mt-5 flex w-full items-center justify-center gap-2 rounded-lg bg-[#229ED9] px-5 py-3 text-sm font-bold text-[#fff] transition hover:bg-[#1c8cc2] focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2 sm:w-auto">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">{!! $telegramIcon !!}</svg>
                        Telegramda ulash
                    </a>

                    <div class="mt-4" x-data="{ copied: false, copy() { navigator.clipboard.writeText(@js($telegramStartUrl)).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000) }) } }">
                        <p class="text-xs text-slate-500">Telegram boshqa qurilmada bo'lsa, havolani nusxalab o'sha yerda oching:</p>
                        <button type="button" x-on:click="copy()" class="btn btn-secondary mt-2 !px-3 !py-1.5 text-xs">
                            <span x-text="copied ? 'Nusxalandi' : 'Havolani nusxalash'">Havolani nusxalash</span>
                        </button>
                    </div>
                </div>
            @else
                <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Bot havolasi chiqishi uchun `.env` fayliga `TELEGRAM_BOT_USERNAME` qiymatini qo'shing.
                </div>
            @endif

            <form method="POST" action="{{ route('settings.telegram.regenerate') }}">
                @csrf
                <button type="submit" class="btn btn-secondary">Ulanish kodini yangilash</button>
                @if (session('status') === 'telegram-token-regenerated')
                    <span class="ml-3 text-sm font-medium text-emerald-600">Kod yangilandi.</span>
                @endif
            </form>
        </div>
    @endif

    @if (session('status') === 'telegram-disconnected')
        <p class="mt-4 text-sm font-medium text-emerald-600">Telegram ulanishi uzildi.</p>
    @endif

    @if (session('status') === 'telegram-migration-required')
        <p class="mt-4 text-sm font-medium text-red-600">Avval Telegram migratsiyasini bajaring.</p>
    @endif
</section>

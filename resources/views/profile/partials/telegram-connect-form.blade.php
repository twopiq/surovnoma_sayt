<section>
    <header>
        <h2 class="text-lg font-bold text-slate-950">
            Telegram ulanishi
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            Botni oching va akkauntni bitta bosishda ulang. Bot ichida profilni ko'rish va xabarnomalarni yoqish yoki o'chirish mumkin.
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

    @if ($telegramSchemaReady && $user->telegram_chat_id)
        <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-4">
            <div class="text-sm font-bold text-emerald-800">Telegram ulangan</div>
            <div class="mt-2 space-y-1 text-sm text-emerald-700">
                <div>Chat ID: {{ $user->telegram_chat_id }}</div>
                <div>Username: {{ $user->telegram_username ? '@'.$user->telegram_username : '-' }}</div>
                <div>Xabarnomalar: {{ $user->telegram_notifications_enabled !== false ? 'Yoqilgan' : "O'chirilgan" }}</div>
                <div>Ulangan vaqt: {{ $user->telegram_linked_at?->format('d.m.Y H:i') ?? '-' }}</div>
            </div>
        </div>

        <p class="mt-4 text-sm text-slate-600">
            Xabarnomalarni yoqish yoki o'chirish uchun Telegram botdagi tugmalardan foydalaning.
        </p>

        <form method="POST" action="{{ route('settings.telegram.disconnect') }}" class="mt-5">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                Telegramni uzish
            </button>
        </form>
    @elseif ($telegramSchemaReady)
        <div class="mt-5 space-y-4">
            @if (is_string($telegramBotUsername) && $telegramBotUsername !== '')
                @php
                    $telegramStartUrl = 'https://t.me/'.ltrim($telegramBotUsername, '@').'?start='.$user->telegram_link_token;
                @endphp

                <div class="rounded-xl border border-slate-200 bg-cyan-50 p-5">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#229ED9] text-white shadow-md shadow-sky-500/30">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M21.94 4.3a1.5 1.5 0 0 0-2.02-1.08L2.9 10.04c-1.17.47-1.1 2.15.1 2.52l4.16 1.29 1.6 5.04c.3.93 1.48 1.2 2.15.48l2.3-2.43 4.23 3.12c.86.63 2.08.16 2.3-.88L21.94 4.3ZM9.6 13.9l8.1-6.2-6.6 7.36-.26 2.9-1.24-4.06Z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="text-base font-bold text-slate-900">Bot orqali ulash</div>
                            <p class="mt-1 text-sm text-slate-600">
                                Tugmani bosing, Telegram ochiladi. Botda <span class="font-semibold">«Start»</span> tugmasini bossangiz, akkauntingiz avtomatik ulanadi.
                            </p>
                        </div>
                    </div>

                    <a
                        href="{{ $telegramStartUrl }}"
                        target="_blank"
                        rel="noreferrer"
                        class="group mt-5 flex w-full items-center justify-center gap-2 rounded-lg bg-[#229ED9] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-sky-500/30 transition hover:-translate-y-0.5 hover:bg-[#1c8cc2] hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2 sm:w-auto"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M21.94 4.3a1.5 1.5 0 0 0-2.02-1.08L2.9 10.04c-1.17.47-1.1 2.15.1 2.52l4.16 1.29 1.6 5.04c.3.93 1.48 1.2 2.15.48l2.3-2.43 4.23 3.12c.86.63 2.08.16 2.3-.88L21.94 4.3ZM9.6 13.9l8.1-6.2-6.6 7.36-.26 2.9-1.24-4.06Z" />
                        </svg>
                        Telegramda ulash
                        <svg class="h-4 w-4 transition group-hover:translate-x-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.64l-3.22-3.22a.75.75 0 1 1 1.06-1.06l4.5 4.5a.75.75 0 0 1 0 1.06l-4.5 4.5a.75.75 0 1 1-1.06-1.06l3.22-3.22H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                        </svg>
                    </a>

                    <div
                        class="mt-4"
                        x-data="{ copied: false, copy() { navigator.clipboard.writeText(@js($telegramStartUrl)).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000) }) } }"
                    >
                        <p class="text-xs text-slate-500">Telegram boshqa qurilmada bo'lsa, havolani nusxalab o'sha yerda oching:</p>
                        <button
                            type="button"
                            x-on:click="copy()"
                            class="mt-2 inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-50"
                        >
                            <svg x-show="!copied" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M7 3.5A1.5 1.5 0 0 1 8.5 2h6A1.5 1.5 0 0 1 16 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-6A1.5 1.5 0 0 1 7 12.5v-9Z" />
                                <path d="M4 6.5A1.5 1.5 0 0 1 5.5 5H6v7.5A2.5 2.5 0 0 0 8.5 15H13v.5a1.5 1.5 0 0 1-1.5 1.5h-6A1.5 1.5 0 0 1 4 15.5v-9Z" />
                            </svg>
                            <svg x-show="copied" x-cloak class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" />
                            </svg>
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
                <button type="submit" class="rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Ulanish kodini yangilash
                </button>

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

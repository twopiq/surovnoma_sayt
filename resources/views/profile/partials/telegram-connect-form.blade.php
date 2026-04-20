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
                <a
                    href="https://t.me/{{ ltrim($telegramBotUsername, '@') }}?start={{ $user->telegram_link_token }}"
                    target="_blank"
                    rel="noreferrer"
                    class="inline-flex rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-cyan-800"
                >
                    Telegram botni ochish
                </a>
            @endif

            <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-4">
                <div class="text-sm font-semibold text-slate-800">Bot orqali ulash</div>
                <p class="mt-1 text-sm text-slate-600">
                    Tugma ishlamasa, botga quyidagi buyruqni yuboring:
                </p>
                <div class="mt-3 select-all break-all rounded-md bg-white px-3 py-2 font-mono text-sm text-slate-900 ring-1 ring-slate-200">
                    /start {{ $user->telegram_link_token }}
                </div>
                @if (! is_string($telegramBotUsername) || $telegramBotUsername === '')
                    <p class="mt-3 text-xs text-slate-500">
                        Bot havolasi chiqishi uchun `.env` fayliga `TELEGRAM_BOT_USERNAME` qiymatini qo'shing.
                    </p>
                @endif
            </div>

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

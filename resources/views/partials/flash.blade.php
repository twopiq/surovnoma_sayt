@if (session('status'))
    @php
        $status = session('status');
        $messages = [
            'profile-updated' => "Profil ma'lumotlari saqlandi.",
            'email-updated' => 'Pochta manzili saqlandi.',
            'password-updated' => 'Parol yangilandi.',
            'verification-link-sent' => 'Tasdiqlash xati emailingizga yuborildi.',
            'telegram-token-regenerated' => 'Telegram ulanish kodi yangilandi.',
            'telegram-disconnected' => 'Telegram ulanishi uzildi.',
            'telegram-migration-required' => 'Avval Telegram migratsiyasini bajaring.',
            'notifications-read' => "Bildirishnomalar o'qildi deb belgilandi.",
            'notification-deleted' => "Bildirishnoma o'chirildi.",
            'notifications-cleared' => 'Bildirishnomalar tozalandi.',
        ];
        $message = $messages[$status] ?? $status;
        $isWarning = in_array($status, ['telegram-migration-required'], true);
    @endphp

    <div
        x-data="{ show: true }"
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-2 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-2 opacity-0"
        x-init="setTimeout(() => show = false, 5000)"
        class="pointer-events-none fixed right-4 top-4 z-[230] w-[min(360px,calc(100vw-2rem))]"
    >
        <div class="pointer-events-auto rounded-md border px-4 py-3 shadow-2xl ring-1 ring-black/5 {{ $isWarning ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }}">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $isWarning ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                    @if ($isWarning)
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.515 2.625H3.72c-1.345 0-2.188-1.458-1.515-2.625L8.485 2.495ZM10 6a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                        </svg>
                    @else
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.31a1 1 0 0 1-1.42.002L3.29 9.216a1 1 0 1 1 1.42-1.408l4.04 4.078 6.54-6.59a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </span>

                <div class="min-w-0 flex-1">
                    <div class="text-sm font-semibold">{{ $message }}</div>
                </div>

                <button
                    type="button"
                    class="shrink-0 rounded p-1 opacity-70 transition hover:bg-black/5 hover:opacity-100"
                    aria-label="Xabarni yopish"
                    @click="show = false"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
@endif

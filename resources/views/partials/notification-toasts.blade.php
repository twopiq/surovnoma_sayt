@auth
    <div
        x-data="notificationToasts({
            feedUrl: @js(route('notifications.feed')),
            csrfToken: @js(csrf_token()),
        })"
        x-init="start()"
        class="pointer-events-none fixed right-4 top-4 z-[200] flex w-[min(360px,calc(100vw-2rem))] flex-col gap-3"
        aria-live="polite"
        aria-atomic="true"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <a
                :href="toast.href"
                class="pointer-events-auto block rounded-md bg-slate-950 px-4 py-3 text-white shadow-2xl ring-1 ring-white/10 transition hover:bg-slate-900"
            >
                <div class="flex items-start gap-3">
                    <span class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border border-emerald-400 text-emerald-400">
                        <svg class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.31a1 1 0 0 1-1.42.002L3.29 9.216a1 1 0 1 1 1.42-1.408l4.04 4.078 6.54-6.59a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold" x-text="toast.title"></span>
                        <span class="mt-1 block text-xs text-slate-300" x-text="toast.body"></span>
                        <span class="mt-2 block text-xs font-semibold text-white">Ochish</span>
                    </span>
                    <button
                        type="button"
                        class="shrink-0 rounded p-1 text-slate-400 transition hover:bg-white/10 hover:text-white"
                        aria-label="Bildirishnomani yopish"
                        @click.prevent="dismiss(toast.id)"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </a>
        </template>
    </div>
@endauth

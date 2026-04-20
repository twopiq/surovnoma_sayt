<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-wide text-cyan-700">Sozlamalar</p>
            <h1 class="mt-2 font-['Space_Grotesk'] text-3xl font-bold text-slate-950">Akkaunt sozlamalari</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-600">Pochta, Telegram, parol va akkaunt xavfsizligini shu yerda boshqaring.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
                @include('profile.partials.update-email-form')
            </section>

            <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
                @include('profile.partials.telegram-connect-form')
            </section>

            <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
                @include('profile.partials.update-password-form')
            </section>

            <section class="rounded-md border border-red-100 bg-white p-5 shadow-sm lg:col-span-2">
                @include('profile.partials.delete-user-form')
            </section>
        </div>
    </div>
</x-app-layout>

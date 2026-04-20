<x-app-layout>
    @if (auth()->user()->hasRole(\App\Enums\UserRole::Admin->value))
        <x-slot name="header">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-['Space_Grotesk'] text-2xl font-bold">Dispetcher doskasi</h2>
                    <p class="mt-1 text-sm text-slate-500">Faol murojaatlarning holatlar bo'yicha ko'rinishi</p>
                </div>
            </div>
        </x-slot>

        <div class="mx-auto max-w-7xl space-y-6 px-4 pt-8 sm:px-6 lg:px-8">
            <livewire:admin.dispatch-board />
        </div>
    @else
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        </div>
    @endif
</x-app-layout>

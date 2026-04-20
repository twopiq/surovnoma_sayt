<x-guest-layout>
    <div class="rounded-2xl {{ $isRejected ? 'bg-rose-50' : 'bg-cyan-50' }} p-5">
        <h1 class="font-['Space_Grotesk'] text-2xl font-bold text-slate-900">
            {{ $isRejected ? "So'rov rad etildi" : 'Hisob tekshirilmoqda' }}
        </h1>
        <p class="mt-3 text-sm text-slate-600">
            @if ($isRejected)
                {{ $email ? $email.' uchun' : 'Hisobingiz uchun' }} yuborilgan ro'yxatdan o'tish so'rovi admin tomonidan rad etildi. Ma'lumotlarni tekshirib, qayta ro'yxatdan o'ting.
            @else
                {{ $email ? $email.' uchun' : 'Hisobingiz uchun' }} admin tasdig'i kutilmoqda. Tasdiq berilgach tizimga kirishingiz mumkin bo'ladi.
            @endif
        </p>
    </div>

    <div class="mt-6 flex items-center justify-between">
        @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <input type="hidden" name="redirect" value="login">
                <button type="submit" class="text-sm text-slate-500 underline hover:text-slate-700">Kirish sahifasiga qaytish</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="text-sm text-slate-500 underline hover:text-slate-700">Kirish sahifasiga qaytish</a>
        @endauth

        @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <input type="hidden" name="redirect" value="home">
                <button type="submit" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Bosh sahifa</button>
            </form>
        @else
            <a href="{{ route('home') }}" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Bosh sahifa</a>
        @endauth
    </div>
</x-guest-layout>

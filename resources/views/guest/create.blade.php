<x-public-layout :title="__('Murojaat yuborish')">
    <h1 class="pb-5 pt-4 font-display text-[30px] font-semibold leading-9">{{ __('Murojaat yuborish') }}</h1>

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
        <form method="POST" action="{{ route('guest.store') }}" enctype="multipart/form-data" class="rounded-3xl border border-line bg-surface p-5 sm:p-6">
            @csrf
            <input type="hidden" name="{{ \App\Services\GuestRequestGuard::FORM_TIME_FIELD }}" value="{{ $formStartedToken }}">
            <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
                <label for="{{ \App\Services\GuestRequestGuard::HONEYPOT_FIELD }}">{{ __('Veb-sayt') }}</label>
                <input type="text" id="{{ \App\Services\GuestRequestGuard::HONEYPOT_FIELD }}" name="{{ \App\Services\GuestRequestGuard::HONEYPOT_FIELD }}" tabindex="-1" autocomplete="off">
            </div>

            @if ($errors->any())
                <p class="ui-note mb-4 !bg-red-50 !text-red-800">{{ __('Forma yuborilmadi. Iltimos, belgilangan maydonlarni to\'g\'rilang.') }}</p>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="ui-field-label" for="name">{{ __('F.I.Sh.') }}</label>
                    <input id="name" name="name" value="{{ old('name') }}" required placeholder="{{ __('Insonov Odam Kishi o\'g\'li') }}" class="pub-input">
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-phone-input id="phone_display" name="phone" :value="old('phone')" required hint="" />
                </div>
                <div>
                    <label class="ui-field-label" for="email">{{ __('Email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="institut@ttysi.uz" class="pub-input">
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <label class="ui-field-label" for="department">{{ __('Ishlaydigan bo\'lim') }}</label>
                    <input id="department" name="department" value="{{ old('department') }}" placeholder="{{ __('Tarmoqlarni boshqarish') }}" class="pub-input">
                    <x-input-error :messages="$errors->get('department')" class="mt-1" />
                </div>
            </div>

            <div class="mt-4">
                <label class="ui-field-label" for="job_title">{{ __('Lavozim') }}</label>
                <input id="job_title" name="job_title" value="{{ old('job_title') }}" placeholder="{{ __('Muhandis') }}" class="pub-input">
                <x-input-error :messages="$errors->get('job_title')" class="mt-1" />
            </div>

            <div class="mt-4">
                <label class="ui-field-label" for="category_id">{{ __('Muammo kategoriyasi') }}</label>
                <select id="category_id" name="category_id" required class="pub-input">
                    <option value="">{{ __('Tanlang') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
            </div>

            <div class="mt-4">
                <label class="ui-field-label" for="description">{{ __('Tavsif') }}</label>
                <textarea id="description" name="description" rows="5" required placeholder="{{ __('Muammoni qisqa va aniq yozing: nima, qayerda, qachondan beri?') }}" class="pub-input min-h-[112px]">{{ old('description') }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-1" />
            </div>

            @if (config('guest_limits.max_files') > 0)
                <div class="mt-4">
                    <label class="ui-field-label" for="attachments">
                        {{ __('Fayllar') }}
                        <span class="font-normal text-muted">({{ __('ixtiyoriy, :n tagacha, har biri :size', ['n' => config('guest_limits.max_files'), 'size' => \App\Support\TicketFileUpload::maxFileSizeLabel(config('guest_limits.max_file_size_kb'))]) }})</span>
                    </label>
                    <x-file-upload-input id="attachments" name="attachments[]" :max-files="config('guest_limits.max_files')" :max-size-kb="config('guest_limits.max_file_size_kb')" />
                    <x-input-error :messages="$errors->get('attachments')" class="mt-1" />
                    <x-input-error :messages="$errors->get('attachments.*')" class="mt-1" />
                </div>
            @endif

            @if ($captchaSiteKey)
                <div class="mt-4">
                    <div class="cf-turnstile" data-sitekey="{{ $captchaSiteKey }}"></div>
                    <x-input-error :messages="$errors->get('captcha')" class="mt-1" />
                </div>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            @endif

            <div class="mt-5 flex flex-wrap justify-end gap-2">
                <button type="reset" class="btn btn-secondary btn-lg">{{ __('Tozalash') }}</button>
                <button type="submit" class="btn btn-primary btn-lg">{{ __('Yuborish') }}</button>
            </div>
        </form>

        <aside class="grid gap-4">
            <div class="rounded-3xl border border-line bg-surface p-5">
                <h3 class="mb-2 text-base font-semibold">{{ __('Keyin nima bo\'ladi?') }}</h3>
                <ul class="pub-list">
                    <li><span class="pub-num">1</span>{{ __('Ticket ID va maxfiy kod beriladi') }}</li>
                    <li><span class="pub-num">2</span>{{ __('Operator murojaatni ko\'rib chiqadi') }}</li>
                    <li><span class="pub-num">3</span>{{ __('Ijrochi muammoni hal qiladi') }}</li>
                </ul>
            </div>
            <p class="ui-note"><span><b class="text-accent-strong">{{ __('Kodni saqlang.') }}</b> {{ __('Maxfiy kod keyin qayta ko\'rsatilmaydi.') }}</span></p>
            <div class="rounded-3xl border border-line bg-surface p-5">
                <h3 class="mb-1 text-base font-semibold">{{ __('Avval yuborganmisiz?') }}</h3>
                <p class="mb-3 text-sm text-muted">{{ __('Ticket ID va kod bilan holatni ko\'ring.') }}</p>
                <a href="{{ route('guest.track') }}" class="btn btn-secondary">{{ __('Holatni kuzatish') }}</a>
            </div>
        </aside>
    </div>
</x-public-layout>

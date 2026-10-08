{{-- Login tanlash: F.I.Sh. maydonidan takliflar olinadi, foydalanuvchi bittasini bosadi yoki o'zi yozadi. --}}
@props(['source' => 'name', 'value' => '', 'inputClass' => 'ui-input'])

<div
    x-data="{
        login: @js((string) $value),
        suggestions: [],
        loading: false,
        timer: null,
        touched: @js(filled($value)),
        load(name) {
            clearTimeout(this.timer);
            if (!name || name.trim().length < 2) { this.suggestions = []; return; }
            this.timer = setTimeout(async () => {
                this.loading = true;
                try {
                    const response = await fetch(@js(route('login.suggestions')) + '?name=' + encodeURIComponent(name), { headers: { Accept: 'application/json' } });
                    const data = response.ok ? await response.json() : { suggestions: [] };
                    this.suggestions = data.suggestions || [];
                    if (!this.touched && this.suggestions.length) this.login = this.suggestions[0];
                } finally {
                    this.loading = false;
                }
            }, 350);
        },
        init() {
            const field = document.getElementById(@js($source));
            if (field) {
                field.addEventListener('input', () => this.load(field.value));
                if (field.value) this.load(field.value);
            }
        },
    }"
    {{ $attributes }}
>
    <label class="ui-field-label" for="login">Login</label>
    <input id="login" name="login" type="text" x-model="login" @input="touched = true"
           maxlength="{{ \App\Support\LoginSuggester::MAX_LENGTH }}" autocomplete="username" spellcheck="false"
           pattern="[a-z0-9]+([._][a-z0-9]+)*" placeholder="masalan: behzod.qurbonov"
           class="{{ $inputClass }} font-mono">

    <div class="mt-2 flex flex-wrap items-center gap-1.5" x-show="suggestions.length" x-cloak>
        <span class="text-xs text-muted">Takliflar:</span>
        <template x-for="item in suggestions" :key="item">
            <button type="button" class="ui-chip !px-2.5 !py-0.5 font-mono text-xs"
                    :aria-current="login === item ? 'page' : null"
                    @click="login = item; touched = true" x-text="item"></button>
        </template>
    </div>
    <p class="ui-hint">
        F.I. (familiya va ism) dan tuziladi, {{ \App\Support\LoginSuggester::MAX_LENGTH }} belgigacha: kichik lotin harflari, raqam, nuqta yoki pastki chiziq.
        <span x-show="loading" x-cloak>Takliflar yuklanmoqda…</span>
    </p>
    <x-input-error :messages="$errors->get('login')" class="mt-1" />
</div>

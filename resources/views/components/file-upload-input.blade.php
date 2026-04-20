@props([
    'id',
    'name',
    'required' => false,
    'disabled' => false,
])

@php
    use App\Support\TicketFileUpload;

    $maxFiles = TicketFileUpload::MAX_FILES;
    $maxFileSizeMb = TicketFileUpload::maxFileSizeMb();
    $maxFileSizeBytes = TicketFileUpload::MAX_FILE_SIZE_KB * 1024;
@endphp

<div
    x-data="{
        error: '',
        checkFiles(event) {
            this.error = '';
            const files = Array.from(event.target.files || []);

            if (files.length > {{ $maxFiles }}) {
                this.error = @js(TicketFileUpload::tooManyFilesMessage());
                event.target.value = '';
                return;
            }

            if (files.some((file) => file.size > {{ $maxFileSizeBytes }})) {
                this.error = @js(TicketFileUpload::fileTooLargeMessage());
                event.target.value = '';
            }
        },
    }"
>
    <input
        id="{{ $id }}"
        type="file"
        name="{{ $name }}"
        multiple
        accept="{{ TicketFileUpload::acceptAttribute() }}"
        x-on:change="checkFiles($event)"
        @if ($required) required @endif
        @if ($disabled) disabled @endif
        {{ $attributes->merge(['class' => 'block w-full text-sm text-slate-500']) }}
    />
    <p class="mt-2 text-xs text-slate-400">
        Ko'pi bilan {{ $maxFiles }} ta fayl. Har biri {{ $maxFileSizeMb }} MB dan oshmasin. Ruxsat: {{ TicketFileUpload::allowedFormatsLabel() }}.
    </p>
    <p x-show="error" x-text="error" class="mt-2 text-sm text-red-600"></p>
</div>

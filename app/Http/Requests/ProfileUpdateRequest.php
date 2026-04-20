<?php

namespace App\Http\Requests;

use App\Enums\AvailabilityStatus;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^\S+(?:\s+\S+)+$/'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'regex:/^\+998 \d{2} \d{3} \d{2} \d{2}$/'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'availability_status' => ['sometimes', 'required', Rule::enum(AvailabilityStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => "F.I.Sh. kamida ism va familiyadan iborat bo'lishi kerak.",
            'phone.regex' => "Telefon raqami +998 99 999 99 99 ko'rinishida bo'lishi kerak.",
        ];
    }
}

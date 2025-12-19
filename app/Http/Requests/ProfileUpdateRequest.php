<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\-_\.]+$/'
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'Name contains invalid characters. Only letters, spaces, hyphens, underscores, and periods are allowed.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize inputs to remove dangerous characters
        $dangerousChars = ['<', '>', '"', "'", '&', '|', ';', '`', '$', '(', ')', '{', '}', '[', ']', '\\', '/', '..'];

        if ($this->has('name') && is_string($this->input('name'))) {
            $sanitized = str_replace($dangerousChars, '', $this->input('name'));
            $this->merge(['name' => $sanitized]);
        }
    }
}

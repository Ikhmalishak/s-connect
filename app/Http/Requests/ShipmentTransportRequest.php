<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShipmentTransportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'transport_type' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\s\-_]+$/'
            ],
            'transport_number' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_]+$/'
            ],
            'sku_number' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_]+$/'
            ],
            'model_project' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\s\-_]+$/'
            ],
            'forwarder' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\s\-_]+$/'
            ],
            'country' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]+$/'
            ],
            'work_order' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_]+$/'
            ],
            'hauler' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\s\-_]+$/'
            ],
            'high_security_seal' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_]*$/'
            ],
            'gps' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_]*$/'
            ],
            'fork_seal' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_]*$/'
            ],
            'temporary_seal' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_]*$/'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'transport_type.regex' => 'Transport type contains invalid characters. Only letters, numbers, spaces, hyphens, and underscores are allowed.',
            'transport_number.regex' => 'Transport number contains invalid characters. Only letters, numbers, hyphens, and underscores are allowed.',
            'sku_number.regex' => 'SKU number contains invalid characters. Only letters, numbers, hyphens, and underscores are allowed.',
            'model_project.regex' => 'Model project contains invalid characters. Only letters, numbers, spaces, hyphens, and underscores are allowed.',
            'forwarder.regex' => 'Forwarder contains invalid characters. Only letters, numbers, spaces, hyphens, and underscores are allowed.',
            'country.regex' => 'Country contains invalid characters. Only letters and spaces are allowed.',
            'work_order.regex' => 'Work order contains invalid characters. Only letters, numbers, hyphens, and underscores are allowed.',
            'hauler.regex' => 'Hauler contains invalid characters. Only letters, numbers, spaces, hyphens, and underscores are allowed.',
            'high_security_seal.regex' => 'High security seal contains invalid characters. Only letters, numbers, hyphens, and underscores are allowed.',
            'gps.regex' => 'GPS contains invalid characters. Only letters, numbers, hyphens, and underscores are allowed.',
            'fork_seal.regex' => 'Fork seal contains invalid characters. Only letters, numbers, hyphens, and underscores are allowed.',
            'temporary_seal.regex' => 'Temporary seal contains invalid characters. Only letters, numbers, hyphens, and underscores are allowed.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize inputs to remove dangerous characters
        $dangerousChars = ['<', '>', '"', "'", '&', '|', ';', '`', '$', '(', ')', '{', '}', '[', ']', '\\', '/', '..'];

        $fieldsToSanitize = [
            'transport_type', 'transport_number', 'sku_number', 'model_project',
            'forwarder', 'country', 'work_order', 'hauler', 'high_security_seal',
            'gps', 'fork_seal', 'temporary_seal'
        ];

        foreach ($fieldsToSanitize as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $sanitized = str_replace($dangerousChars, '', $this->input($field));
                $this->merge([$field => $sanitized]);
            }
        }
    }
}

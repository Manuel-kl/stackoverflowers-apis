<?php

namespace App\Http\Requests\Domain;

use Illuminate\Foundation\Http\FormRequest;

class DomainSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'domain_name' => [
                'required',
                'regex:/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.(co|or|ac|sc|go|me|ne|mobi|info))?\.ke$/i',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'domain_name.required' => 'The domain name is required.',
            'domain_name.regex' => 'The domain name must be a valid .ke domain.',
        ];
    }
}

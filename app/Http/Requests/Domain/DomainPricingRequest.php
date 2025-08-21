<?php

namespace App\Http\Requests\Domain;

use Illuminate\Foundation\Http\FormRequest;

class DomainPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'domain.required' => 'Domain parameter is required.',
            'domain.string' => 'Domain must be a string.',
            'domain.max' => 'Domain cannot exceed 255 characters.',
        ];
    }
}

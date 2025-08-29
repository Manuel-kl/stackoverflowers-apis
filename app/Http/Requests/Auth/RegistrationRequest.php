<?php

namespace App\Http\Requests\Auth;

use App\Helpers\Helpers;
use Illuminate\Foundation\Http\FormRequest;

class RegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if ($this->phone_number) {
            $formattedPhone = Helpers::formatPhoneNumber($this->phone_number);
            $this->merge([
                'phone_number' => $formattedPhone,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15|unique:users,phone_number',
            'password' => 'required|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.required' => 'Please enter a valid phone number.',
        ];
    }
}

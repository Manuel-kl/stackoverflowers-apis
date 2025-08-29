<?php

namespace App\Http\Requests\Payment;

use App\Helpers\Helpers;
use Illuminate\Foundation\Http\FormRequest;

class OrderPaymentRequest extends FormRequest
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
        $rules = [
            'payment_method' => 'required|string|in:mpesa,card',
        ];

        if ($this->input('payment_method') === 'mpesa') {
            $rules['phone_number'] = 'required|string';
        } elseif ($this->input('payment_method') === 'card') {
            $rules['card_number'] = 'required|string';
            $rules['cvv'] = 'required|string';
            $rules['expiry_month'] = 'required|string';
            $rules['expiry_year'] = 'required|string';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'Please select a payment method (mpesa or card).',
            'payment_method.in' => 'Payment method must be either mpesa or card.',
            'phone_number.required' => 'Please enter a valid phone number.',
            'phone_number.string' => 'Phone number must be a valid text format.',
            'card_number.required' => 'Card number is required for card payments.',
            'card_number.string' => 'Card number must be a valid text format.',
            'cvv.required' => 'CVV is required for card payments.',
            'cvv.string' => 'CVV must be a valid text format.',
            'expiry_month.required' => 'Expiry month is required for card payments.',
            'expiry_month.string' => 'Expiry month must be a valid text format.',
            'expiry_year.required' => 'Expiry year is required for card payments.',
            'expiry_year.string' => 'Expiry year must be a valid text format.',
        ];
    }
}

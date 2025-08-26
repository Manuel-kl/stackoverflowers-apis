<?php

namespace App\Http\Requests\Domain;

use App\Rules\ValidKeDomain;
use Illuminate\Foundation\Http\FormRequest;

class DomainLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'searchTerm' => ['required', 'string', new ValidKeDomain],
        ];
    }
}

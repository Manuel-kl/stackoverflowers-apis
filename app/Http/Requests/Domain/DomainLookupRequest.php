<?php

namespace App\Http\Requests\Domain;

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
            'searchTerm' => ['required', 'string'],
            'punyCodeSearchTerm' => ['nullable', 'string'],
            'tldsToInclude' => ['nullable', 'array'],
            'isIdnDomain' => ['nullable', 'boolean'],
            'premiumEnabled' => ['nullable', 'boolean'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();
        $data['punyCodeSearchTerm'] = $data['punyCodeSearchTerm'] ?? $data['searchTerm'];

        // Extract domain name and TLD
        $searchTerm = $data['searchTerm'];
        $tld = '';
        $name = $searchTerm;
        if (preg_match('/^([^.]+)\.(.+)$/', $searchTerm, $matches)) {
            $name = $matches[1];
            $tld = $matches[2];
        }
        $data['searchTerm'] = $name;
        $data['tldsToInclude'] = [$tld];

        $input = request()->all();
        $data['isIdnDomain'] = filter_var($input['isIdnDomain'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['premiumEnabled'] = filter_var($input['premiumEnabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $key ? ($data[$key] ?? $default) : $data;
    }
}

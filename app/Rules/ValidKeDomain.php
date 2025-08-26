<?php

namespace App\Rules;

use App\Models\KeDomainPricing;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidKeDomain implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        $rawInput = trim($value);
        if ($rawInput === '') {
            $fail('The :attribute cannot be empty.');

            return;
        }

        $validTlds = KeDomainPricing::getActiveTlds();
        $validTlds = array_values(array_unique(array_map(function ($tld) {
            $tld = strtolower(trim($tld));
            if ($tld === '') {
                return $tld;
            }

            return str_starts_with($tld, '.') ? $tld : '.'.$tld;
        }, $validTlds)));

        usort($validTlds, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        $domainName = strtolower($rawInput);

        $isValid = false;
        $matchedTld = '';
        foreach ($validTlds as $tld) {
            if (str_ends_with($domainName, $tld)) {
                $isValid = true;
                $matchedTld = $tld;
                break;
            }
        }

        if (!$isValid) {
            $fail('Invalid domain. Allowed extensions: '.implode(', ', $validTlds));

            return;
        }

        if (strlen($domainName) > 253) {
            $fail('Domain name cannot exceed 253 characters.');

            return;
        }

        $namePart = substr($domainName, 0, -strlen($matchedTld));
        $namePart = rtrim($namePart, '.');

        if ($namePart === '') {
            $fail('The domain name part cannot be empty.');

            return;
        }

        if (str_contains($namePart, '.')) {
            $fail('Invalid domain. Allowed extensions: '.implode(', ', $validTlds));

            return;
        }

        if (strlen($namePart) > 63) {
            $fail('The domain label cannot exceed 63 characters.');

            return;
        }

        if (!preg_match('/^[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?$/', $namePart)) {
            $fail('Domain name can only contain letters, numbers, and hyphens. Labels cannot start or end with hyphens.');

            return;
        }
    }
}

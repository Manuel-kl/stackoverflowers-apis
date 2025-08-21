<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeDomainPricing extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registration_price' => 'decimal:2',
            'renewal_price' => 'decimal:2',
            'transfer_price' => 'decimal:2',
            'grace_fee' => 'decimal:2',
            'redemption_fee' => 'decimal:2',
            'years' => 'array',
            'is_active' => 'boolean',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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

    public static function getActiveTlds(): array
    {
        return Cache::rememberForever('valid_ke_tlds', function () {
            return static::where('is_active', true)
                ->pluck('tld')
                ->toArray();
        });
    }

    public static function clearTldCache(): void
    {
        Cache::forget('valid_ke_tlds');
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function () {
            static::clearTldCache();
        });

        static::updated(function () {
            static::clearTldCache();
        });

        static::deleted(function () {
            static::clearTldCache();
        });
    }
}

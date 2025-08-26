<?php

namespace App\Models;

use App\Enums\TaxStatusEnum;
use App\Enums\TaxTypeEnum;
use Illuminate\Database\Eloquent\Model;

class PaymentTaxes extends Model
{
    protected $casts = [
        'status' => TaxStatusEnum::class,
        'type' => TaxTypeEnum::class,
        'value' => 'decimal:2',
    ];
}

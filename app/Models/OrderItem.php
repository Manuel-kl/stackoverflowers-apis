<?php

namespace App\Models;

use App\Enums\OrderItemStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $casts = [
        'status' => OrderItemStatusEnum::class,
        'price' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

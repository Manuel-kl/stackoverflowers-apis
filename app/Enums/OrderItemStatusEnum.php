<?php

namespace App\Enums;

enum OrderItemStatusEnum: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::EXPIRED => 'Expired',
            self::PAID => 'Paid',
            self::CANCELLED => 'Cancelled',
        };
    }
}

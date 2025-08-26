<?php

namespace App\Enums;

enum AdminStatusEnum: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case DISABLED = 'disabled';
}

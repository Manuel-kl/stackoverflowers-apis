<?php

namespace App\Enums;

enum PaymentStatusEnum: string
{
    case ABANDONED = 'abandoned';
    case FAILED = 'failed';
    case ONGOING = 'ongoing';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case QUEUED = 'queued';
    case REVERSED = 'reversed';
    case SUCCESSFUL = 'success';
    case OPEN_URL = 'open_url';
}

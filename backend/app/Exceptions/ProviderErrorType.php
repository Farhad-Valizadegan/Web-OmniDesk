<?php

namespace App\Exceptions;

enum ProviderErrorType: string
{
    case Authorization = 'authorization';
    case Permission = 'permission';
    case RateLimit = 'rate_limit';
    case Temporary = 'temporary';
    case InvalidResponse = 'invalid_response';

    public function isRetryable(): bool
    {
        return match ($this) {
            self::RateLimit, self::Temporary => true,
            default => false,
        };
    }
}

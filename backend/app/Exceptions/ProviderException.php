<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class ProviderException extends RuntimeException
{
    public function __construct(
        public readonly string $provider,
        public readonly ProviderErrorType $type,
        string $safeMessage,
        ?Throwable $previous = null,
        public readonly ?string $operation = null,
    ) {
        parent::__construct($safeMessage, previous: $previous);
    }

    public function errorCode(): string
    {
        return $this->provider.'_'.$this->type->value;
    }

    public function httpStatus(): int
    {
        return match ($this->type) {
            ProviderErrorType::Authorization => 409,
            ProviderErrorType::Permission => 403,
            ProviderErrorType::RateLimit => 429,
            ProviderErrorType::Temporary => 503,
            ProviderErrorType::InvalidResponse => 502,
        };
    }

    public function isRetryable(): bool
    {
        return $this->type->isRetryable();
    }
}

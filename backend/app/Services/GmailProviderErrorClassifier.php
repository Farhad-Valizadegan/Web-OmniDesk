<?php

namespace App\Services;

use App\Exceptions\ProviderErrorType;
use App\Exceptions\ProviderException;
use Throwable;

class GmailProviderErrorClassifier
{
    public function classify(Throwable $exception): ProviderException
    {
        if ($exception instanceof ProviderException) {
            return $exception;
        }

        $status = (int) $exception->getCode();
        $message = strtolower($exception->getMessage());

        $type = match (true) {
            $status === 401 => ProviderErrorType::Authorization,
            $status === 429 => ProviderErrorType::RateLimit,
            $status === 403 && $this->isRateLimitMessage($message) => ProviderErrorType::RateLimit,
            $status === 403 => ProviderErrorType::Permission,
            $status === 0, $status === 408, $status >= 500 => ProviderErrorType::Temporary,
            default => ProviderErrorType::InvalidResponse,
        };

        return new ProviderException(
            'gmail',
            $type,
            $this->safeMessage($type),
            $exception,
        );
    }

    private function isRateLimitMessage(string $message): bool
    {
        return str_contains($message, 'ratelimitexceeded')
            || str_contains($message, 'userratelimitexceeded')
            || str_contains($message, 'quotaexceeded')
            || str_contains($message, 'rate limit');
    }

    private function safeMessage(ProviderErrorType $type): string
    {
        return match ($type) {
            ProviderErrorType::Authorization => 'Gmail authorization is invalid or expired.',
            ProviderErrorType::Permission => 'Gmail permission is insufficient.',
            ProviderErrorType::RateLimit => 'Gmail request limit was reached.',
            ProviderErrorType::Temporary => 'Gmail is temporarily unavailable.',
            ProviderErrorType::InvalidResponse => 'Gmail returned an invalid response.',
        };
    }
}

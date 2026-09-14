<?php

namespace Tests\Unit;

use App\Exceptions\ProviderErrorType;
use App\Exceptions\ProviderException;
use PHPUnit\Framework\TestCase;

class ProviderExceptionTest extends TestCase
{
    public function test_provider_error_codes_are_stable(): void
    {
        $exception = new ProviderException(
            'gmail',
            ProviderErrorType::Permission,
            'Gmail permission is insufficient.'
        );

        $this->assertSame(
            'gmail_permission',
            $exception->errorCode()
        );

        $this->assertFalse($exception->isRetryable());
    }

    public function test_only_temporary_errors_are_retryable(): void
    {
        $this->assertTrue(
            ProviderErrorType::RateLimit->isRetryable()
        );

        $this->assertTrue(
            ProviderErrorType::Temporary->isRetryable()
        );

        $this->assertFalse(
            ProviderErrorType::Authorization->isRetryable()
        );

        $this->assertFalse(
            ProviderErrorType::InvalidResponse->isRetryable()
        );
    }
}

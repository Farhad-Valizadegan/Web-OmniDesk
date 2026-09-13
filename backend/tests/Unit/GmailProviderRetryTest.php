<?php

namespace Tests\Unit;

use App\Services\GmailMessageSyncService;
use App\Services\GoogleOAuthService;
use RuntimeException;
use Tests\TestCase;

class GmailProviderRetryTest extends TestCase
{
    public function test_transient_provider_failure_is_retried(): void
    {
        $service = new class(new GoogleOAuthService) extends GmailMessageSyncService
        {
            public function callProvider(callable $operation): mixed
            {
                return $this->providerCall($operation);
            }
        };

        $attempts = 0;

        $result = $service->callProvider(function () use (&$attempts): string {
            $attempts++;

            if ($attempts < 3) {
                throw new RuntimeException('temporary failure', 503);
            }

            return 'completed';
        });

        $this->assertSame('completed', $result);
        $this->assertSame(3, $attempts);
    }

    public function test_non_transient_provider_failure_is_not_retried(): void
    {
        $service = new class(new GoogleOAuthService) extends GmailMessageSyncService
        {
            public function callProvider(callable $operation): mixed
            {
                return $this->providerCall($operation);
            }
        };

        $attempts = 0;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid credentials');

        try {
            $service->callProvider(function () use (&$attempts): never {
                $attempts++;
                throw new RuntimeException('invalid credentials', 401);
            });
        } finally {
            $this->assertSame(1, $attempts);
        }
    }
}

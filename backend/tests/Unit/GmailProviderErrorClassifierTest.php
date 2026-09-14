<?php

namespace Tests\Unit;

use App\Exceptions\ProviderErrorType;
use App\Services\GmailProviderErrorClassifier;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class GmailProviderErrorClassifierTest extends TestCase
{
    public function test_gmail_failures_are_classified(): void
    {
        $classifier = new GmailProviderErrorClassifier;

        $cases = [
            [401, 'invalid credentials', ProviderErrorType::Authorization],
            [403, 'permission denied', ProviderErrorType::Permission],
            [403, 'rateLimitExceeded', ProviderErrorType::RateLimit],
            [429, 'too many requests', ProviderErrorType::RateLimit],
            [503, 'service unavailable', ProviderErrorType::Temporary],
            [400, 'malformed response', ProviderErrorType::InvalidResponse],
        ];

        foreach ($cases as [$status, $message, $expected]) {
            $classified = $classifier->classify(
                new RuntimeException($message, $status)
            );

            $this->assertSame($expected, $classified->type);
            $this->assertSame('gmail_'.$expected->value, $classified->errorCode());
        }
    }

    public function test_provider_details_are_not_exposed(): void
    {
        $classified = (new GmailProviderErrorClassifier)->classify(
            new RuntimeException('Sensitive provider response', 503)
        );

        $this->assertSame(
            'Gmail is temporarily unavailable.',
            $classified->getMessage()
        );

        $this->assertStringNotContainsString(
            'Sensitive',
            $classified->getMessage()
        );
    }
}

<?php

namespace Tests\Unit;

use App\Contracts\MessageProviderConnector;
use App\Services\GmailMessageSyncService;
use App\Services\GmailProviderErrorClassifier;
use App\Services\GoogleOAuthService;
use PHPUnit\Framework\TestCase;

class GmailConnectorContractTest extends TestCase
{
    public function test_gmail_service_implements_message_provider_contract(): void
    {
        $connector = new GmailMessageSyncService(
            new GoogleOAuthService(new GmailProviderErrorClassifier)
        );

        $this->assertInstanceOf(
            MessageProviderConnector::class,
            $connector
        );

        $this->assertSame('gmail', $connector->provider());
    }
}

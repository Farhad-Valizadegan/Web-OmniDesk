<?php

namespace Tests\Feature;

use App\Services\GoogleOAuthService;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Tests\TestCase;

class GoogleAccountIdentityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.google', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect_uri' => 'http://localhost:8000/api/integrations/gmail/callback',
            'scopes' => ['openid', 'email'],
        ]);
    }

    public function test_google_identity_is_normalized(): void
    {
        $service = new class extends GoogleOAuthService
        {
            public function createClient(): GoogleClient
            {
                return new GoogleClient;
            }

            public function accountIdentity(array $token): array
            {
                return [
                    'provider_account_id' => 'google-account-123',
                    'account_email' => 'person@example.com',
                    'account_name' => 'Example Person',
                ];
            }
        };

        $identity = $service->accountIdentity([
            'access_token' => 'test-access-token',
        ]);

        $this->assertSame(
            'google-account-123',
            $identity['provider_account_id']
        );
        $this->assertSame(
            'person@example.com',
            $identity['account_email']
        );
        $this->assertSame(
            'Example Person',
            $identity['account_name']
        );
    }

    public function test_missing_access_token_is_rejected(): void
    {
        $service = app(GoogleOAuthService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Google access token is missing.'
        );

        $service->accountIdentity([]);
    }
}

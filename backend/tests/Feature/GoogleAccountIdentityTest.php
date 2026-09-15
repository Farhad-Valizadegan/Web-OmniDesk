<?php

namespace Tests\Feature;

use App\Exceptions\ProviderErrorType;
use App\Exceptions\ProviderException;
use App\Services\GmailProviderErrorClassifier;
use App\Services\GoogleOAuthService;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Config;
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
        $service = new class(new GmailProviderErrorClassifier) extends GoogleOAuthService
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

    public function test_missing_access_token_is_classified(): void
    {
        $service = app(GoogleOAuthService::class);

        try {
            $service->accountIdentity([]);

            $this->fail('Expected a provider exception.');
        } catch (ProviderException $exception) {
            $this->assertSame(
                ProviderErrorType::InvalidResponse,
                $exception->type
            );

            $this->assertSame(
                'gmail_invalid_response',
                $exception->errorCode()
            );
        }
    }
}

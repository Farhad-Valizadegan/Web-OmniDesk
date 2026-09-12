<?php

namespace Tests\Feature;

use App\Services\GoogleOAuthService;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Tests\TestCase;

class GoogleTokenExchangeTest extends TestCase
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

    public function test_authorization_code_is_exchanged_for_access_token(): void
    {
        $service = new class extends GoogleOAuthService
        {
            public function createClient(): GoogleClient
            {
                return new class extends GoogleClient
                {
                    public function fetchAccessTokenWithAuthCode(
                        $code,
                        $codeVerifier = null
                    ): array {
                        return [
                            'access_token' => 'test-access-token',
                            'refresh_token' => 'test-refresh-token',
                            'expires_in' => 3600,
                            'scope' => 'openid email',
                            'token_type' => 'Bearer',
                        ];
                    }
                };
            }
        };

        $token = $service->exchangeCode('test-authorization-code');

        $this->assertSame('test-access-token', $token['access_token']);
        $this->assertSame('test-refresh-token', $token['refresh_token']);
        $this->assertSame(3600, $token['expires_in']);
    }

    public function test_empty_authorization_code_is_rejected(): void
    {
        $service = app(GoogleOAuthService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'OAuth authorization code cannot be empty.'
        );

        $service->exchangeCode('');
    }

    public function test_failed_token_exchange_is_rejected(): void
    {
        $service = new class extends GoogleOAuthService
        {
            public function createClient(): GoogleClient
            {
                return new class extends GoogleClient
                {
                    public function fetchAccessTokenWithAuthCode(
                        $code,
                        $codeVerifier = null
                    ): array {
                        return [
                            'error' => 'invalid_grant',
                            'error_description' => 'Sensitive provider detail',
                        ];
                    }
                };
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Google authorization token exchange failed.'
        );

        $service->exchangeCode('invalid-authorization-code');
    }
}

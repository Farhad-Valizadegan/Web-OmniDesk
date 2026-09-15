<?php

namespace Tests\Feature;

use App\Exceptions\ProviderErrorType;
use App\Exceptions\ProviderException;
use App\Services\GmailProviderErrorClassifier;
use App\Services\GoogleOAuthService;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Config;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class GoogleOAuthServiceTest extends TestCase
{
    public function test_google_authorization_url_contains_expected_parameters(): void
    {
        Config::set('services.google', [
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'test-client-secret',
            'redirect_uri' => 'http://localhost:8000/api/integrations/gmail/callback',
            'scopes' => [
                'openid',
                'email',
                'profile',
                'https://www.googleapis.com/auth/gmail.readonly',
            ],
        ]);

        $url = app(GoogleOAuthService::class)
            ->authorizationUrl('test-oauth-state');

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame(
            'test-client-id.apps.googleusercontent.com',
            $query['client_id']
        );
        $this->assertSame(
            'http://localhost:8000/api/integrations/gmail/callback',
            $query['redirect_uri']
        );
        $this->assertSame('test-oauth-state', $query['state']);
        $this->assertSame('offline', $query['access_type']);
        $this->assertSame('consent', $query['prompt']);
        $this->assertStringContainsString('gmail.readonly', $query['scope']);
        $this->assertStringNotContainsString('test-client-secret', $url);
    }

    public function test_empty_oauth_state_is_rejected(): void
    {
        Config::set('services.google', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect_uri' => 'http://localhost:8000/api/integrations/gmail/callback',
            'scopes' => ['openid', 'email'],
        ]);

        $this->expectExceptionMessage('OAuth state cannot be empty.');

        app(GoogleOAuthService::class)->authorizationUrl('');
    }

    public function test_missing_google_configuration_is_rejected(): void
    {
        Config::set('services.google', [
            'client_id' => null,
            'client_secret' => null,
            'redirect_uri' => null,
            'scopes' => [],
        ]);

        $this->expectExceptionMessage(
            'Google OAuth client ID is not configured.'
        );

        app(GoogleOAuthService::class)->authorizationUrl('test-state');
    }

    public function test_service_resolves_with_error_classifier_dependency(): void
    {
        $service = $this->app->make(GoogleOAuthService::class);

        $this->assertInstanceOf(GoogleOAuthService::class, $service);
    }

    public function test_google_api_exception_is_classified_during_token_exchange(): void
    {
        $client = Mockery::mock(GoogleClient::class);
        $client->shouldReceive('fetchAccessTokenWithAuthCode')
            ->once()
            ->with('test-code')
            ->andThrow(new RuntimeException('Unauthorized', 401));

        $service = $this->serviceUsingClient($client);

        try {
            $service->exchangeCode('test-code');
            $this->fail('Expected a ProviderException.');
        } catch (ProviderException $exception) {
            $this->assertSame(ProviderErrorType::Authorization, $exception->type);
            $this->assertSame('gmail_authorization', $exception->errorCode());
            $this->assertSame(
                'Gmail authorization is invalid or expired.',
                $exception->getMessage(),
            );
        }
    }

    public function test_invalid_token_exchange_response_is_rejected(): void
    {
        $client = Mockery::mock(GoogleClient::class);
        $client->shouldReceive('fetchAccessTokenWithAuthCode')
            ->once()
            ->with('test-code')
            ->andReturn([
                'error' => 'invalid_grant',
                'error_description' => 'Bad authorization code.',
            ]);

        $service = $this->serviceUsingClient($client);

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage(
            'Gmail authorization is invalid or expired.',
        );

        $service->exchangeCode('test-code');
    }

    public function test_revocation_prefers_refresh_token(): void
    {
        $client = Mockery::mock(GoogleClient::class);
        $client->shouldReceive('revokeToken')
            ->once()
            ->with('refresh-token')
            ->andReturnTrue();

        $service = $this->serviceUsingClient($client);

        $service->revokeAuthorization('refresh-token', 'access-token');

        $this->addToAssertionCount(1);
    }

    public function test_revocation_uses_access_token_when_refresh_token_is_missing(): void
    {
        $client = Mockery::mock(GoogleClient::class);
        $client->shouldReceive('revokeToken')
            ->once()
            ->with('access-token')
            ->andReturnTrue();

        $service = $this->serviceUsingClient($client);

        $service->revokeAuthorization(null, 'access-token');

        $this->addToAssertionCount(1);
    }

    public function test_revocation_failure_is_classified(): void
    {
        $client = Mockery::mock(GoogleClient::class);
        $client->shouldReceive('revokeToken')
            ->once()
            ->with('refresh-token')
            ->andThrow(new RuntimeException('Provider unavailable', 503));

        $service = $this->serviceUsingClient($client);

        try {
            $service->revokeAuthorization('refresh-token', 'access-token');
            $this->fail('Expected a ProviderException.');
        } catch (ProviderException $exception) {
            $this->assertSame(ProviderErrorType::Temporary, $exception->type);
            $this->assertSame('gmail_temporary', $exception->errorCode());
        }
    }

    private function serviceUsingClient(GoogleClient $client): GoogleOAuthService
    {
        return new class(new GmailProviderErrorClassifier, $client) extends GoogleOAuthService
        {
            public function __construct(
                GmailProviderErrorClassifier $errorClassifier,
                private readonly GoogleClient $client,
            ) {
                parent::__construct($errorClassifier);
            }

            public function createClient(): GoogleClient
            {
                return $this->client;
            }
        };
    }
}

<?php

namespace Tests\Feature;

use App\Services\GoogleOAuthService;
use Illuminate\Support\Facades\Config;
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
}

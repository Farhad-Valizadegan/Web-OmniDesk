<?php

namespace Tests\Feature;

use App\Models\ProviderConnection;
use App\Services\GmailMessageSyncService;
use App\Services\GoogleOAuthService;
use Google\Client as GoogleClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GmailAccessTokenRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_access_token_is_refreshed_and_persisted(): void
    {
        $connection = ProviderConnection::factory()->create([
            'access_token' => 'expired-access-token',
            'refresh_token' => 'stored-refresh-token',
            'token_expires_at' => now()->subMinute(),
        ]);

        $oauthService = new class extends GoogleOAuthService
        {
            public function createClient(): GoogleClient
            {
                return new class extends GoogleClient
                {
                    public function fetchAccessTokenWithRefreshToken(
                        $refreshToken = null
                    ): array {
                        if ($refreshToken !== 'stored-refresh-token') {
                            throw new \RuntimeException(
                                'Unexpected refresh token.'
                            );
                        }

                        return [
                            'access_token' => 'refreshed-access-token',
                            'expires_in' => 7200,
                            'token_type' => 'Bearer',
                        ];
                    }
                };
            }
        };

        $service = new class($oauthService) extends GmailMessageSyncService
        {
            public function exposeAuthenticatedClient(
                ProviderConnection $connection
            ): GoogleClient {
                return $this->authenticatedClient($connection);
            }
        };

        $client = $service->exposeAuthenticatedClient($connection);
        $connection->refresh();

        $this->assertSame(
            'refreshed-access-token',
            $connection->access_token
        );
        $this->assertSame(
            'stored-refresh-token',
            $connection->refresh_token
        );
        $this->assertTrue(
            $connection->token_expires_at->isFuture()
        );
        $this->assertSame(
            'refreshed-access-token',
            $client->getAccessToken()['access_token']
        );
    }
}

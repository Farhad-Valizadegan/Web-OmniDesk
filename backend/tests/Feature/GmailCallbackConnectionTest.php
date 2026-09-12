<?php

namespace Tests\Feature;

use App\Http\Controllers\GmailAuthorizationController;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Services\GoogleOAuthService;
use Google\Client as GoogleClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GmailCallbackConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('cache.default', 'array');

        Config::set('services.google', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect_uri' => 'http://localhost:8000/api/integrations/gmail/callback',
            'scopes' => ['openid', 'email'],
        ]);
    }

    public function test_callback_exchanges_and_stores_encrypted_credentials(): void
    {
        $user = User::factory()->create();
        $state = 'connection-state';

        Cache::put(
            GmailAuthorizationController::stateCacheKey($state),
            ['user_id' => $user->id],
            now()->addMinutes(10)
        );

        $fakeService = new class extends GoogleOAuthService
        {
            public function createClient(): GoogleClient
            {
                return new GoogleClient();
            }

            public function exchangeCode(string $code): array
            {
                return [
                    'access_token' => 'stored-access-token',
                    'refresh_token' => 'stored-refresh-token',
                    'expires_in' => 3600,
                    'scope' => 'openid email',
                ];
            }
        };

        $this->app->instance(GoogleOAuthService::class, $fakeService);

        $response = $this->getJson(
            '/api/integrations/gmail/callback?state='
            . $state
            . '&code=test-code'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Gmail authorization completed.'
            )
            ->assertJsonPath('connection.provider', 'gmail')
            ->assertJsonPath('connection.status', 'connected')
            ->assertJsonMissing(['access_token' => 'stored-access-token'])
            ->assertJsonMissing(['refresh_token' => 'stored-refresh-token']);

        $connection = ProviderConnection::first();

        $this->assertNotNull($connection);
        $this->assertSame($user->id, $connection->user_id);
        $this->assertSame('stored-access-token', $connection->access_token);
        $this->assertSame('stored-refresh-token', $connection->refresh_token);
        $this->assertSame(['openid', 'email'], $connection->scopes);

        $stored = DB::table('provider_connections')
            ->where('id', $connection->id)
            ->first();

        $this->assertNotSame(
            'stored-access-token',
            $stored->access_token
        );
        $this->assertNotSame(
            'stored-refresh-token',
            $stored->refresh_token
        );
    }

    public function test_token_exchange_failure_does_not_expose_provider_details(): void
    {
        $user = User::factory()->create();
        $state = 'failed-exchange-state';

        Cache::put(
            GmailAuthorizationController::stateCacheKey($state),
            ['user_id' => $user->id],
            now()->addMinutes(10)
        );

        $fakeService = new class extends GoogleOAuthService
        {
            public function createClient(): GoogleClient
            {
                return new GoogleClient();
            }

            public function exchangeCode(string $code): array
            {
                throw new \RuntimeException(
                    'Sensitive provider token exchange detail'
                );
            }
        };

        $this->app->instance(GoogleOAuthService::class, $fakeService);

        $this->getJson(
            '/api/integrations/gmail/callback?state='
            . $state
            . '&code=invalid-code'
        )
            ->assertStatus(502)
            ->assertJsonPath(
                'error',
                'gmail_token_exchange_failed'
            )
            ->assertJsonMissing([
                'message' => 'Sensitive provider token exchange detail',
            ]);

        $this->assertDatabaseCount('provider_connections', 0);
    }
}

<?php

namespace Tests\Feature;

use App\Exceptions\ProviderErrorType;
use App\Exceptions\ProviderException;
use App\Http\Controllers\GmailAuthorizationController;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Services\GmailProviderErrorClassifier;
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

        Config::set('services.frontend.url', 'http://127.0.0.1:5173');

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

        $fakeService = new class(new GmailProviderErrorClassifier) extends GoogleOAuthService
        {
            public function createClient(): GoogleClient
            {
                return new GoogleClient;
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

            public function accountIdentity(array $token): array
            {
                return [
                    'provider_account_id' => 'google-account-123',
                    'account_email' => 'person@example.com',
                    'account_name' => 'Example Person',
                ];
            }
        };

        $this->app->instance(GoogleOAuthService::class, $fakeService);

        $this->get(
            '/api/integrations/gmail/callback?state='
            .$state
            .'&code=test-code'
        )
            ->assertRedirect(
                'http://127.0.0.1:5173/?gmail=connected'
            );

        $connection = ProviderConnection::first();

        $this->assertNotNull($connection);
        $this->assertSame($user->id, $connection->user_id);
        $this->assertSame(
            'google-account-123',
            $connection->provider_account_id
        );
        $this->assertSame(
            'person@example.com',
            $connection->account_email
        );
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

    public function test_callback_rejects_switching_accounts_without_disconnect(): void
    {
        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->for($user)->create([
            'provider_account_id' => 'original-account',
            'history_id' => '123',
            'gmail_sync_page_token' => 'original-page',
        ]);
        $original = $connection->fresh()->getRawOriginal();
        $this->bindReconnectService('different-account');
        Cache::put(GmailAuthorizationController::stateCacheKey('switch'), ['user_id' => $user->id], 600);

        $this->get('/api/integrations/gmail/callback?state=switch&code=test')
            ->assertRedirect('http://127.0.0.1:5173/?gmail=gmail_account_mismatch');

        $this->assertSame($original, $connection->fresh()->getRawOriginal());
        $this->assertDatabaseCount('provider_connections', 1);
    }

    public function test_same_account_reconnect_preserves_refresh_token_and_sync_cursor(): void
    {
        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->for($user)->create([
            'provider_account_id' => 'original-account', 'history_id' => '123',
            'refresh_token' => 'original-refresh',
        ]);
        $this->bindReconnectService('original-account');
        Cache::put(GmailAuthorizationController::stateCacheKey('reconnect'), ['user_id' => $user->id], 600);

        $this->get('/api/integrations/gmail/callback?state=reconnect&code=test')
            ->assertRedirect('http://127.0.0.1:5173/?gmail=connected');

        $this->assertSame('new-access', $connection->fresh()->access_token);
        $this->assertSame('original-refresh', $connection->fresh()->refresh_token);
        $this->assertSame('123', $connection->fresh()->history_id);
    }

    private function bindReconnectService(string $accountId): void
    {
        $service = new class($accountId) extends GoogleOAuthService
        {
            public function __construct(private readonly string $accountId) {}

            public function exchangeCode(string $code): array
            {
                return ['access_token' => 'new-access', 'expires_in' => 3600];
            }

            public function accountIdentity(array $token): array
            {
                return ['provider_account_id' => $this->accountId, 'account_email' => 'person@example.com'];
            }
        };
        $this->app->instance(GoogleOAuthService::class, $service);
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

        $fakeService = new class(new GmailProviderErrorClassifier) extends GoogleOAuthService
        {
            public function createClient(): GoogleClient
            {
                return new GoogleClient;
            }

            public function exchangeCode(string $code): array
            {
                throw new \RuntimeException(
                    'Sensitive provider token exchange detail'
                );
            }
        };

        $this->app->instance(GoogleOAuthService::class, $fakeService);

        $this->get(
            '/api/integrations/gmail/callback?state='
            .$state
            .'&code=invalid-code'
        )
            ->assertRedirect(
                'http://127.0.0.1:5173/?gmail=gmail_token_exchange_failed'
            );

        $this->assertDatabaseCount('provider_connections', 0);
    }

    public function test_identity_failure_does_not_create_connection(): void
    {
        $user = User::factory()->create();
        $state = 'identity-failure-state';

        Cache::put(
            GmailAuthorizationController::stateCacheKey($state),
            ['user_id' => $user->id],
            now()->addMinutes(10)
        );

        $fakeService = new class(new GmailProviderErrorClassifier) extends GoogleOAuthService
        {
            public function createClient(): GoogleClient
            {
                return new GoogleClient;
            }

            public function exchangeCode(string $code): array
            {
                return [
                    'access_token' => 'temporary-access-token',
                    'expires_in' => 3600,
                ];
            }

            public function accountIdentity(array $token): array
            {
                throw new \RuntimeException(
                    'Sensitive account identity provider detail'
                );
            }
        };

        $this->app->instance(GoogleOAuthService::class, $fakeService);

        $this->get(
            '/api/integrations/gmail/callback?state='
            .$state
            .'&code=test-code'
        )
            ->assertRedirect(
                'http://127.0.0.1:5173/?gmail=gmail_account_identity_failed'
            );

        $this->assertDatabaseCount('provider_connections', 0);
    }

    public function test_classified_provider_failure_redirects_with_stable_error_code(): void
    {
        $user = User::factory()->create();
        $state = 'classified-provider-failure-state';

        Cache::put(
            GmailAuthorizationController::stateCacheKey($state),
            ['user_id' => $user->id],
            now()->addMinutes(10)
        );

        $fakeService = new class(new GmailProviderErrorClassifier) extends GoogleOAuthService
        {
            public function exchangeCode(string $code): array
            {
                throw new ProviderException(
                    'gmail',
                    ProviderErrorType::RateLimit,
                    'Gmail request limit was reached.'
                );
            }
        };

        $this->app->instance(GoogleOAuthService::class, $fakeService);

        $this->get(
            '/api/integrations/gmail/callback?state='
            .$state
            .'&code=test-code'
        )->assertRedirect(
            'http://127.0.0.1:5173/?gmail=gmail_rate_limit'
        );

        $this->assertDatabaseCount('provider_connections', 0);
    }
}

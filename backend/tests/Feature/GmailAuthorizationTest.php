<?php

namespace Tests\Feature;

use App\Http\Controllers\GmailAuthorizationController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class GmailAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

        Config::set('cache.default', 'array');
    }

    public function test_authenticated_user_can_start_gmail_authorization(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/integrations/gmail/authorize');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'authorization_url',
                'expires_in',
            ]);

        $authorizationUrl = $response->json('authorization_url');

        parse_str(
            (string) parse_url($authorizationUrl, PHP_URL_QUERY),
            $query
        );

        $this->assertSame(
            'test-client-id.apps.googleusercontent.com',
            $query['client_id']
        );
        $this->assertArrayHasKey('state', $query);
        $this->assertSame(600, $response->json('expires_in'));

        $cachedState = Cache::get(
            GmailAuthorizationController::stateCacheKey($query['state'])
        );

        $this->assertSame(
            ['user_id' => $user->id],
            $cachedState
        );
    }

    public function test_unauthenticated_user_cannot_start_gmail_authorization(): void
    {
        $this->getJson('/api/integrations/gmail/authorize')
            ->assertUnauthorized();
    }
}

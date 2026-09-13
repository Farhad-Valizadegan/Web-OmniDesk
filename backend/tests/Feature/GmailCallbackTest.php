<?php

namespace Tests\Feature;

use App\Http\Controllers\GmailAuthorizationController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GmailCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
    }

    public function test_callback_rejects_missing_state(): void
    {
        $this->get('/api/integrations/gmail/callback')
            ->assertRedirect('http://127.0.0.1:5173/?gmail=oauth_state_invalid');
    }

    public function test_callback_rejects_unknown_state(): void
    {
        $this->get('/api/integrations/gmail/callback?state=unknown-state')
            ->assertRedirect('http://127.0.0.1:5173/?gmail=oauth_state_invalid');
    }

    public function test_callback_rejects_replayed_state(): void
    {
        $user = User::factory()->create();
        $state = 'single-use-state';

        Cache::put(
            GmailAuthorizationController::stateCacheKey($state),
            ['user_id' => $user->id],
            now()->addMinutes(10)
        );

        $this->get(
            '/api/integrations/gmail/callback?state='
            .$state
            .'&error=access_denied'
        )
            ->assertRedirect(
                'http://127.0.0.1:5173/?gmail=authorization_denied'
            );

        $this->get(
            '/api/integrations/gmail/callback?state='
            .$state
            .'&error=access_denied'
        )
            ->assertRedirect(
                'http://127.0.0.1:5173/?gmail=oauth_state_invalid'
            );
    }

    public function test_callback_rejects_missing_authorization_code(): void
    {
        $user = User::factory()->create();
        $state = 'valid-state-without-code';

        Cache::put(
            GmailAuthorizationController::stateCacheKey($state),
            ['user_id' => $user->id],
            now()->addMinutes(10)
        );

        $this->get(
            '/api/integrations/gmail/callback?state='.$state
        )
            ->assertRedirect(
                'http://127.0.0.1:5173/?gmail=authorization_code_missing'
            );
    }

    public function test_valid_state_is_consumed_before_token_exchange(): void
    {
        $user = User::factory()->create();
        $state = 'valid-state';

        Cache::put(
            GmailAuthorizationController::stateCacheKey($state),
            ['user_id' => $user->id],
            now()->addMinutes(10)
        );

        $this->get(
            '/api/integrations/gmail/callback?state='
            .$state
            .'&code=test-code'
        )
            ->assertRedirect(
                'http://127.0.0.1:5173/?gmail=gmail_token_exchange_failed'
            );

        $this->assertNull(
            Cache::get(
                GmailAuthorizationController::stateCacheKey($state)
            )
        );
    }
}

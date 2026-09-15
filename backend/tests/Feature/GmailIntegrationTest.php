<?php

namespace Tests\Feature;

use App\Exceptions\ProviderErrorType;
use App\Exceptions\ProviderException;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Services\GoogleOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class GmailIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_without_connection_sees_disconnected_status(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/integrations/gmail')
            ->assertOk()
            ->assertJson([
                'connected' => false,
                'connection' => null,
            ]);
    }

    public function test_authenticated_user_can_view_own_connection_status(): void
    {
        $user = User::factory()->create();

        ProviderConnection::factory()
            ->for($user)
            ->create([
                'provider_account_id' => 'gmail-account-123',
                'account_email' => 'person@example.com',
                'status' => 'connected',
            ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/integrations/gmail')
            ->assertOk()
            ->assertJsonPath('connected', true)
            ->assertJsonPath(
                'connection.provider_account_id',
                'gmail-account-123'
            )
            ->assertJsonPath(
                'connection.account_email',
                'person@example.com'
            )
            ->assertJsonPath('connection.status', 'connected')
            ->assertJsonMissingPath('connection.access_token')
            ->assertJsonMissingPath('connection.refresh_token');
    }

    public function test_user_cannot_view_another_users_connection(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        ProviderConnection::factory()
            ->for($owner)
            ->create();

        $this->actingAs($otherUser, 'sanctum')
            ->getJson('/api/integrations/gmail')
            ->assertOk()
            ->assertJson([
                'connected' => false,
                'connection' => null,
            ]);
    }

    public function test_disconnect_removes_gmail_data_without_connection(): void
    {
        $user = User::factory()->create();

        $conversation = Conversation::factory()
            ->for($user)
            ->create(['provider' => 'gmail']);

        Message::factory()
            ->for($conversation)
            ->create(['provider' => 'gmail']);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/integrations/gmail')
            ->assertNoContent();

        $this->assertDatabaseMissing('conversations', [
            'id' => $conversation->id,
        ]);

        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
        ]);
    }

    public function test_disconnect_revokes_refresh_token_and_removes_local_gmail_data(): void
    {
        $user = User::factory()->create();

        ProviderConnection::factory()
            ->for($user)
            ->create([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
            ]);

        $conversation = Conversation::factory()
            ->for($user)
            ->create(['provider' => 'gmail']);

        Message::factory()
            ->for($conversation)
            ->create(['provider' => 'gmail']);

        $this->mock(
            GoogleOAuthService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('revokeAuthorization')
                    ->once()
                    ->with('refresh-token', 'access-token');
            }
        );

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/integrations/gmail')
            ->assertNoContent();

        $this->assertDatabaseMissing('provider_connections', [
            'user_id' => $user->id,
            'provider' => 'gmail',
        ]);

        $this->assertDatabaseMissing('conversations', [
            'id' => $conversation->id,
        ]);

        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
        ]);
    }

    public function test_disconnect_revokes_access_token_when_refresh_token_is_missing(): void
    {
        $user = User::factory()->create();

        ProviderConnection::factory()
            ->for($user)
            ->create([
                'access_token' => 'access-token-only',
                'refresh_token' => null,
            ]);

        $this->mock(
            GoogleOAuthService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('revokeAuthorization')
                    ->once()
                    ->with(null, 'access-token-only');
            }
        );

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/integrations/gmail')
            ->assertNoContent();

        $this->assertDatabaseMissing('provider_connections', [
            'user_id' => $user->id,
            'provider' => 'gmail',
        ]);
    }

    public function test_revocation_failure_is_reported_after_local_cleanup(): void
    {
        $user = User::factory()->create();

        ProviderConnection::factory()
            ->for($user)
            ->create([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
            ]);

        $conversation = Conversation::factory()
            ->for($user)
            ->create(['provider' => 'gmail']);

        Message::factory()
            ->for($conversation)
            ->create(['provider' => 'gmail']);

        $this->mock(
            GoogleOAuthService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('revokeAuthorization')
                    ->once()
                    ->with('refresh-token', 'access-token')
                    ->andThrow(new ProviderException(
                        'gmail',
                        ProviderErrorType::Temporary,
                        'Gmail is temporarily unavailable.'
                    ));
            }
        );

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/integrations/gmail');

        $response
            ->assertStatus(202)
            ->assertJsonPath('error', 'gmail_revocation_failed')
            ->assertJsonMissingPath('access_token')
            ->assertJsonMissingPath('refresh_token');

        $this->assertStringNotContainsString(
            'refresh-token',
            $response->getContent()
        );
        $this->assertStringNotContainsString(
            'access-token',
            $response->getContent()
        );

        $this->assertDatabaseMissing('provider_connections', [
            'user_id' => $user->id,
            'provider' => 'gmail',
        ]);
        $this->assertDatabaseMissing('conversations', [
            'id' => $conversation->id,
        ]);
        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
        ]);
    }

    public function test_user_cannot_delete_or_revoke_another_users_connection(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $connection = ProviderConnection::factory()
            ->for($owner)
            ->create([
                'access_token' => 'owner-access-token',
                'refresh_token' => 'owner-refresh-token',
            ]);

        $this->mock(
            GoogleOAuthService::class,
            function (MockInterface $mock): void {
                $mock->shouldNotReceive('revokeAuthorization');
            }
        );

        $this->actingAs($otherUser, 'sanctum')
            ->deleteJson('/api/integrations/gmail')
            ->assertNoContent();

        $this->assertDatabaseHas('provider_connections', [
            'id' => $connection->id,
        ]);
    }

    public function test_unauthenticated_users_cannot_manage_gmail_connection(): void
    {
        $this->getJson('/api/integrations/gmail')
            ->assertUnauthorized();

        $this->deleteJson('/api/integrations/gmail')
            ->assertUnauthorized();
    }
}

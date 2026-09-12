<?php

namespace Tests\Feature;

use App\Models\ProviderConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_user_can_disconnect_own_gmail_connection(): void
    {
        $user = User::factory()->create();

        ProviderConnection::factory()
            ->for($user)
            ->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/integrations/gmail')
            ->assertNoContent();

        $this->assertDatabaseMissing('provider_connections', [
            'user_id' => $user->id,
            'provider' => 'gmail',
        ]);
    }

    public function test_user_cannot_delete_another_users_connection(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $connection = ProviderConnection::factory()
            ->for($owner)
            ->create();

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

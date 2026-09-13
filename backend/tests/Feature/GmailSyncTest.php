<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GmailMessageSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GmailSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_start_gmail_synchronization(): void
    {
        $user = User::factory()->create();

        $fakeService = new class extends GmailMessageSyncService
        {
            public function __construct()
            {
            }

            public function sync(
                User $user,
                int $maxResults = 20,
                ?string $pageToken = null,
            ): array {
                return [
                    'threads_processed' => 2,
                    'messages_processed' => 3,
                    'next_page_token' => 'next-page-token',
                ];
            }
        };

        $this->app->instance(GmailMessageSyncService::class, $fakeService);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/integrations/gmail/sync', [
                'max_results' => 10,
                'page_token' => 'page-token',
            ])
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Gmail synchronization completed.'
            )
            ->assertJsonPath('sync.threads_processed', 2)
            ->assertJsonPath('sync.messages_processed', 3)
            ->assertJsonPath(
                'sync.next_page_token',
                'next-page-token'
            );
    }

    public function test_sync_failure_does_not_expose_provider_details(): void
    {
        $user = User::factory()->create();

        $fakeService = new class extends GmailMessageSyncService
        {
            public function __construct()
            {
            }

            public function sync(
                User $user,
                int $maxResults = 20,
                ?string $pageToken = null,
            ): array {
                throw new \RuntimeException(
                    'Sensitive Gmail provider detail'
                );
            }
        };

        $this->app->instance(GmailMessageSyncService::class, $fakeService);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/integrations/gmail/sync')
            ->assertStatus(502)
            ->assertJsonPath('error', 'gmail_sync_failed')
            ->assertJsonMissing([
                'message' => 'Sensitive Gmail provider detail',
            ]);
    }

    public function test_unauthenticated_users_cannot_synchronize_gmail(): void
    {
        $this->postJson('/api/integrations/gmail/sync')
            ->assertUnauthorized();
    }
}

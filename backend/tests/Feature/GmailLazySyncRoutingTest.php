<?php

namespace Tests\Feature;

use App\Models\ProviderConnection;
use App\Models\User;
use App\Services\GmailMessageSyncService;
use App\Services\GmailProviderErrorClassifier;
use App\Services\GoogleOAuthService;
use Google\Service\Gmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class GmailLazySyncRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_sync_finishes_lazy_sync_before_incremental_sync(): void
    {
        $user = User::factory()->create();

        $connection = ProviderConnection::factory()
            ->for($user)
            ->create([
                'history_id' => 'existing-history',
                'gmail_sync_completed' => false,
                'gmail_sync_page_token' => 'provider-page-token',
            ]);

        $service = new class(new GoogleOAuthService(new GmailProviderErrorClassifier)) extends GmailMessageSyncService
        {
            public int $lazyCalls = 0;

            public function syncNextPage(
                User $user,
                int $maxResults = 15,
            ): array {
                $this->lazyCalls++;

                if ($this->lazyCalls === 2) {
                    ProviderConnection::query()
                        ->where('user_id', $user->id)
                        ->where('provider', 'gmail')
                        ->update([
                            'gmail_sync_completed' => true,
                            'gmail_sync_page_token' => null,
                        ]);
                }

                return [
                    'threads_processed' => $this->lazyCalls,
                    'messages_processed' => $this->lazyCalls,
                    'has_more' => $this->lazyCalls === 1,
                ];
            }

            protected function gmailService(
                ProviderConnection $connection
            ): Gmail {
                throw new LogicException(
                    'Incremental synchronization started too early.'
                );
            }
        };

        $firstResult = $service->sync($user, 100);

        $this->assertSame(1, $firstResult['threads_processed']);
        $this->assertSame(1, $firstResult['messages_processed']);
        $this->assertNull($firstResult['next_page_token']);
        $this->assertSame(1, $service->lazyCalls);

        $this->assertFalse(
            $connection->fresh()->gmail_sync_completed
        );

        $secondResult = $service->sync($user, 100);

        $this->assertSame(2, $secondResult['threads_processed']);
        $this->assertSame(2, $secondResult['messages_processed']);
        $this->assertNull($secondResult['next_page_token']);
        $this->assertSame(2, $service->lazyCalls);
        $this->assertTrue(
            $connection->fresh()->gmail_sync_completed
        );
        $this->assertNull(
            $connection->fresh()->gmail_sync_page_token
        );
    }
}

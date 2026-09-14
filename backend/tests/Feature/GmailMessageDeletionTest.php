<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\GmailMessageSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GmailMessageDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_gmail_deletion_is_restricted_to_the_synchronizing_user(): void
    {
        $synchronizingUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownedConversation = Conversation::factory()
            ->for($synchronizingUser)
            ->create(['provider' => 'gmail']);

        $otherConversation = Conversation::factory()
            ->for($otherUser)
            ->create(['provider' => 'gmail']);

        $ownedMessage = Message::factory()
            ->for($ownedConversation)
            ->create([
                'provider' => 'gmail',
                'provider_message_id' => 'shared-provider-message-id',
            ]);

        $otherMessage = Message::factory()
            ->for($otherConversation)
            ->create([
                'provider' => 'gmail',
                'provider_message_id' => 'shared-provider-message-id',
            ]);

        $service = new class extends GmailMessageSyncService
        {
            public function __construct() {}

            /**
             * @param  array<int, string>  $messageIds
             */
            public function deleteForUser(
                User $user,
                array $messageIds,
            ): void {
                $this->deleteMessages($user, $messageIds);
            }
        };

        $service->deleteForUser(
            $synchronizingUser,
            ['shared-provider-message-id']
        );

        $this->assertModelMissing($ownedMessage);
        $this->assertModelExists($otherMessage);
    }
}

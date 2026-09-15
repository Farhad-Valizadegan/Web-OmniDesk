<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Services\GmailMessageSyncService;
use App\Services\GmailProviderErrorClassifier;
use App\Services\GoogleOAuthService;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Google\Service\Gmail\ListThreadsResponse;
use Google\Service\Gmail\Message as GmailMessage;
use Google\Service\Gmail\MessagePart;
use Google\Service\Gmail\MessagePartBody;
use Google\Service\Gmail\MessagePartHeader;
use Google\Service\Gmail\Profile;
use Google\Service\Gmail\Resource\Users;
use Google\Service\Gmail\Resource\UsersThreads;
use Google\Service\Gmail\Thread as GmailThread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GmailFullSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_sync_persists_mailbox_without_duplicates(): void
    {
        $user = User::factory()->create();

        $connection = ProviderConnection::factory()
            ->for($user)
            ->create(['history_id' => null]);

        $profile = new Profile;
        $profile->setHistoryId('history-200');

        $usersResource = Mockery::mock(Users::class);
        $usersResource
            ->shouldReceive('getProfile')
            ->twice()
            ->with('me')
            ->andReturn($profile);

        $headers = [];

        foreach ([
            'From' => 'Sender <sender@example.com>',
            'To' => 'Recipient <recipient@example.com>',
            'Subject' => 'Initial synchronized message',
        ] as $name => $value) {
            $header = new MessagePartHeader;
            $header->setName($name);
            $header->setValue($value);
            $headers[] = $header;
        }

        $body = new MessagePartBody;
        $body->setData(
            rtrim(
                strtr(base64_encode('Initial message body'), '+/', '-_'),
                '='
            )
        );

        $payload = new MessagePart;
        $payload->setMimeType('text/plain');
        $payload->setHeaders($headers);
        $payload->setBody($body);

        $gmailMessage = new GmailMessage;
        $gmailMessage->setId('message-1');
        $gmailMessage->setThreadId('thread-1');
        $gmailMessage->setLabelIds(['INBOX', 'UNREAD']);
        $gmailMessage->setInternalDate('1726311600000');
        $gmailMessage->setPayload($payload);

        $threadSummary = new GmailThread;
        $threadSummary->setId('thread-1');

        $threadList = new ListThreadsResponse;
        $threadList->setThreads([$threadSummary]);
        $threadList->setNextPageToken(null);

        $fullThread = new GmailThread;
        $fullThread->setId('thread-1');
        $fullThread->setMessages([$gmailMessage]);

        $listRequests = [];

        $threadsResource = Mockery::mock(UsersThreads::class);
        $threadsResource
            ->shouldReceive('listUsersThreads')
            ->twice()
            ->withArgs(function (
                string $userId,
                array $options,
            ) use (&$listRequests): bool {
                $listRequests[] = [
                    'user_id' => $userId,
                    'options' => $options,
                ];

                return true;
            })
            ->andReturn($threadList);

        $threadsResource
            ->shouldReceive('get')
            ->twice()
            ->with('me', 'thread-1', ['format' => 'full'])
            ->andReturn($fullThread);

        $gmail = new Gmail(new GoogleClient);
        $gmail->users = $usersResource;
        $gmail->users_threads = $threadsResource;

        $service = new class($gmail) extends GmailMessageSyncService
        {
            public function __construct(
                private readonly Gmail $fakeGmail,
            ) {
                parent::__construct(
                    new GoogleOAuthService(new GmailProviderErrorClassifier)
                );
            }

            protected function gmailService(
                ProviderConnection $connection
            ): Gmail {
                return $this->fakeGmail;
            }
        };

        $firstResult = $service->sync($user);
        $secondResult = $service->sync($user, 20, 'repeat-page');

        $this->assertSame(1, $firstResult['threads_processed']);
        $this->assertSame(1, $firstResult['messages_processed']);
        $this->assertSame(1, $secondResult['threads_processed']);
        $this->assertSame(1, $secondResult['messages_processed']);

        $this->assertCount(2, $listRequests);
        $this->assertArrayNotHasKey(
            'pageToken',
            $listRequests[0]['options']
        );
        $this->assertSame(
            'repeat-page',
            $listRequests[1]['options']['pageToken']
        );

        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseCount('messages', 1);

        $conversation = Conversation::query()->firstOrFail();
        $message = Message::query()->firstOrFail();

        $this->assertSame($user->id, $conversation->user_id);
        $this->assertSame('thread-1', $conversation->provider_conversation_id);
        $this->assertFalse($conversation->is_read);
        $this->assertSame(['INBOX', 'UNREAD'], $conversation->labels);

        $this->assertSame($conversation->id, $message->conversation_id);
        $this->assertSame('message-1', $message->provider_message_id);
        $this->assertSame('Initial message body', $message->body);
        $this->assertFalse($message->is_read);

        $this->assertSame(
            'history-200',
            $connection->fresh()->history_id
        );
    }
}

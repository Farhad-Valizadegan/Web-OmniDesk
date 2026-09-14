<?php

namespace Tests\Feature;

use App\Exceptions\ProviderException;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Services\GmailMessageSyncService;
use App\Services\GoogleOAuthService;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Google\Service\Gmail\History;
use Google\Service\Gmail\HistoryLabelAdded;
use Google\Service\Gmail\HistoryMessageDeleted;
use Google\Service\Gmail\ListHistoryResponse;
use Google\Service\Gmail\ListThreadsResponse;
use Google\Service\Gmail\Message as GmailMessage;
use Google\Service\Gmail\MessagePart;
use Google\Service\Gmail\MessagePartBody;
use Google\Service\Gmail\MessagePartHeader;
use Google\Service\Gmail\Profile;
use Google\Service\Gmail\Resource\Users;
use Google\Service\Gmail\Resource\UsersHistory;
use Google\Service\Gmail\Resource\UsersMessages;
use Google\Service\Gmail\Resource\UsersThreads;
use Google\Service\Gmail\Thread as GmailThread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class GmailIncrementalSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_incremental_sync_paginates_history_and_advances_cursor(): void
    {
        $user = User::factory()->create();

        $connection = ProviderConnection::factory()
            ->for($user)
            ->create(['history_id' => 'history-100']);

        $firstPage = new ListHistoryResponse;
        $firstPage->setHistory([]);
        $firstPage->setHistoryId('history-150');
        $firstPage->setNextPageToken('history-page-2');

        $secondPage = new ListHistoryResponse;
        $secondPage->setHistory([]);
        $secondPage->setHistoryId('history-200');
        $secondPage->setNextPageToken(null);

        $requests = [];

        $historyResource = Mockery::mock(UsersHistory::class);
        $historyResource
            ->shouldReceive('listUsersHistory')
            ->twice()
            ->withArgs(function (
                string $userId,
                array $options,
            ) use (&$requests): bool {
                $requests[] = [
                    'user_id' => $userId,
                    'options' => $options,
                ];

                return true;
            })
            ->andReturn($firstPage, $secondPage);

        $gmail = new Gmail(new GoogleClient);
        $gmail->users_history = $historyResource;

        $service = new class($gmail) extends GmailMessageSyncService
        {
            public function __construct(
                private readonly Gmail $fakeGmail,
            ) {
                parent::__construct(new GoogleOAuthService);
            }

            protected function gmailService(
                ProviderConnection $connection
            ): Gmail {
                return $this->fakeGmail;
            }
        };

        $result = $service->sync($user, 4);

        $this->assertSame([
            'threads_processed' => 0,
            'messages_processed' => 0,
            'next_page_token' => null,
        ], $result);

        $this->assertCount(2, $requests);
        $this->assertSame('me', $requests[0]['user_id']);
        $this->assertSame(
            'history-100',
            $requests[0]['options']['startHistoryId']
        );
        $this->assertSame(100, $requests[0]['options']['maxResults']);
        $this->assertArrayNotHasKey(
            'pageToken',
            $requests[0]['options']
        );
        $this->assertSame(
            'history-page-2',
            $requests[1]['options']['pageToken']
        );
        $this->assertSame(
            'history-200',
            $connection->fresh()->history_id
        );
    }

    public function test_failed_incremental_sync_does_not_advance_cursor(): void
    {
        $user = User::factory()->create();

        $connection = ProviderConnection::factory()
            ->for($user)
            ->create(['history_id' => 'history-100']);

        $historyResource = Mockery::mock(UsersHistory::class);
        $historyResource
            ->shouldReceive('listUsersHistory')
            ->times(3)
            ->andThrow(
                new RuntimeException(
                    'Sensitive temporary provider failure',
                    503
                )
            );

        $gmail = new Gmail(new GoogleClient);
        $gmail->users_history = $historyResource;

        $service = new class($gmail) extends GmailMessageSyncService
        {
            public function __construct(
                private readonly Gmail $fakeGmail,
            ) {
                parent::__construct(new GoogleOAuthService);
            }

            protected function gmailService(
                ProviderConnection $connection
            ): Gmail {
                return $this->fakeGmail;
            }
        };

        try {
            $service->sync($user);
            $this->fail('Expected a provider exception.');
        } catch (ProviderException $exception) {
            $this->assertSame(
                'gmail_temporary',
                $exception->errorCode()
            );
        }

        $this->assertSame(
            'history-100',
            $connection->fresh()->history_id
        );
    }

    public function test_expired_history_cursor_falls_back_to_full_sync(): void
    {
        $user = User::factory()->create();

        $connection = ProviderConnection::factory()
            ->for($user)
            ->create(['history_id' => 'expired-history']);

        $historyResource = Mockery::mock(UsersHistory::class);
        $historyResource
            ->shouldReceive('listUsersHistory')
            ->once()
            ->andThrow(
                new RuntimeException('History cursor not found', 404)
            );

        $profile = new Profile;
        $profile->setHistoryId('replacement-history');

        $usersResource = Mockery::mock(Users::class);
        $usersResource
            ->shouldReceive('getProfile')
            ->once()
            ->with('me')
            ->andReturn($profile);

        $threadList = new ListThreadsResponse;
        $threadList->setThreads([]);
        $threadList->setNextPageToken(null);

        $threadsResource = Mockery::mock(UsersThreads::class);
        $threadsResource
            ->shouldReceive('listUsersThreads')
            ->once()
            ->with(
                'me',
                Mockery::on(
                    fn (array $options): bool => $options['maxResults'] === 20
                )
            )
            ->andReturn($threadList);

        $gmail = new Gmail(new GoogleClient);
        $gmail->users = $usersResource;
        $gmail->users_history = $historyResource;
        $gmail->users_threads = $threadsResource;

        $service = new class($gmail) extends GmailMessageSyncService
        {
            public function __construct(
                private readonly Gmail $fakeGmail,
            ) {
                parent::__construct(new GoogleOAuthService);
            }

            protected function gmailService(
                ProviderConnection $connection
            ): Gmail {
                return $this->fakeGmail;
            }
        };

        $result = $service->sync($user);

        $this->assertSame([
            'threads_processed' => 0,
            'messages_processed' => 0,
            'next_page_token' => null,
        ], $result);

        $this->assertSame(
            'replacement-history',
            $connection->fresh()->history_id
        );
    }

    public function test_incremental_sync_removes_deleted_gmail_message(): void
    {
        $user = User::factory()->create();

        $connection = ProviderConnection::factory()
            ->for($user)
            ->create(['history_id' => 'history-100']);

        $conversation = Conversation::factory()
            ->for($user)
            ->create(['provider' => 'gmail']);

        $localMessage = Message::factory()
            ->for($conversation)
            ->create([
                'provider' => 'gmail',
                'provider_message_id' => 'deleted-message-1',
            ]);

        $gmailMessage = new GmailMessage;
        $gmailMessage->setId('deleted-message-1');

        $deletedChange = new HistoryMessageDeleted;
        $deletedChange->setMessage($gmailMessage);

        $historyRecord = new History;
        $historyRecord->setMessagesDeleted([$deletedChange]);

        $historyPage = new ListHistoryResponse;
        $historyPage->setHistory([$historyRecord]);
        $historyPage->setHistoryId('history-200');
        $historyPage->setNextPageToken(null);

        $historyResource = Mockery::mock(UsersHistory::class);
        $historyResource
            ->shouldReceive('listUsersHistory')
            ->once()
            ->andReturn($historyPage);

        $gmail = new Gmail(new GoogleClient);
        $gmail->users_history = $historyResource;

        $service = new class($gmail) extends GmailMessageSyncService
        {
            public function __construct(
                private readonly Gmail $fakeGmail,
            ) {
                parent::__construct(new GoogleOAuthService);
            }

            protected function gmailService(
                ProviderConnection $connection
            ): Gmail {
                return $this->fakeGmail;
            }
        };

        $service->sync($user);

        $this->assertModelMissing($localMessage);
        $this->assertSame(
            'history-200',
            $connection->fresh()->history_id
        );
    }

    public function test_label_change_refreshes_labels_and_read_state(): void
    {
        $user = User::factory()->create();

        $connection = ProviderConnection::factory()
            ->for($user)
            ->create(['history_id' => 'history-100']);

        $conversation = Conversation::factory()
            ->for($user)
            ->create([
                'provider' => 'gmail',
                'provider_conversation_id' => 'thread-1',
                'is_read' => false,
                'labels' => ['INBOX', 'UNREAD'],
            ]);

        Message::factory()
            ->for($conversation)
            ->create([
                'provider' => 'gmail',
                'provider_message_id' => 'message-1',
                'is_read' => false,
            ]);

        $headers = [];

        foreach ([
            'From' => 'Sender <sender@example.com>',
            'To' => 'Recipient <recipient@example.com>',
            'Subject' => 'Updated subject',
        ] as $name => $value) {
            $header = new MessagePartHeader;
            $header->setName($name);
            $header->setValue($value);
            $headers[] = $header;
        }

        $body = new MessagePartBody;
        $body->setData(
            rtrim(
                strtr(base64_encode('Updated body'), '+/', '-_'),
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
        $gmailMessage->setLabelIds(['INBOX', 'IMPORTANT']);
        $gmailMessage->setInternalDate('1726311600000');
        $gmailMessage->setPayload($payload);

        $labelChange = new HistoryLabelAdded;
        $labelChange->setMessage($gmailMessage);

        $historyRecord = new History;
        $historyRecord->setLabelsAdded([$labelChange]);

        $historyPage = new ListHistoryResponse;
        $historyPage->setHistory([$historyRecord]);
        $historyPage->setHistoryId('history-200');

        $historyResource = Mockery::mock(UsersHistory::class);
        $historyResource
            ->shouldReceive('listUsersHistory')
            ->once()
            ->andReturn($historyPage);

        $messagesResource = Mockery::mock(UsersMessages::class);
        $messagesResource
            ->shouldReceive('get')
            ->once()
            ->with('me', 'message-1', ['format' => 'full'])
            ->andReturn($gmailMessage);

        $gmailThread = new GmailThread;
        $gmailThread->setMessages([$gmailMessage]);

        $threadsResource = Mockery::mock(UsersThreads::class);
        $threadsResource
            ->shouldReceive('get')
            ->once()
            ->with('me', 'thread-1', ['format' => 'full'])
            ->andReturn($gmailThread);

        $gmail = new Gmail(new GoogleClient);
        $gmail->users_history = $historyResource;
        $gmail->users_messages = $messagesResource;
        $gmail->users_threads = $threadsResource;

        $service = new class($gmail) extends GmailMessageSyncService
        {
            public function __construct(
                private readonly Gmail $fakeGmail,
            ) {
                parent::__construct(new GoogleOAuthService);
            }

            protected function gmailService(
                ProviderConnection $connection
            ): Gmail {
                return $this->fakeGmail;
            }
        };

        $result = $service->sync($user);

        $this->assertSame(1, $result['threads_processed']);
        $this->assertSame(1, $result['messages_processed']);

        $conversation->refresh();

        $this->assertTrue($conversation->is_read);
        $this->assertSame(
            ['INBOX', 'IMPORTANT'],
            $conversation->labels
        );

        $message = $conversation->messages()->firstOrFail();

        $this->assertTrue($message->is_read);
        $this->assertSame('Updated subject', $message->subject);
        $this->assertSame('Updated body', $message->body);
        $this->assertSame(
            'history-200',
            $connection->fresh()->history_id
        );
    }
}

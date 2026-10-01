<?php

namespace Tests\Feature;

use App\Exceptions\ProviderException;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Services\GmailMessageSyncService;
use App\Services\GmailProviderErrorClassifier;
use App\Services\GoogleOAuthService;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\History;
use Google\Service\Gmail\HistoryLabelAdded;
use Google\Service\Gmail\HistoryMessageDeleted;
use Google\Service\Gmail\ListHistoryResponse;
use Google\Service\Gmail\ListThreadsResponse;
use Google\Service\Gmail\Message as GmailMessage;
use Google\Service\Gmail\Profile;
use Google\Service\Gmail\Resource\Users;
use Google\Service\Gmail\Resource\UsersHistory;
use Google\Service\Gmail\Resource\UsersMessages;
use Google\Service\Gmail\Resource\UsersThreads;
use Google\Service\Gmail\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class GmailSyncRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_scan_keeps_first_snapshot_until_all_pages_finish(): void
    {
        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->for($user)->create(['history_id' => null]);
        $gmail = new Gmail(new Client);
        $gmail->users = Mockery::mock(Users::class);
        $gmail->users->shouldReceive('getProfile')->once()->andReturn(new Profile(['historyId' => '100']));
        $gmail->users_threads = Mockery::mock(UsersThreads::class);
        $gmail->users_threads->shouldReceive('listUsersThreads')->once()
            ->with('me', ['maxResults' => 20])
            ->andReturn(new ListThreadsResponse(['threads' => [], 'nextPageToken' => 'page-two']));
        $gmail->users_threads->shouldReceive('listUsersThreads')->once()
            ->with('me', ['maxResults' => 20, 'pageToken' => 'page-two'])
            ->andReturn(new ListThreadsResponse(['threads' => []]));
        $service = $this->service($gmail);

        $this->assertSame('page-two', $service->sync($user)['next_page_token']);
        $this->assertNull($connection->fresh()->history_id);
        // A later invocation must resume even if the client lost its page token.
        $service->sync($user);
        $this->assertSame('100', $connection->fresh()->history_id);
        $this->assertNull($connection->fresh()->gmail_sync_snapshot_history_id);
        $this->assertNull($connection->fresh()->gmail_sync_page_token);

        $gmail->users_history = Mockery::mock(UsersHistory::class);
        $gmail->users_history->shouldReceive('listUsersHistory')->once()
            ->with('me', Mockery::on(fn (array $options): bool => $options['startHistoryId'] === '100'))
            ->andReturn(new ListHistoryResponse(['historyId' => '300', 'history' => []]));
        $service->sync($user);
        $this->assertSame('300', $connection->fresh()->history_id);
    }

    public function test_failed_full_scan_continuation_retains_snapshot_and_resume_token(): void
    {
        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->for($user)->create([
            'history_id' => '50', 'gmail_sync_snapshot_history_id' => '100',
            'gmail_sync_page_token' => 'page-two',
        ]);
        $gmail = new Gmail(new Client);
        $gmail->users_threads = Mockery::mock(UsersThreads::class);
        $gmail->users_threads->shouldReceive('listUsersThreads')->once()
            ->with('me', ['maxResults' => 20, 'pageToken' => 'page-two'])
            ->andThrow(new RuntimeException('Failed continuation', 400));
        $gmail->users_history = Mockery::mock(UsersHistory::class);
        $gmail->users_history->shouldNotReceive('listUsersHistory');

        try {
            $this->service($gmail)->sync($user);
            $this->fail('Expected the page request to fail.');
        } catch (ProviderException $exception) {
            $this->assertSame(400, $exception->getPrevious()->getCode());
        }

        $this->assertSame('50', $connection->fresh()->history_id);
        $this->assertSame('100', $connection->fresh()->gmail_sync_snapshot_history_id);
        $this->assertSame('page-two', $connection->fresh()->gmail_sync_page_token);
    }

    public function test_changed_then_deleted_message_is_not_fetched(): void
    {
        [$user, $connection, $conversation, $message] = $this->localMailbox();
        $remote = new GmailMessage(['id' => 'message-one', 'threadId' => 'thread-one']);
        $history = new History([
            'labelsAdded' => [new HistoryLabelAdded(['message' => $remote])],
            'messagesDeleted' => [new HistoryMessageDeleted(['message' => $remote])],
        ]);
        $gmail = $this->historyGmail($history);
        $gmail->users_messages = Mockery::mock(UsersMessages::class);
        $gmail->users_messages->shouldNotReceive('get');

        $this->service($gmail)->sync($user);

        $this->assertModelMissing($message);
        $this->assertModelMissing($conversation);
        $this->assertSame('200', $connection->fresh()->history_id);
    }

    public function test_message_deleted_after_history_response_does_not_reset_sync(): void
    {
        [$user, $connection, $conversation, $message] = $this->localMailbox();
        $gmail = $this->historyGmail(new History([
            'labelsAdded' => [new HistoryLabelAdded(['message' => new GmailMessage(['id' => 'message-one'])])],
        ]));
        $gmail->users_messages = Mockery::mock(UsersMessages::class);
        $gmail->users_messages->shouldReceive('get')->once()->andThrow(new RuntimeException('Gone', 404));
        $gmail->users = Mockery::mock(Users::class);
        $gmail->users->shouldNotReceive('getProfile');

        $this->service($gmail)->sync($user);

        $this->assertModelMissing($message);
        $this->assertModelMissing($conversation);
        $this->assertSame('200', $connection->fresh()->history_id);
    }

    public function test_deleting_latest_message_recomputes_conversation_metadata(): void
    {
        [$user, $connection, $conversation, $message] = $this->localMailbox();
        $remaining = Message::factory()->for($conversation)->create([
            'provider' => 'gmail', 'provider_message_id' => 'older',
            'sent_at' => now()->subDay(), 'subject' => 'Remaining subject',
            'is_read' => false, 'metadata' => ['label_ids' => ['INBOX', 'UNREAD']],
        ]);
        $gmail = $this->historyGmail(new History([
            'messagesDeleted' => [new HistoryMessageDeleted(['message' => new GmailMessage(['id' => 'message-one'])])],
        ]));

        $this->service($gmail)->sync($user);

        $conversation->refresh();
        $this->assertSame('Remaining subject', $conversation->subject);
        $this->assertTrue($remaining->sent_at->equalTo($conversation->last_message_at));
        $this->assertFalse($conversation->is_read);
        $this->assertSame(['INBOX', 'UNREAD'], $conversation->labels);
    }

    public function test_thread_deleted_after_message_fetch_removes_only_owned_thread(): void
    {
        [$user, $connection, $conversation, $message] = $this->localMailbox();
        $otherConversation = Conversation::factory()->create([
            'provider' => 'gmail', 'provider_conversation_id' => 'thread-one',
        ]);
        $otherMessage = Message::factory()->for($otherConversation)->create([
            'provider' => 'gmail', 'provider_message_id' => 'message-one',
        ]);
        $remote = new GmailMessage(['id' => 'message-one', 'threadId' => 'thread-one']);
        $gmail = $this->historyGmail(new History([
            'labelsAdded' => [new HistoryLabelAdded(['message' => $remote])],
        ]));
        $gmail->users_messages = Mockery::mock(UsersMessages::class);
        $gmail->users_messages->shouldReceive('get')->once()->andReturn($remote);
        $gmail->users_threads = Mockery::mock(UsersThreads::class);
        $gmail->users_threads->shouldReceive('get')->once()->andThrow(new RuntimeException('Gone', 404));
        $gmail->users = Mockery::mock(Users::class);
        $gmail->users->shouldNotReceive('getProfile');

        $this->service($gmail)->sync($user);

        $this->assertModelMissing($message);
        $this->assertModelMissing($conversation);
        $this->assertModelExists($otherMessage);
        $this->assertModelExists($otherConversation);
        $this->assertSame('200', $connection->fresh()->history_id);
    }

    public function test_earlier_unread_message_keeps_thread_unread(): void
    {
        [$user, $connection, $conversation] = $this->localMailbox();
        $older = new GmailMessage([
            'id' => 'message-one', 'threadId' => 'thread-one',
            'internalDate' => '1726311600000', 'labelIds' => ['INBOX', 'UNREAD'],
        ]);
        $newer = new GmailMessage([
            'id' => 'message-two', 'threadId' => 'thread-one',
            'internalDate' => '1726311700000', 'labelIds' => ['INBOX'],
        ]);
        $gmail = $this->historyGmail(new History([
            'labelsAdded' => [new HistoryLabelAdded(['message' => $older])],
        ]));
        $gmail->users_messages = Mockery::mock(UsersMessages::class);
        $gmail->users_messages->shouldReceive('get')->once()->andReturn($older);
        $gmail->users_threads = Mockery::mock(UsersThreads::class);
        $gmail->users_threads->shouldReceive('get')->once()->andReturn(new Thread(['messages' => [$older, $newer]]));

        $this->service($gmail)->sync($user);

        $this->assertFalse($conversation->fresh()->is_read);
        $this->assertContains('UNREAD', $conversation->fresh()->labels);
    }

    private function localMailbox(): array
    {
        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->for($user)->create(['history_id' => '100']);
        $conversation = Conversation::factory()->for($user)->create([
            'provider' => 'gmail', 'provider_conversation_id' => 'thread-one',
            'is_read' => true, 'subject' => 'Latest subject', 'last_message_at' => now(),
            'labels' => ['INBOX', 'IMPORTANT'],
        ]);
        $message = Message::factory()->for($conversation)->create([
            'provider' => 'gmail', 'provider_message_id' => 'message-one', 'sent_at' => now(),
        ]);

        return [$user, $connection, $conversation, $message];
    }

    private function historyGmail(History $history): Gmail
    {
        $gmail = new Gmail(new Client);
        $gmail->users_history = Mockery::mock(UsersHistory::class);
        $gmail->users_history->shouldReceive('listUsersHistory')->once()
            ->andReturn(new ListHistoryResponse(['historyId' => '200', 'history' => [$history]]));

        return $gmail;
    }

    private function service(Gmail $gmail): GmailMessageSyncService
    {
        return new class($gmail) extends GmailMessageSyncService
        {
            public function __construct(private readonly Gmail $fakeGmail)
            {
                parent::__construct(new GoogleOAuthService(new GmailProviderErrorClassifier));
            }

            protected function gmailService(ProviderConnection $connection): Gmail
            {
                return $this->fakeGmail;
            }
        };
    }
}

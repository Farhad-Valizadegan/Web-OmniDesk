<?php

namespace App\Services;

use App\Contracts\MessageProviderConnector;
use App\Exceptions\ProviderErrorType;
use App\Exceptions\ProviderException;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProviderConnection;
use App\Models\User;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Illuminate\Support\Facades\DB;
use Throwable;

class GmailMessageSyncService implements MessageProviderConnector
{
    private const GMAIL_READONLY_SCOPE =
        'https://www.googleapis.com/auth/gmail.readonly';

    private const FULL_SYNC_THROTTLE_MICROSECONDS = 2000000;

    public function __construct(
        private readonly GoogleOAuthService $googleOAuthService,
        private readonly GmailProviderErrorClassifier $errorClassifier = new GmailProviderErrorClassifier,
    ) {}

    public function provider(): string
    {
        return 'gmail';
    }

    /**
     * Synchronize one lazy-loading page from the Gmail mailbox.
     *
     * @return array{threads_processed: int, messages_processed: int, has_more: bool}
     */
    public function syncNextPage(
        User $user,
        int $maxResults = 15,
    ): array {
        $connection = ProviderConnection::query()
            ->where('user_id', $user->id)
            ->where('provider', 'gmail')
            ->where('status', 'connected')
            ->latest('id')
            ->first();

        if (! $connection) {
            throw new ProviderException(
                'gmail',
                ProviderErrorType::Authorization,
                'A connected Gmail account is required before synchronizing.'
            );
        }

        if ($connection->gmail_sync_completed) {
            return [
                'threads_processed' => 0,
                'messages_processed' => 0,
                'has_more' => false,
            ];
        }

        $scopes = is_array($connection->scopes) ? $connection->scopes : [];

        if (! in_array(self::GMAIL_READONLY_SCOPE, $scopes, true)) {
            throw new ProviderException(
                'gmail',
                ProviderErrorType::Permission,
                'The Gmail read-only permission is required.'
            );
        }

        $gmail = $this->gmailService($connection);
        $profile = $this->providerCall(
            fn (): object => $gmail->users->getProfile('me')
        );

        if (
            ! is_string($connection->gmail_sync_snapshot_history_id)
            || $connection->gmail_sync_snapshot_history_id === ''
        ) {
            $snapshotHistoryId = $profile->getHistoryId();

            if (is_string($snapshotHistoryId) && $snapshotHistoryId !== '') {
                $connection->gmail_sync_snapshot_history_id = $snapshotHistoryId;
                $connection->save();
            }
        }

        $options = [
            'maxResults' => min(max($maxResults, 1), 100),
        ];

        if (
            is_string($connection->gmail_sync_page_token)
            && $connection->gmail_sync_page_token !== ''
        ) {
            $options['pageToken'] = $connection->gmail_sync_page_token;
        }

        $threadList = $this->providerCall(
            fn (): object => $gmail->users_threads->listUsersThreads(
                'me',
                $options
            )
        );

        $threadsProcessed = 0;
        $messagesProcessed = 0;

        foreach ($threadList->getThreads() ?? [] as $threadSummary) {
            $threadId = $threadSummary->getId();

            if (! is_string($threadId) || $threadId === '') {
                continue;
            }

            usleep(250000);

            $thread = $this->providerCall(
                fn (): object => $gmail->users_threads->get(
                    'me',
                    $threadId,
                    ['format' => 'full']
                )
            );
            $messages = $thread->getMessages() ?? [];

            if ($messages === []) {
                continue;
            }

            $messagesProcessed += $this->syncThread($user, $threadId, $messages);
            $threadsProcessed++;
        }

        $nextPageToken = $threadList->getNextPageToken();

        if (is_string($nextPageToken) && $nextPageToken !== '') {
            $connection->gmail_sync_page_token = $nextPageToken;
        } else {
            $connection->gmail_sync_page_token = null;
            $connection->gmail_sync_completed = true;

            if (
                is_string($connection->gmail_sync_snapshot_history_id)
                && $connection->gmail_sync_snapshot_history_id !== ''
            ) {
                $connection->history_id =
                    $connection->gmail_sync_snapshot_history_id;
            }

            $connection->gmail_sync_snapshot_history_id = null;
        }

        $connection->save();

        return [
            'threads_processed' => $threadsProcessed,
            'messages_processed' => $messagesProcessed,
            'has_more' => is_string($nextPageToken)
                && $nextPageToken !== '',
        ];
    }

    /**
     * Synchronize Gmail threads and messages for one application user.
     *
     * @return array{
     *     threads_processed: int,
     *     messages_processed: int,
     *     next_page_token: string|null
     * }
     */
    public function sync(
        User $user,
        int $maxResults = 20,
        ?string $pageToken = null,
    ): array {
        $connection = ProviderConnection::query()
            ->where('user_id', $user->id)
            ->where('provider', 'gmail')
            ->where('status', 'connected')
            ->latest('id')
            ->first();

        if (! $connection) {
            throw new ProviderException(
                'gmail',
                ProviderErrorType::Authorization,
                'A connected Gmail account is required before synchronizing.'
            );
        }

        $scopes = is_array($connection->scopes) ? $connection->scopes : [];

        if (! in_array(self::GMAIL_READONLY_SCOPE, $scopes, true)) {
            throw new ProviderException(
                'gmail',
                ProviderErrorType::Permission,
                'The Gmail read-only permission is required.'
            );
        }

        if (
            is_string($connection->history_id)
            && $connection->history_id !== ''
            && ($pageToken === null || $pageToken === '')
        ) {
            $gmail = $this->gmailService($connection);

            try {
                return $this->syncIncrementally($connection, $user, $gmail, $maxResults);
            } catch (ProviderException $exception) {
                if ((int) $exception->getPrevious()?->getCode() !== 404) {
                    throw $exception;
                }

                $connection->history_id = null;
                $connection->save();
            }
        }

        return $this->syncFull($user, $maxResults, $pageToken);
    }

    /**
     * Perform a full mailbox synchronization.
     *
     * @return array{
     *     threads_processed: int,
     *     messages_processed: int,
     *     next_page_token: string|null
     * }
     */
    private function syncFull(
        User $user,
        int $maxResults = 20,
        ?string $pageToken = null,
    ): array {
        $connection = ProviderConnection::query()
            ->where('user_id', $user->id)
            ->where('provider', 'gmail')
            ->where('status', 'connected')
            ->latest('id')
            ->first();

        if (! $connection) {
            throw new ProviderException(
                'gmail',
                ProviderErrorType::Authorization,
                'A connected Gmail account is required before synchronizing.'
            );
        }

        $scopes = is_array($connection->scopes)
            ? $connection->scopes
            : [];

        if (! in_array(self::GMAIL_READONLY_SCOPE, $scopes, true)) {
            throw new ProviderException(
                'gmail',
                ProviderErrorType::Permission,
                'The Gmail read-only permission is required.'
            );
        }

        $gmail = $this->gmailService($connection);
        $profile = $this->providerCall(
            fn (): object => $gmail->users->getProfile('me')
        );
        $currentHistoryId = $profile->getHistoryId();

        $options = [
            'maxResults' => min(max($maxResults, 1), 100),
        ];

        if ($pageToken !== null && $pageToken !== '') {
            $options['pageToken'] = $pageToken;
        }

        $threadList = $this->providerCall(
            fn (): object => $gmail->users_threads->listUsersThreads(
                'me',
                $options
            )
        );

        $threadsProcessed = 0;
        $messagesProcessed = 0;

        foreach ($threadList->getThreads() ?? [] as $threadSummary) {
            $threadId = $threadSummary->getId();

            if (! is_string($threadId) || $threadId === '') {
                continue;
            }

            usleep(self::FULL_SYNC_THROTTLE_MICROSECONDS);

            $thread = $this->providerCall(
                fn (): object => $gmail->users_threads->get(
                    'me',
                    $threadId,
                    ['format' => 'full']
                )
            );
            $messages = $thread->getMessages() ?? [];

            if ($messages === []) {
                continue;
            }

            $messagesProcessed += $this->syncThread($user, $threadId, $messages);
            $threadsProcessed++;
        }

        if (is_string($currentHistoryId) && $currentHistoryId !== '') {
            $connection->history_id = $currentHistoryId;
            $connection->save();
        }

        return [
            'threads_processed' => $threadsProcessed,
            'messages_processed' => $messagesProcessed,
            'next_page_token' => $threadList->getNextPageToken(),
        ];
    }

    /**
     * Synchronize only mailbox changes after the stored history cursor.
     *
     * @return array{threads_processed: int, messages_processed: int, next_page_token: string|null}
     */
    private function syncIncrementally(
        ProviderConnection $connection,
        User $user,
        Gmail $gmail,
        int $maxResults,
    ): array {
        $historyId = $connection->history_id;
        $nextPageToken = null;
        $messageIds = [];
        $deletedMessageIds = [];
        $latestHistoryId = null;

        do {
            $options = [
                'startHistoryId' => $historyId,
                'maxResults' => min(max($maxResults, 1) * 25, 500),
                'historyTypes' => [
                    'messageAdded', 'messageDeleted', 'labelAdded', 'labelRemoved',
                ],
            ];

            if (is_string($nextPageToken) && $nextPageToken !== '') {
                $options['pageToken'] = $nextPageToken;
            }

            $history = $this->providerCall(
                fn (): object => $gmail->users_history->listUsersHistory(
                    'me',
                    $options
                )
            );
            $latestHistoryId = $history->getHistoryId() ?: $latestHistoryId;

            foreach ($history->getHistory() ?? [] as $record) {
                foreach ($record->getMessagesAdded() ?? [] as $change) {
                    $messageId = $change->getMessage()?->getId();
                    if (is_string($messageId) && $messageId !== '') {
                        $messageIds[$messageId] = true;
                    }
                }

                foreach ([
                    ...($record->getLabelsAdded() ?? []),
                    ...($record->getLabelsRemoved() ?? []),
                ] as $change) {
                    $messageId = $change->getMessage()?->getId();
                    if (is_string($messageId) && $messageId !== '') {
                        $messageIds[$messageId] = true;
                    }
                }

                foreach ($record->getMessagesDeleted() ?? [] as $change) {
                    $messageId = $change->getMessage()?->getId();
                    if (is_string($messageId) && $messageId !== '') {
                        $deletedMessageIds[$messageId] = true;
                    }
                }
            }

            $nextPageToken = $history->getNextPageToken();
        } while (is_string($nextPageToken) && $nextPageToken !== '');

        $threadsProcessed = 0;
        $messagesProcessed = 0;

        foreach (array_keys($messageIds) as $messageId) {
            $gmailMessage = $this->providerCall(
                fn (): object => $gmail->users_messages->get(
                    'me',
                    $messageId,
                    ['format' => 'full']
                )
            );
            $threadId = $gmailMessage->getThreadId();

            if (! is_string($threadId) || $threadId === '') {
                continue;
            }

            $thread = $this->providerCall(
                fn (): object => $gmail->users_threads->get(
                    'me',
                    $threadId,
                    ['format' => 'full']
                )
            );
            $messages = $thread->getMessages() ?? [];

            if ($messages === []) {
                continue;
            }

            $messagesProcessed += $this->syncThread($user, $threadId, $messages);
            $threadsProcessed++;
        }

        $this->deleteMessages($user, array_keys($deletedMessageIds));

        if (is_string($latestHistoryId) && $latestHistoryId !== '') {
            $connection->history_id = $latestHistoryId;
            $connection->save();
        }

        return [
            'threads_processed' => $threadsProcessed,
            'messages_processed' => $messagesProcessed,
            'next_page_token' => null,
        ];
    }

    /**
     * Delete Gmail messages owned by the synchronizing user.
     *
     * @param  array<int, string>  $messageIds
     */
    protected function deleteMessages(User $user, array $messageIds): void
    {
        if ($messageIds === []) {
            return;
        }

        Message::query()
            ->where('provider', 'gmail')
            ->whereIn('provider_message_id', $messageIds)
            ->whereHas(
                'conversation',
                fn ($query) => $query->where('user_id', $user->id)
            )
            ->delete();
    }

    /**
     * Upsert one complete Gmail thread and its messages.
     *
     * @param  array<int, object>  $messages
     */
    private function syncThread(User $user, string $threadId, array $messages): int
    {
        return DB::transaction(function () use ($user, $threadId, $messages): int {
            $mappedMessages = array_map(
                fn (object $gmailMessage): array => $this->mapMessage($gmailMessage),
                $messages
            );

            usort(
                $mappedMessages,
                fn (array $left, array $right): int => $left['sent_at']->getTimestamp() <=> $right['sent_at']->getTimestamp()
            );

            $latest = $mappedMessages[array_key_last($mappedMessages)];
            $labelIds = array_values(array_unique(array_merge(
                ...array_map(
                    fn (array $message): array => $message['label_ids'],
                    $mappedMessages
                )
            )));

            $conversation = Conversation::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => 'gmail',
                    'provider_conversation_id' => $threadId,
                ],
                [
                    'subject' => $latest['subject'],
                    'status' => 'open',
                    'is_read' => ! in_array('UNREAD', $latest['label_ids'], true),
                    'last_message_at' => $latest['sent_at'],
                    'labels' => $labelIds,
                ]
            );

            foreach ($mappedMessages as $mappedMessage) {
                Message::updateOrCreate(
                    [
                        'conversation_id' => $conversation->id,
                        'provider' => 'gmail',
                        'provider_message_id' => $mappedMessage['provider_message_id'],
                    ],
                    [
                        'sender_name' => $mappedMessage['sender_name'],
                        'sender_address' => $mappedMessage['sender_address'],
                        'recipient_name' => $mappedMessage['recipient_name'],
                        'recipient_address' => $mappedMessage['recipient_address'],
                        'subject' => $mappedMessage['subject'],
                        'body' => $mappedMessage['body'],
                        'sent_at' => $mappedMessage['sent_at'],
                        'is_read' => $mappedMessage['is_read'],
                        'metadata' => [
                            'label_ids' => $mappedMessage['label_ids'],
                            'html_body' => $mappedMessage['body_html'],
                            'attachments' => $mappedMessage['attachments'],
                        ],
                    ]
                );
            }

            return count($mappedMessages);
        });
    }

    /**
     * Retry only failures that may recover without changing application state.
     *
     * @template T
     *
     * @param  callable(): T  $operation
     * @return T
     */
    protected function providerCall(callable $operation): mixed
    {
        try {
            return retry(
                3,
                $operation,
                500,
                fn (Throwable $exception): bool => $this
                    ->errorClassifier
                    ->classify($exception)
                    ->isRetryable()
            );
        } catch (Throwable $exception) {
            throw $this->errorClassifier->classify($exception);
        }
    }

    /**
     * Create an authenticated Gmail SDK service.
     */
    protected function gmailService(
        ProviderConnection $connection
    ): Gmail {
        return new Gmail(
            $this->authenticatedClient($connection)
        );
    }

    private function authenticatedClient(
        ProviderConnection $connection
    ): GoogleClient {
        $client = $this->googleOAuthService->createClient();
        $expiresAt = $connection->token_expires_at;

        if ($expiresAt === null || $expiresAt->isPast()) {
            $refreshToken = $connection->refresh_token;

            if (! is_string($refreshToken) || $refreshToken === '') {
                throw new ProviderException(
                    'gmail',
                    ProviderErrorType::Authorization,
                    'Gmail authorization is invalid or expired.'
                );
            }

            try {
                $token = $client
                    ->fetchAccessTokenWithRefreshToken($refreshToken);
            } catch (Throwable $exception) {
                throw $this->errorClassifier->classify($exception);
            }

            if (
                ! is_array($token)
                || isset($token['error'])
                || ! isset($token['access_token'])
                || ! is_string($token['access_token'])
                || $token['access_token'] === ''
            ) {
                throw new ProviderException(
                    'gmail',
                    ProviderErrorType::Authorization,
                    'Gmail authorization is invalid or expired.'
                );
            }

            $connection->access_token = $token['access_token'];
            $connection->token_expires_at = now()->addSeconds(
                max(1, (int) ($token['expires_in'] ?? 3600))
            );
            $connection->save();

            $client->setAccessToken($token);

            return $client;
        }

        $client->setAccessToken([
            'access_token' => $connection->access_token,
            'expires_at' => $expiresAt->getTimestamp(),
        ]);

        return $client;
    }

    /**
     * @return array{
     *     provider_message_id: string,
     *     sender_name: string,
     *     sender_address: string|null,
     *     recipient_name: string|null,
     *     recipient_address: string|null,
     *     subject: string|null,
     *     body: string,
     *     body_html: string|null,
     *     sent_at: Carbon,
     *     is_read: bool,
     *     label_ids: array<int, string>,
     *     attachments: array<int, array{
     *         filename: string,
     *         mime_type: string,
     *         size: int,
     *         attachment_id: string|null,
     *         inline: bool
     *     }>
     * }
     */
    private function mapMessage(object $gmailMessage): array
    {
        $payload = $gmailMessage->getPayload();
        $headers = $this->headers($payload?->getHeaders() ?? []);
        $labelIds = array_values(array_filter(
            $gmailMessage->getLabelIds() ?? [],
            'is_string'
        ));

        $from = $this->parseAddress($headers['from'] ?? null);
        $to = $this->parseAddress($headers['to'] ?? null);
        $bodyParts = $this->bodyParts($payload);

        $bodyHtml = trim($bodyParts['html']);

        $body = $bodyParts['plain'] !== ''
            ? $bodyParts['plain']
            : strip_tags($bodyHtml);

        $internalDate = $gmailMessage->getInternalDate();
        $sentAt = is_numeric($internalDate)
            ? Carbon::createFromTimestampMs((int) $internalDate)
            : $this->headerDate($headers['date'] ?? null);

        return [
            'provider_message_id' => (string) $gmailMessage->getId(),
            'sender_name' => $from['name']
                ?? $from['address']
                ?? 'Unknown sender',
            'sender_address' => $from['address'],
            'recipient_name' => $to['name'],
            'recipient_address' => $to['address'],
            'subject' => $headers['subject'] ?? null,
            'body' => trim($body),
            'body_html' => $bodyHtml !== '' ? $bodyHtml : null,
            'attachments' => $bodyParts['attachments'],
            'sent_at' => $sentAt,
            'is_read' => ! in_array('UNREAD', $labelIds, true),
            'label_ids' => $labelIds,
        ];
    }

    /**
     * @param  array<int, object>  $headers
     * @return array<string, string>
     */
    private function headers(array $headers): array
    {
        $result = [];

        foreach ($headers as $header) {
            $name = strtolower((string) $header->getName());
            $value = trim((string) $header->getValue());

            if ($name !== '' && $value !== '') {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    /**
     * @return array{
     *     plain: string,
     *     html: string,
     *     attachments: array<int, array{
     *         filename: string,
     *         mime_type: string,
     *         size: int,
     *         attachment_id: string|null,
     *         inline: bool
     *     }>
     * }
     */
    private function bodyParts(?object $part): array
    {
        if ($part === null) {
            return ['plain' => '', 'html' => '', 'attachments' => []];
        }

        $mimeType = strtolower((string) $part->getMimeType());
        $filename = trim((string) $part->getFilename());
        $partHeaders = $this->headers($part->getHeaders() ?? []);
        $body = $part->getBody();
        $data = $body?->getData();
        $attachmentId = $body?->getAttachmentId();
        $size = $body?->getSize();
        $isAttachment = $filename !== ''
            || (is_string($attachmentId) && $attachmentId !== '');

        $result = [
            'plain' => '',
            'html' => '',
            'attachments' => [],
        ];

        if (! $isAttachment) {
            $decoded = is_string($data)
                ? $this->decodeBase64Url($data)
                : '';

            $result['plain'] = $mimeType === 'text/plain' ? $decoded : '';
            $result['html'] = $mimeType === 'text/html' ? $decoded : '';
        }

        if ($isAttachment) {
            $result['attachments'][] = [
                'filename' => $filename === '' ? 'Unnamed attachment' : $filename,
                'mime_type' => $mimeType === ''
                    ? 'application/octet-stream'
                    : $mimeType,
                'size' => is_numeric($size) ? max(0, (int) $size) : 0,
                'attachment_id' => is_string($attachmentId)
                    && $attachmentId !== ''
                    ? $attachmentId
                    : null,
                'inline' => isset($partHeaders['content-id']),
            ];
        }

        foreach ($part->getParts() ?? [] as $child) {
            $childResult = $this->bodyParts($child);

            if ($result['plain'] === '') {
                $result['plain'] = $childResult['plain'];
            }

            if ($result['html'] === '') {
                $result['html'] = $childResult['html'];
            }

            $result['attachments'] = array_merge(
                $result['attachments'],
                $childResult['attachments']
            );
        }

        return $result;
    }

    private function decodeBase64Url(string $data): string
    {
        $data = strtr($data, '-_', '+/');
        $padding = strlen($data) % 4;

        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($data, true) ?: '';
    }

    /**
     * @return array{name: string|null, address: string|null}
     */
    private function parseAddress(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return ['name' => null, 'address' => null];
        }

        $value = trim($value);

        if (preg_match('/^(.*?)\\s*<([^>]+)>$/', $value, $matches)) {
            $name = trim(trim($matches[1]), '"');

            return [
                'name' => $name === '' ? null : $name,
                'address' => trim($matches[2]),
            ];
        }

        return [
            'name' => null,
            'address' => $value,
        ];
    }

    private function headerDate(?string $value): Carbon
    {
        if ($value === null || trim($value) === '') {
            return now();
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return now();
        }
    }
}

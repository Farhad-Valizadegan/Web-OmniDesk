<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProviderConnection;
use App\Models\User;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Exception as GoogleServiceException;
use Google\Service\Gmail;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class GmailMessageSyncService
{
    private const GMAIL_READONLY_SCOPE =
        'https://www.googleapis.com/auth/gmail.readonly';

    public function __construct(
        private readonly GoogleOAuthService $googleOAuthService
    ) {
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
            throw new RuntimeException(
                'A connected Gmail account is required before synchronizing.'
            );
        }

        $scopes = is_array($connection->scopes) ? $connection->scopes : [];

        if (! in_array(self::GMAIL_READONLY_SCOPE, $scopes, true)) {
            throw new RuntimeException(
                'The Gmail read-only scope is not granted for this connection.'
            );
        }

        if (
            is_string($connection->history_id)
            && $connection->history_id !== ''
            && ($pageToken === null || $pageToken === '')
        ) {
            $client = $this->authenticatedClient($connection);
            $gmail = new Gmail($client);

            try {
                return $this->syncIncrementally($connection, $user, $gmail, $maxResults);
            } catch (GoogleServiceException $exception) {
                if ((int) $exception->getCode() !== 404) {
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
            throw new RuntimeException(
                'A connected Gmail account is required before synchronizing.'
            );
        }

        $scopes = is_array($connection->scopes)
            ? $connection->scopes
            : [];

        if (! in_array(self::GMAIL_READONLY_SCOPE, $scopes, true)) {
            throw new RuntimeException(
                'The Gmail read-only scope is not granted for this connection.'
            );
        }

        $client = $this->authenticatedClient($connection);
        $gmail = new Gmail($client);
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

        foreach (array_keys($deletedMessageIds) as $messageId) {
            Message::query()
                ->where('provider', 'gmail')
                ->where('provider_message_id', $messageId)
                ->delete();
        }

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
     * Upsert one complete Gmail thread and its messages.
     *
     * @param array<int, object> $messages
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
                fn (array $left, array $right): int =>
                    $left['sent_at']->getTimestamp() <=> $right['sent_at']->getTimestamp()
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
                        'metadata' => ['label_ids' => $mappedMessage['label_ids']],
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
     * @param callable(): T $operation
     * @return T
     */
    private function providerCall(callable $operation): mixed
    {
        return retry(
            3,
            $operation,
            500,
            function (Throwable $exception): bool {
                $code = (int) $exception->getCode();

                return $code === 0
                    || $code === 408
                    || $code === 429
                    || $code >= 500;
            }
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
                throw new RuntimeException(
                    'The Gmail access token expired and no refresh token exists.'
                );
            }

            try {
                $token = $client
                    ->fetchAccessTokenWithRefreshToken($refreshToken);
            } catch (Throwable $exception) {
                throw new RuntimeException(
                    'The Gmail access token could not be refreshed.',
                    previous: $exception
                );
            }

            if (
                ! is_array($token)
                || isset($token['error'])
                || ! isset($token['access_token'])
                || ! is_string($token['access_token'])
                || $token['access_token'] === ''
            ) {
                throw new RuntimeException(
                    'The Gmail access token could not be refreshed.'
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
     *     sent_at: Carbon,
     *     is_read: bool,
     *     label_ids: array<int, string>
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

        $body = $bodyParts['plain'] !== ''
            ? $bodyParts['plain']
            : strip_tags($bodyParts['html']);

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
            'sent_at' => $sentAt,
            'is_read' => ! in_array('UNREAD', $labelIds, true),
            'label_ids' => $labelIds,
        ];
    }

    /**
     * @param array<int, object> $headers
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
     * @return array{plain: string, html: string}
     */
    private function bodyParts(?object $part): array
    {
        if ($part === null) {
            return ['plain' => '', 'html' => ''];
        }

        $mimeType = strtolower((string) $part->getMimeType());
        $body = $part->getBody();
        $data = $body?->getData();
        $decoded = is_string($data)
            ? $this->decodeBase64Url($data)
            : '';

        $result = [
            'plain' => $mimeType === 'text/plain' ? $decoded : '',
            'html' => $mimeType === 'text/html' ? $decoded : '',
        ];

        foreach ($part->getParts() ?? [] as $child) {
            $childResult = $this->bodyParts($child);

            if ($result['plain'] === '') {
                $result['plain'] = $childResult['plain'];
            }

            if ($result['html'] === '') {
                $result['html'] = $childResult['html'];
            }
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

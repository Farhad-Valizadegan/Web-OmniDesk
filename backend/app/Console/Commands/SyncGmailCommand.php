<?php

namespace App\Console\Commands;

use App\Models\ProviderConnection;
use App\Services\GmailMessageSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncGmailCommand extends Command
{
    protected $signature = 'gmail:sync {--max-results=20}';

    protected $description =
        'Synchronize connected Gmail accounts with the unified inbox';

    public function handle(GmailMessageSyncService $syncService): int
    {
        $maxResults = min(
            max((int) $this->option('max-results'), 1),
            100
        );

        $connections = ProviderConnection::query()
            ->with('user')
            ->where('provider', 'gmail')
            ->where('status', 'connected')
            ->get();

        if ($connections->isEmpty()) {
            $this->info('No connected Gmail accounts found.');

            return self::SUCCESS;
        }

        $exitCode = self::SUCCESS;

        foreach ($connections as $connection) {
            try {
                $threadsProcessed = 0;
                $messagesProcessed = 0;
                $pageToken = null;

                do {
                    $result = $pageToken === null
                        ? $syncService->sync($connection->user, $maxResults)
                        : $syncService->sync(
                            $connection->user,
                            $maxResults,
                            $pageToken
                        );

                    $threadsProcessed += $result['threads_processed'];
                    $messagesProcessed += $result['messages_processed'];
                    $pageToken = $result['next_page_token'];
                } while (is_string($pageToken) && $pageToken !== '');

                $this->info(sprintf(
                    'User %d: %d threads and %d messages synchronized.',
                    $connection->user_id,
                    $threadsProcessed,
                    $messagesProcessed,
                ));
            } catch (Throwable $exception) {
                $exitCode = self::FAILURE;

                Log::error('Scheduled Gmail synchronization failed.', [
                    'provider_connection_id' => $connection->id,
                    'user_id' => $connection->user_id,
                    'exception' => $exception,
                ]);

                $this->error(sprintf(
                    'User %d: synchronization failed.',
                    $connection->user_id
                ));
            }
        }

        return $exitCode;
    }
}

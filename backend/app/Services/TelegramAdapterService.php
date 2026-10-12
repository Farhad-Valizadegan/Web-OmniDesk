<?php

namespace App\Services;

use App\Exceptions\ProviderErrorType;
use App\Exceptions\ProviderException;
use App\Models\ProviderConnection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class TelegramAdapterService
{
    /**
     * Retrieve a bounded Telegram content sample from the local adapter.
     *
     * @return array<string, mixed>
     */
    public function fetchContentSample(
        ProviderConnection $connection,
        ?int $chatId = null,
        int $limit = 20,
        ?int $beforeMessageId = null,
        ?int $mediaMessageId = null,
    ): array {
        $operation = $this->operationFor($chatId, $beforeMessageId);
        $startedAt = microtime(true);

        Log::channel('telegram')->info('[telegram] request.start', [
            'operation' => $operation,
            'chat_id' => $chatId,
            'limit' => $limit,
            'before_message_id' => $beforeMessageId,
        ]);

        $pythonBinary = (string) config(
            'telegram.python_binary',
            'python'
        );

        $script = (string) config(
            'telegram.content_demo_script'
        );

        $runtimeDirectory = $this->runtimeDirectory($connection);

        $apiId = (string) config(
            'telegram.api_id'
        );

        $apiHash = (string) config(
            'telegram.api_hash'
        );

        $limit = max(1, min($limit, 20));
        $timeoutSeconds = max(
            5,
            (int) config('telegram.timeout_seconds', 60)
        );

        if ($script === '' || is_file($script) === false) {
            throw new ProviderException(
                'telegram',
                ProviderErrorType::InvalidResponse,
                'Telegram adapter script is not available.',
                null,
                $operation,
            );
        }

        if ($runtimeDirectory === '') {
            throw new ProviderException(
                'telegram',
                ProviderErrorType::InvalidResponse,
                'Telegram runtime directory is not configured.',
                null,
                $operation,
            );
        }

        if ($apiId === '' || $apiHash === '') {
            throw new ProviderException(
                'telegram',
                ProviderErrorType::Authorization,
                'Telegram API credentials are not configured.',
                null,
                $operation,
            );
        }

        // Pass executable arguments separately; Symfony manages platform process cleanup.
        $command = [$pythonBinary, $script];
        set_time_limit($timeoutSeconds + 15);

        // Preserve OS networking/runtime settings (notably SystemRoot on Windows).
        $environment = array_merge(getenv(), [
            'TELEGRAM_API_ID' => $apiId,
            'TELEGRAM_API_HASH' => $apiHash,
            'TELEGRAM_TDLIB_RUNTIME' => $runtimeDirectory,
            'TELEGRAM_DATABASE_KEY' => (string) $connection->telegram_database_key,
            'PYTHONIOENCODING' => 'utf-8',
            'TELEGRAM_MESSAGE_LIMIT' => (string) $limit,
            'TELEGRAM_CHAT_ID' => '',
            'TELEGRAM_BEFORE_MESSAGE_ID' => '',
            'TELEGRAM_MEDIA_MESSAGE_ID' => $mediaMessageId === null ? '' : (string) $mediaMessageId,
        ]);

        if ($beforeMessageId !== null) {
            $environment['TELEGRAM_BEFORE_MESSAGE_ID'] = (string) $beforeMessageId;
        }

        if ($chatId !== null) {
            $environment['TELEGRAM_CHAT_ID'] = (string) $chatId;
        }

        $lockHandle = null;

        try {
            $lockHandle = $this->acquireRuntimeLock(
                $runtimeDirectory,
                $operation,
            );

            // Symfony uses safe Windows output handling and enforces the deadline
            // even when the child produces no output.
            $process = new Process($command, base_path(), $environment, null, $timeoutSeconds);
            $timedOut = false;

            try {
                $process->run();
            } catch (ProcessTimedOutException) {
                $timedOut = true;
            }

            $result = [
                'stdout' => $process->getOutput(),
                'stderr' => $process->getErrorOutput(),
                'exit_code' => $process->getExitCode() ?? -1,
                'timed_out' => $timedOut,
            ];

            if ($result['timed_out']) {
                Log::channel('telegram')->error(
                    '[telegram] process.timeout',
                    [
                        'operation' => $operation,
                        'timeout_seconds' => $timeoutSeconds,
                        'duration_ms' => $this->durationMs($startedAt),
                    ],
                );

                throw new ProviderException(
                    'telegram',
                    ProviderErrorType::Temporary,
                    'Telegram request timed out.',
                    null,
                    $operation,
                );
            }

            $stdout = $result['stdout'];
            $stderr = $result['stderr'];
            $exitCode = $result['exit_code'];

            if ($exitCode !== 0) {
                $diagnostic = trim($stderr);

                Log::channel('telegram')->error(
                    '[telegram] process.exit_failure',
                    [
                        'operation' => $operation,
                        'exit_code' => $exitCode,
                        'stderr' => mb_substr($diagnostic, -2000),
                        'duration_ms' => $this->durationMs($startedAt),
                    ],
                );

                $requiresAuthorization = str_contains($diagnostic, 'Telegram session requires authorization.');
                $initializationTimedOut = str_contains($diagnostic, 'Telegram session initialization timed out.');
                $message = $requiresAuthorization
                    ? 'Telegram session requires authorization. Disconnect and reconnect Telegram in the workspace.'
                    : ($initializationTimedOut
                        ? 'Telegram session could not initialize. Check your connection and retry.'
                        : 'Telegram adapter request failed.');

                throw new ProviderException(
                    'telegram',
                    $requiresAuthorization ? ProviderErrorType::Authorization : ProviderErrorType::Temporary,
                    $message,
                    null,
                    $operation,
                );
            }

            if (trim($stdout) === '') {
                throw new ProviderException(
                    'telegram',
                    ProviderErrorType::InvalidResponse,
                    'Telegram adapter returned an empty response.',
                    null,
                    $operation,
                );
            }

            $decoded = json_decode($stdout, true);

            if (is_array($decoded) === false) {
                Log::channel('telegram')->error(
                    '[telegram] response.invalid_json',
                    [
                        'operation' => $operation,
                        'duration_ms' => $this->durationMs($startedAt),
                    ],
                );

                throw new ProviderException(
                    'telegram',
                    ProviderErrorType::InvalidResponse,
                    'Telegram adapter returned invalid JSON.',
                    null,
                    $operation,
                );
            }

            Log::channel('telegram')->info('[telegram] request.complete', [
                'operation' => $operation,
                'chat_count' => is_array($decoded['chats'] ?? null)
                    ? count($decoded['chats'])
                    : 0,
                'message_count' => is_array($decoded['messages'] ?? null)
                    ? count($decoded['messages'])
                    : 0,
                'has_more' => (bool) ($decoded['has_more'] ?? false),
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            return $decoded;
        } catch (ProviderException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::channel('telegram')->error(
                '[telegram] request.unexpected_failure',
                [
                    'operation' => $operation,
                    'exception' => $exception::class,
                    'error' => $exception->getMessage(),
                    'duration_ms' => $this->durationMs($startedAt),
                ],
            );

            throw new ProviderException(
                'telegram',
                ProviderErrorType::Temporary,
                'Telegram adapter request failed.',
                $exception,
                $operation,
            );
        } finally {
            if (is_resource($lockHandle)) {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
            }
        }
    }

    /** @return array{status: string} */
    public function authorize(ProviderConnection $connection, string $action, string $value = ''): array
    {
        set_time_limit(max(30, (int) config('telegram.timeout_seconds', 60) + 15));
        $script = (string) config('telegram.auth_worker_script');
        if ($script === '' || ! is_file($script)) {
            throw new ProviderException('telegram', ProviderErrorType::InvalidResponse, 'Telegram authorization adapter is unavailable.', null, 'authorization');
        }

        $runtimeDirectory = $this->runtimeDirectory($connection);
        $lockHandle = $this->acquireRuntimeLock($runtimeDirectory, 'authorization');

        try {
            $environment = array_merge(getenv(), [
                'TELEGRAM_API_ID' => (string) config('telegram.api_id'),
                'TELEGRAM_API_HASH' => (string) config('telegram.api_hash'),
                'TELEGRAM_TDLIB_RUNTIME' => $runtimeDirectory,
                'TELEGRAM_DATABASE_KEY' => (string) $connection->telegram_database_key,
                'PYTHONIOENCODING' => 'utf-8',
            ]);
            $ipc = $runtimeDirectory.DIRECTORY_SEPARATOR.'auth-ipc';
            $this->ensureAuthorizationWorker($script, $runtimeDirectory, $ipc, $environment);
            $id = bin2hex(random_bytes(16));
            $commands = $ipc.DIRECTORY_SEPARATOR.'commands';
            $responses = $ipc.DIRECTORY_SEPARATOR.'responses';
            $temporary = $commands.DIRECTORY_SEPARATOR.$id.'.tmp';
            $command = $commands.DIRECTORY_SEPARATOR.$id.'.json';
            file_put_contents($temporary, json_encode(['id' => $id, 'action' => $action, 'value' => $value], JSON_THROW_ON_ERROR), LOCK_EX);
            rename($temporary, $command);
            $response = $responses.DIRECTORY_SEPARATOR.$id.'.json';
            $deadline = microtime(true) + max(10, (int) config('telegram.timeout_seconds', 60));
            while (microtime(true) < $deadline) {
                if (is_file($response)) {
                    $decoded = json_decode((string) file_get_contents($response), true);
                    unlink($response);
                    if (! is_array($decoded) || ! is_string($decoded['status'] ?? null) || isset($decoded['error'])) {
                        throw new ProviderException('telegram', ProviderErrorType::Authorization, (string) ($decoded['error'] ?? 'Telegram authorization could not be completed.'), null, 'authorization');
                    }

                    if ($action === 'shutdown') {
                        usleep(250000);
                    }

                    return ['status' => $decoded['status']];
                }
                usleep(100000);
            }
            @unlink($command);
            throw new ProviderException('telegram', ProviderErrorType::Temporary, 'Telegram authorization timed out.', null, 'authorization');
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    private function ensureAuthorizationWorker(string $script, string $runtimeDirectory, string $ipc, array $environment): void
    {
        $state = $ipc.DIRECTORY_SEPARATOR.'state.json';
        if (is_file($state) && filemtime($state) >= time() - 3) {
            return;
        }
        foreach ([$ipc, $ipc.DIRECTORY_SEPARATOR.'commands', $ipc.DIRECTORY_SEPARATOR.'responses'] as $directory) {
            if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
                throw new ProviderException('telegram', ProviderErrorType::Temporary, 'Telegram authorization runtime could not be created.', null, 'authorization');
            }
        }
        $this->purgeStaleAuthorizationIpc($ipc);
        $log = $runtimeDirectory.DIRECTORY_SEPARATOR.'auth-worker.log';
        $base = $this->buildCommand((string) config('telegram.python_binary', 'python'), $script);
        $command = PHP_OS_FAMILY === 'Windows'
            ? 'cmd /c start "" /B '.$base.' >> "'.str_replace('"', '\\"', $log).'" 2>&1'
            : 'nohup '.$base.' >> '.escapeshellarg($log).' 2>&1 &';
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), $environment);
        if (! is_resource($process)) {
            throw new ProviderException('telegram', ProviderErrorType::Temporary, 'Telegram authorization worker could not be started.', null, 'authorization');
        }
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }
        proc_close($process);
        $deadline = microtime(true) + 10;
        while (microtime(true) < $deadline) {
            if (is_file($state) && filemtime($state) >= time() - 3) {
                $payload = json_decode((string) file_get_contents($state), true);
                if (is_array($payload) && ($payload['status'] ?? 'pending') !== 'pending') {
                    return;
                }
            }
            usleep(100000);
        }
        throw new ProviderException('telegram', ProviderErrorType::Temporary, 'Telegram authorization worker did not become ready.', null, 'authorization');
    }

    public function deleteRuntime(ProviderConnection $connection): void
    {
        $directory = $this->runtimeDirectory($connection);
        $root = realpath((string) config('telegram.runtime_directory'));

        if (! is_dir($directory)) {
            return;
        }

        $resolved = realpath($directory);
        if ($root === false || $resolved === false) {
            throw new ProviderException('telegram', ProviderErrorType::Temporary, 'Telegram session cleanup could not resolve its private runtime.', null, 'disconnect');
        }

        $comparisonRoot = PHP_OS_FAMILY === 'Windows' ? strtolower($root) : $root;
        $comparisonDirectory = PHP_OS_FAMILY === 'Windows' ? strtolower($resolved) : $resolved;
        if (! str_starts_with($comparisonDirectory, $comparisonRoot.DIRECTORY_SEPARATOR)) {
            throw new ProviderException('telegram', ProviderErrorType::InvalidResponse, 'Telegram session cleanup refused an unsafe path.', null, 'disconnect');
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($resolved, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $path = $item->getPathname();
            if ($item->isDir() && ! $item->isLink()) {
                if (! @rmdir($path) && is_dir($path)) {
                    throw new ProviderException('telegram', ProviderErrorType::Temporary, 'Telegram session cleanup could not remove a private directory.', null, 'disconnect');
                }
            } elseif (! @unlink($path) && file_exists($path)) {
                @chmod($path, 0600);
                if (! @unlink($path) && file_exists($path)) {
                    throw new ProviderException('telegram', ProviderErrorType::Temporary, 'Telegram session cleanup could not remove private data.', null, 'disconnect');
                }
            }
        }

        if (! @rmdir($resolved) && is_dir($resolved)) {
            throw new ProviderException('telegram', ProviderErrorType::Temporary, 'Telegram session cleanup could not finish.', null, 'disconnect');
        }
    }

    private function purgeStaleAuthorizationIpc(string $ipc): void
    {
        foreach (['commands', 'responses'] as $kind) {
            $directory = $ipc.DIRECTORY_SEPARATOR.$kind;
            foreach (glob($directory.DIRECTORY_SEPARATOR.'*') ?: [] as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }

        foreach (['state.json', 'worker.pid'] as $name) {
            $path = $ipc.DIRECTORY_SEPARATOR.$name;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    public function runtimeDirectory(ProviderConnection $connection): string
    {
        $root = rtrim((string) config('telegram.runtime_directory'), DIRECTORY_SEPARATOR);

        return $root.DIRECTORY_SEPARATOR.'connection-'.$connection->getKey();
    }

    /**
     * @return resource
     */
    private function acquireRuntimeLock(
        string $runtimeDirectory,
        string $operation,
    ): mixed {
        if (
            is_dir($runtimeDirectory) === false
            && mkdir($runtimeDirectory, 0775, true) === false
            && is_dir($runtimeDirectory) === false
        ) {
            throw new ProviderException(
                'telegram',
                ProviderErrorType::Temporary,
                'Telegram runtime directory could not be created.',
                null,
                $operation,
            );
        }

        $lockPath = $runtimeDirectory
            .DIRECTORY_SEPARATOR
            .'.telegram-adapter.lock';
        $handle = @fopen($lockPath, 'c');

        if ($handle === false) {
            throw new ProviderException(
                'telegram',
                ProviderErrorType::Temporary,
                'Telegram adapter lock could not be opened.',
                null,
                $operation,
            );
        }

        $deadline = microtime(true) + 10.0;
        $locked = false;

        do {
            $locked = flock($handle, LOCK_EX | LOCK_NB);

            if ($locked) {
                break;
            }

            usleep(100000);
        } while (microtime(true) < $deadline);

        if ($locked === false) {
            fclose($handle);

            Log::channel('telegram')->warning(
                '[telegram] request.busy',
                [
                    'operation' => $operation,
                ],
            );

            throw new ProviderException(
                'telegram',
                ProviderErrorType::Temporary,
                'Another Telegram request is still being processed.',
                null,
                $operation,
            );
        }

        return $handle;
    }

    private function operationFor(
        ?int $chatId,
        ?int $beforeMessageId,
    ): string {
        if ($chatId === null) {
            return 'chat_discovery';
        }

        return $beforeMessageId === null
            ? 'history_initial'
            : 'history_pagination';
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private function buildCommand(
        string $pythonBinary,
        string $script,
    ): string {
        if (PHP_OS_FAMILY === 'Windows') {
            return sprintf(
                '"%s" "%s"',
                str_replace('"', '\\"', $pythonBinary),
                str_replace('"', '\\"', $script),
            );
        }

        return sprintf(
            '%s %s',
            escapeshellarg($pythonBinary),
            escapeshellarg($script),
        );
    }
}

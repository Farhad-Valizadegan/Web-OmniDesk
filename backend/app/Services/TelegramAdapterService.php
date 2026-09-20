<?php

namespace App\Services;

use App\Exceptions\ProviderErrorType;
use App\Exceptions\ProviderException;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramAdapterService
{
    /**
     * Retrieve a bounded Telegram content sample from the local adapter.
     *
     * @return array<string, mixed>
     */
    public function fetchContentSample(
        ?int $chatId = null,
        int $limit = 20,
        ?int $beforeMessageId = null,
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

        $runtimeDirectory = (string) config(
            'telegram.runtime_directory'
        );

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

        $command = $this->buildCommand(
            $pythonBinary,
            $script
        );

        $environment = [
            'TELEGRAM_API_ID' => $apiId,
            'TELEGRAM_API_HASH' => $apiHash,
            'TELEGRAM_TDLIB_RUNTIME' => $runtimeDirectory,
            'PYTHONIOENCODING' => 'utf-8',
            'TELEGRAM_MESSAGE_LIMIT' => (string) $limit,
        ];

        if ($beforeMessageId !== null) {
            $environment['TELEGRAM_BEFORE_MESSAGE_ID'] = (string) $beforeMessageId;
        }

        if ($chatId !== null) {
            $environment['TELEGRAM_CHAT_ID'] = (string) $chatId;
        }

        $descriptors = [
            0 => [
                'pipe',
                'r',
            ],
            1 => [
                'pipe',
                'w',
            ],
            2 => [
                'pipe',
                'w',
            ],
        ];

        $lockHandle = null;

        try {
            $lockHandle = $this->acquireRuntimeLock(
                $runtimeDirectory,
                $operation,
            );

            $process = proc_open(
                $command,
                $descriptors,
                $pipes,
                base_path(),
                $environment
            );

            if (is_resource($process) === false) {
                throw new ProviderException(
                    'telegram',
                    ProviderErrorType::Temporary,
                    'Telegram adapter process could not be started.',
                    null,
                    $operation,
                );
            }

            fclose($pipes[0]);

            $result = $this->collectProcessOutput(
                $process,
                $pipes,
                $timeoutSeconds,
            );

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

                throw new ProviderException(
                    'telegram',
                    ProviderErrorType::Temporary,
                    'Telegram adapter request failed.',
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

    /**
     * @param resource $process
     * @param array<int, resource> $pipes
     * @return array{stdout: string, stderr: string, exit_code: int, timed_out: bool}
     */
    private function collectProcessOutput(
        mixed $process,
        array $pipes,
        int $timeoutSeconds,
    ): array {
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $timedOut = false;
        $exitCode = -1;
        $deadline = microtime(true) + $timeoutSeconds;

        while (true) {
            $stdout .= $this->readPipe($pipes[1]);
            $stderr .= $this->readPipe($pipes[2]);

            $status = proc_get_status($process);

            if (($status['running'] ?? false) === false) {
                $exitCode = (int) ($status['exitcode'] ?? -1);
                break;
            }

            if (microtime(true) >= $deadline) {
                $timedOut = true;
                proc_terminate($process);
                break;
            }

            usleep(50000);
        }

        $drainDeadline = microtime(true) + 0.75;

        do {
            $stdout .= $this->readPipe($pipes[1]);
            $stderr .= $this->readPipe($pipes[2]);

            if (
                feof($pipes[1])
                && feof($pipes[2])
            ) {
                break;
            }

            usleep(25000);
        } while (microtime(true) < $drainDeadline);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $closedExitCode = (int) proc_close($process);

        if ($exitCode < 0) {
            $exitCode = $closedExitCode;
        }

        return [
            'stdout' => $stdout,
            'stderr' => $stderr,
            'exit_code' => $exitCode,
            'timed_out' => $timedOut,
        ];
    }

    /**
     * @param resource $pipe
     */
    private function readPipe(mixed $pipe): string
    {
        $content = stream_get_contents($pipe);

        return is_string($content) ? $content : '';
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

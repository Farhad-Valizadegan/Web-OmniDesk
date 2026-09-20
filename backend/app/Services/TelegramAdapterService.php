<?php

namespace App\Services;

use RuntimeException;

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
    ): array
    {
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

        $timeoutSeconds = (int) config(
            'telegram.timeout_seconds',
            120
        );

        if ($script === '' || !is_file($script)) {
            throw new RuntimeException(
                'Telegram adapter script is not available.'
            );
        }

        if ($runtimeDirectory === '') {
            throw new RuntimeException(
                'Telegram runtime directory is not configured.'
            );
        }

        if ($apiId === '' || $apiHash === '') {
            throw new RuntimeException(
                'Telegram API credentials are not configured.'
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

        $process = proc_open(
            $command,
            $descriptors,
            $pipes,
            base_path(),
            $environment
        );

        if (!is_resource($process)) {
            throw new RuntimeException(
                'Telegram adapter process could not be started.'
            );
        }

        fclose($pipes[0]);

        stream_set_timeout(
            $pipes[1],
            $timeoutSeconds
        );

        stream_set_timeout(
            $pipes[2],
            $timeoutSeconds
        );

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $diagnostic = trim((string) $stderr);

            logger()->error('Telegram adapter process failed.', [
                'exit_code' => $exitCode,
                'stderr' => mb_substr($diagnostic, -2000),
            ]);

            throw new RuntimeException(
                'Telegram adapter request failed.'
            );
        }

        if (! is_string($stdout) || trim($stdout) === '') {
            throw new RuntimeException(
                'Telegram adapter returned an empty response.'
            );
        }

        $decoded = json_decode(
            $stdout,
            true
        );

        if (! is_array($decoded)) {
            throw new RuntimeException(
                'Telegram adapter returned invalid JSON.'
            );
        }

        return $decoded;
    }

    private function buildCommand(
        string $pythonBinary,
        string $script,
    ): string {
        if (PHP_OS_FAMILY === 'Windows') {
            return sprintf(
                '"%s" "%s"',
                str_replace('"', '\"', $pythonBinary),
                str_replace('"', '\"', $script),
            );
        }

        return sprintf(
            '%s %s',
            escapeshellarg($pythonBinary),
            escapeshellarg($script),
        );
    }
}

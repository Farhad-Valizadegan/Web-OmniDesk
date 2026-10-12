<?php

namespace Tests\Feature;

use App\Exceptions\ProviderException;
use App\Models\ProviderConnection;
use App\Services\TelegramAdapterService;
use Tests\TestCase;

class TelegramAdapterEnvironmentTest extends TestCase
{
    public function test_adapter_inherits_environment_without_reusing_request_arguments(): void
    {
        $runtime = sys_get_temp_dir().DIRECTORY_SEPARATOR.'omnidesk-adapter-'.uniqid();
        $keys = ['OMNIDESK_ADAPTER_TEST_SETTING', 'TELEGRAM_CHAT_ID', 'TELEGRAM_BEFORE_MESSAGE_ID'];
        $original = array_combine($keys, array_map('getenv', $keys));
        putenv('OMNIDESK_ADAPTER_TEST_SETTING=network-runtime-setting');
        putenv('TELEGRAM_CHAT_ID=999');
        putenv('TELEGRAM_BEFORE_MESSAGE_ID=888');

        config([
            'telegram.python_binary' => PHP_BINARY,
            'telegram.content_demo_script' => base_path('tests/Fixtures/telegram_environment.php'),
            'telegram.runtime_directory' => $runtime,
            'telegram.api_id' => 'test-id',
            'telegram.api_hash' => 'test-hash',
        ]);

        try {
            $adapter = app(TelegramAdapterService::class);
            $connection = new ProviderConnection(['telegram_database_key' => 'test-database-key']);
            $connection->id = 123;
            $discovery = $adapter->fetchContentSample($connection);
            $this->assertSame('network-runtime-setting', $discovery['inherited_setting']);
            $this->assertContains($discovery['chat_id'], ['', false]);
            $this->assertContains($discovery['cursor'], ['', false]);

            $history = $adapter->fetchContentSample($connection, -100123, 5, 42);
            $this->assertSame('-100123', $history['chat_id']);
            $this->assertSame('42', $history['cursor']);
            $this->assertSame('5', $history['limit']);
        } finally {
            foreach ($original as $key => $value) {
                putenv($value === false ? $key : $key.'='.$value);
            }
            $connectionRuntime = $runtime.DIRECTORY_SEPARATOR.'connection-123';
            if (is_file($connectionRuntime.DIRECTORY_SEPARATOR.'.telegram-adapter.lock')) {
                unlink($connectionRuntime.DIRECTORY_SEPARATOR.'.telegram-adapter.lock');
            }
            if (is_dir($connectionRuntime)) {
                rmdir($connectionRuntime);
            }
            if (is_dir($runtime)) {
                rmdir($runtime);
            }
        }
    }

    public function test_silent_worker_times_out_and_releases_its_resources(): void
    {
        $runtime = sys_get_temp_dir().DIRECTORY_SEPARATOR.'omnidesk-timeout-'.uniqid();
        mkdir($runtime);
        $workerLock = $runtime.DIRECTORY_SEPARATOR.'worker.lock';
        $previous = getenv('OMNIDESK_ADAPTER_TEST_LOCK');
        putenv('OMNIDESK_ADAPTER_TEST_LOCK='.$workerLock);
        config([
            'telegram.python_binary' => PHP_BINARY,
            'telegram.content_demo_script' => base_path('tests/Fixtures/telegram_silent_worker.php'),
            'telegram.runtime_directory' => $runtime,
            'telegram.api_id' => 'test-id',
            'telegram.api_hash' => 'test-hash',
            'telegram.timeout_seconds' => 5,
        ]);
        $connection = new ProviderConnection(['telegram_database_key' => 'test-database-key']);
        $connection->id = 321;
        $started = microtime(true);

        try {
            try {
                app(TelegramAdapterService::class)->fetchContentSample($connection);
                $this->fail('The silent worker must time out before completing.');
            } catch (ProviderException $exception) {
                $this->assertSame('Telegram request timed out.', $exception->getMessage());
            }
            $this->assertLessThan(9, microtime(true) - $started);
            $this->assertFileExists($workerLock.'.started');
            $handle = fopen($workerLock, 'c');
            try {
                $this->assertTrue(flock($handle, LOCK_EX | LOCK_NB), 'The terminated worker must not retain its lock.');
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        } finally {
            putenv($previous === false ? 'OMNIDESK_ADAPTER_TEST_LOCK' : 'OMNIDESK_ADAPTER_TEST_LOCK='.$previous);
            foreach ([$workerLock, $workerLock.'.started', $runtime.DIRECTORY_SEPARATOR.'connection-321'.DIRECTORY_SEPARATOR.'.telegram-adapter.lock'] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            $connectionRuntime = $runtime.DIRECTORY_SEPARATOR.'connection-321';
            if (is_dir($connectionRuntime)) {
                rmdir($connectionRuntime);
            }
            rmdir($runtime);
        }
    }
}

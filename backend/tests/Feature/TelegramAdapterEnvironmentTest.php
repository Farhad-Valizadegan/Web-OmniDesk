<?php

namespace Tests\Feature;

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
            $discovery = $adapter->fetchContentSample();
            $this->assertSame('network-runtime-setting', $discovery['inherited_setting']);
            $this->assertContains($discovery['chat_id'], ['', false]);
            $this->assertContains($discovery['cursor'], ['', false]);

            $history = $adapter->fetchContentSample(-100123, 5, 42);
            $this->assertSame('-100123', $history['chat_id']);
            $this->assertSame('42', $history['cursor']);
            $this->assertSame('5', $history['limit']);
        } finally {
            foreach ($original as $key => $value) {
                putenv($value === false ? $key : $key.'='.$value);
            }
            if (is_file($runtime.DIRECTORY_SEPARATOR.'.telegram-adapter.lock')) {
                unlink($runtime.DIRECTORY_SEPARATOR.'.telegram-adapter.lock');
            }
            if (is_dir($runtime)) {
                rmdir($runtime);
            }
        }
    }
}

<?php

return [
    'python_binary' => env(
        'TELEGRAM_PYTHON_BINARY',
        'python'
    ),

    'content_demo_script' => base_path(
        '../spikes/telegram-tdlib/adapter/telegram_content_demo.py'
    ),

    'auth_script' => base_path(
        '../spikes/telegram-tdlib/adapter/telegram_auth.py'
    ),

    'auth_worker_script' => base_path(
        '../spikes/telegram-tdlib/adapter/telegram_auth_worker.py'
    ),

    'api_id' => env('TELEGRAM_API_ID'),

    'api_hash' => env('TELEGRAM_API_HASH'),

    'runtime_directory' => env(
        'TELEGRAM_TDLIB_RUNTIME',
        storage_path('app/private/telegram')
    ),

    'timeout_seconds' => (int) env(
        'TELEGRAM_ADAPTER_TIMEOUT_SECONDS',
        60
    ),
];

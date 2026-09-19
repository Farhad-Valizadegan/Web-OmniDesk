<?php

return [
    'python_binary' => env(
        'TELEGRAM_PYTHON_BINARY',
        'python'
    ),

    'content_demo_script' => base_path(
        '../spikes/telegram-tdlib/adapter/telegram_content_demo.py'
    ),

    'api_id' => env('TELEGRAM_API_ID'),

    'api_hash' => env('TELEGRAM_API_HASH'),

    'runtime_directory' => env(
        'TELEGRAM_TDLIB_RUNTIME',
        base_path('../spikes/telegram-tdlib/runtime')
    ),

    'timeout_seconds' => (int) env(
        'TELEGRAM_ADAPTER_TIMEOUT_SECONDS',
        60
    ),
];
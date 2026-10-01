<?php

echo json_encode([
    'inherited_setting' => getenv('OMNIDESK_ADAPTER_TEST_SETTING'),
    'chat_id' => getenv('TELEGRAM_CHAT_ID'),
    'cursor' => getenv('TELEGRAM_BEFORE_MESSAGE_ID'),
    'limit' => getenv('TELEGRAM_MESSAGE_LIMIT'),
]);

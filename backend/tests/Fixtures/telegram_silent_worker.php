<?php

$path = getenv('OMNIDESK_ADAPTER_TEST_LOCK');
$lock = fopen($path, 'c');
flock($lock, LOCK_EX);
file_put_contents($path.'.started', 'started');
sleep(20);
echo json_encode(['finished' => true]);

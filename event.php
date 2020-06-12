<?php
require_once __DIR__ . '/DailyTip.php';

$input = @json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    return;
}

file_put_contents(__DIR__ . '/events/' . time() . '.txt', json_encode([$input]));

(new DailyTip())->getRandomText();
(new Slack())->event($input);
<?php
require_once __DIR__ . '/Slack.php';

$input = @json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    return;
}

file_put_contents(__DIR__ . '/events/' . time() . '.txt', json_encode($input));

(new Slack())->handleEvent($input);
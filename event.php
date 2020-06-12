<?php
$input = @json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    return;
}

file_put_contents(__DIR__ . '/events/' . time() . '.txt', json_encode([$input]));

$secret = @json_decode(file_get_contents(__DIR__ . '/../../secret.json'), true);
$slackToken = $secret['slack_token'] ?? null;
if ($slackToken === null) {
    return;
}
if ($slackToken !== @$input['token']) {
    return;
}
echo @$input['challenge'];

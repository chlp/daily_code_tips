<?php
if (PHP_SAPI !== 'cli') {
    echo 'use cli';
    return;
}

$secret = @json_decode(file_get_contents(__DIR__ . '/../../secret.json'), true);
$slackService = $secret['slack_service'] ?? null;
if ($slackService === null) {
    return;
}
$tips = json_decode(file_get_contents(__DIR__ . '/tips.json'), true);
$tip = $tips[array_rand($tips)];
$text = "*{$tip['title']}*\r\n";
foreach ($tip['points'] as $point) {
    $text .= "* {$point}\r\n";
}
$text .= "_{$tip['link']}_";
$url = 'https://hooks.slack.com/services/' . $slackService;
$result = file_get_contents($url, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-type: application/json',
        'content' => json_encode(['text' => $text])
    ]
]));
if ($result !== 'ok') {
    echo "error {$result}";
}

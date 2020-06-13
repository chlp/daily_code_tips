<?php
if (PHP_SAPI !== 'cli') {
    echo 'use cli';
    return;
}

require_once __DIR__ . '/DailyTip.php';
require_once __DIR__ . '/Slack.php';

$todayTip = (new DailyTip())->getRandomText();
$slack = new Slack();
foreach ($slack->getAppConversations() as $conversationId) {
    $slack->post($conversationId, $todayTip);
}
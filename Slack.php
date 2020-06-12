<?php

class Slack
{
    private $eventToken;
    private $botToken;

    public function __construct()
    {
        $secret = @json_decode(file_get_contents(__DIR__ . '/../../secret.json'), true);
        $slackService = $secret['slack_service'] ?? null;
        if ($slackService === null) {
            return;
        }
        $this->botToken = $secret['slack_bot_token'];
        $this->eventToken = $secret['slack_event_token'];
    }

    public function event(array $input)
    {
        file_put_contents(__DIR__ . '/events/' . time() . '.txt', json_encode([$input]));
        if ($this->eventToken !== @$input['token']) {
            return;
        }
        echo @$input['challenge'];
        switch (@$input['event']['type']) {
            case 'app_mention':
                $channel = (string)@$input['event']['channel'];
                $this->post($channel, "Дратути");
                break;
            default:
                // not action on event
        }
    }

    public function post(string $channel, string $text)
    {
        $data = [
            'token' => $this->botToken,
            'channel' => $channel,
            'text' => $text,
        ];
        $url = 'https://slack.com/api/chat.postMessage?' . http_build_query($data);;
        file_get_contents($url);
    }
}
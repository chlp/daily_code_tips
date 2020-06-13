<?php

class Slack
{
    private $eventToken;
    private $botToken;

    private const EVENT_APP_MENTION = 'app_mention';
    private const EVENT_MESSAGE = 'message';

    private const API_URL_PREFIX = 'https://slack.com/api/';

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
            case self::EVENT_APP_MENTION:
            case self::EVENT_MESSAGE:
                if (@(string)@$input['event']['client_msg_id'] === '') {
                    // будем реагировать только на то, что люди пишут
                    // если убрать, то будем реагировать на свои собственные сообщения и будет бесконечный цикл
                    return;
                }
                $channel = (string)@$input['event']['channel'];
                $this->post(
                    $channel,
                    <<<EOD
Привет 👋
Я бот 🤖 ежедневных советов по разработке.
Добавь меня в канал и каждый будний день в 9 утра буду присылать очередной совет 🤓.
Поправить или добавить советы можно здесь: https://github.com/chlp/daily_code_tips/blob/master/tips.json
EOD
                );
                break;
            case 'another_one_event':
                break;
            default:
                // not action on event
        }
    }

    /**
     * @return string[]
     */
    public function getAppConversations(): array
    {
        $conversationsId = [];
        $iteration = 0;
        $maxIterations = 10;
        $conversationsLimitPerRequest = 1000;
        $cursor = '';
        while (true) {
            $url = self::API_URL_PREFIX . "conversations.list?token={$this->botToken}&limit={$conversationsLimitPerRequest}&exclude_archived=true&cursor={$cursor}";
            $conversations = @json_decode(file_get_contents($url), true);
            if (!is_array($conversations) || !$conversations['ok']) {
                return [];
            }
            foreach ($conversations['channels'] as $channel) {
                if (@$channel['is_member'] && is_string($channel['id'])) {
                    $conversationsId[] = $channel['id'];
                }
            }
            $cursor = (string)@$conversations['response_metadata']['next_cursor'];
            if (++$iteration >= $maxIterations || $cursor === '') {
                break;
            }
        }
        return $conversationsId;
    }

    public function post(string $channel, string $text): void
    {
        $data = [
            'token' => $this->botToken,
            'channel' => $channel,
            'text' => $text,
        ];
        $url = self::API_URL_PREFIX . 'chat.postMessage?' . http_build_query($data);
        file_get_contents($url);
    }
}
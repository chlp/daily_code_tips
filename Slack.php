<?php

class Slack
{
    private $eventToken;
    private $botToken;

    private const EVENT_APP_MENTION = 'app_mention';
    private const EVENT_MESSAGE = 'message';

    private const API_URL_PREFIX = 'https://slack.com/api/';

    private const SECRET_CONFIG_FILE = __DIR__ . '/../../secret.json';
    private const IGNORED_CONVERSATIONS_FILE = __DIR__ . '/ignoredConversations.json';

    public function __construct()
    {
        $secret = @json_decode(file_get_contents(self::SECRET_CONFIG_FILE), true);
        $this->botToken = $secret['slack_bot_token'];
        $this->eventToken = $secret['slack_event_token'];
    }

    public function handleEvent(array $input): void
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
                $text = (string)@$input['event']['text'];
                if (strpos($text, '> del')) { // удалить канал из рассылки
                    if ($this->addIgnoredConversation($channel)) {
                        $responseText = 'Ок, я пока перестану присылать советы в этот канал 😥. Напиши мне "add", чтобы я возобновил.';
                    } else {
                        $responseText = 'Я уже понял, что не нужно пока сюда слать советы. Напиши мне "add", чтобы я возобновил.';
                    }
                } else if (strpos($text, '> add')) { // добавить канал в рассылку
                    if ($this->delIgnoredConversation($channel)) {
                        $responseText = 'Ура! Я снова буду присылать советы в этот канал 🥳.';
                    } else {
                        $responseText = 'Ага, я уже запланировал отправку советов сюда 🥳.';
                    }
                } else {
                    $responseText = <<<EOD
Привет 👋
Я бот 🤖 ежедневных советов по разработке.
Добавь меня в канал и каждый будний день в 9 утра буду присылать очередной совет 🤓.
Поправить или добавить советы можно здесь: https://github.com/chlp/daily_code_tips/blob/master/tips.json
Я перестану слать в этот канал сообщения, если мне написать "del". И по команде "add" снова начну. 
EOD;
                }
                $this->post(
                    $channel,
                    $responseText
                );
                break;
            case 'another_one_event':
                break;
            default:
                // not action on event
        }
    }

    /**
     * @return array
     */
    private function getIgnoredConversations(): array
    {
        $conversationsId = @json_decode(file_get_contents(self::IGNORED_CONVERSATIONS_FILE), true);
        if (is_array($conversationsId)) {
            return $conversationsId;
        }
        return [];
    }

    /**
     * Начать игнорировать канал
     * @param string $channelId
     * @return bool
     */
    private function addIgnoredConversation(string $channelId): bool
    {
        $conversations = $this->getIgnoredConversations();
        if (in_array($channelId, $conversations, true)) {
            return false;
        }
        $conversations[] = $channelId;
        file_put_contents(self::IGNORED_CONVERSATIONS_FILE, json_encode($conversations));
        return true;
    }

    /**
     * Удалить канал из игнорируемых
     * @param string $channelId
     * @return bool
     */
    private function delIgnoredConversation(string $channelId): bool
    {
        $conversations = $this->getIgnoredConversations();
        if (!in_array($channelId, $conversations, true)) {
            return false;
        }
        $conversations = array_diff($conversations, [$channelId]);
        file_put_contents(self::IGNORED_CONVERSATIONS_FILE, json_encode($conversations));
        return true;
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
        $ignoredConversations = $this->getIgnoredConversations();
        while (true) {
            $data = [
                'token' => $this->botToken,
                'types' => 'public_channel,private_channel',
                'limit' => $conversationsLimitPerRequest,
                'exclude_archived' => 'true',
                'cursor' => $cursor,
            ];
            $url = self::API_URL_PREFIX . 'conversations.list?' . http_build_query($data);
            $conversations = @json_decode(file_get_contents($url), true);
            if (!is_array($conversations) || !$conversations['ok']) {
                return [];
            }
            foreach ($conversations['channels'] as $channel) {
                if (
                    @$channel['is_member'] &&
                    is_string($channel['id']) &&
                    !in_array($channel['id'], $ignoredConversations, true)
                ) {
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
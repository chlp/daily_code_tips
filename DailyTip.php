<?php

class DailyTip
{
    private $tips;

    public function __construct()
    {
        $this->tips = json_decode(file_get_contents(__DIR__ . '/tips.json'), true);
    }

    public function getRandomText(): string
    {
        $tip = $this->tips[array_rand($this->tips)];
        $text = "*{$tip['title']}*\r\n";
        foreach ($tip['points'] as $point) {
            $text .= "* {$point}\r\n";
        }
        $text .= "_{$tip['link']}_";
        return $text;
    }
}
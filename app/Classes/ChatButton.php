<?php

namespace App\Classes;

use App\Classes\BotResponse;

class ChatButton{
    public $text;
    public $botResponse;

    public function __construct(string $text, BotResponse $botResponse)
    {
        $this->text = $text;
        $this->botResponse = $botResponse;
    }
}
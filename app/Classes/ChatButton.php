<?php

namespace App\Classes;

use App\Classes\BotResponse;
use Closure;

class ChatButton{
    public $text;
    public $botResponse;

    /**
     * Should return a bot response
     * @var Closure
     */
    public $createBotResponse;

    public function __construct(string $text, ?BotResponse $botResponse = null, ?Closure $createBotResponse = null)
    {
        $this->text = $text;
        $this->botResponse = $botResponse;
        $this->createBotResponse = $createBotResponse;
    }
}
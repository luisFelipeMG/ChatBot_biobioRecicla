<?php

namespace App\Classes;

class BotResponse{          // Can be a question
    public $text;
    public $buttons;        // nullable
    public $saveLog;        // true - false / Save history

    public function __construct(string $text, ?array $buttons = null, bool $saveLog = false)
    {
        $this->text = $text;
        $this->saveLog = $saveLog;
        $this->buttons = $buttons;
    }

}
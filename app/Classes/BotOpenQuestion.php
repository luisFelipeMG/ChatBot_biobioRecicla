<?php

namespace App\Classes;

use App\Classes\BotResponse;
use Closure;

class BotOpenQuestion extends BotResponse{
    /// SHOULD RETURN TRUE OR FALSE IF CAN CONTINUE
    public $onAnswerCallback;

    /**
     * @var BotResponse
     */
    public $nextResponse;

    /**
     * @var BotResponse
     */
    public $errorResponse;

    /**
     * @var string
     */
    public $errorMessage;

    /**
     * @var bool
     */
    public $onErrorBackToRoot = false;

    public function __construct(
        string $text, 
        Closure $onAnswerCallback, 
        ?BotResponse $nextResponse = null, 
        ?BotResponse $errorResponse = null, 
        ?string $errorMessage = null, 
        bool $onErrorBackToRoot = false,
        bool $saveLog = false
    )
    {
        $this->text = $text;
        $this->saveLog = $saveLog;
        $this->nextResponse = $nextResponse;
        $this->errorResponse = $errorResponse;
        $this->errorMessage = $errorMessage;
        $this->onErrorBackToRoot = $onErrorBackToRoot;
        $this->onAnswerCallback = $onAnswerCallback;
    }
}

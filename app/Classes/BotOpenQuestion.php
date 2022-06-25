<?php

namespace App\Classes;

use App\Classes\BotResponse;
use Closure;
use Opis\Closure\SerializableClosure;

class BotOpenQuestion extends BotResponse{
    /// SHOULD RETURN TRUE OR FALSE IF CAN CONTINUE
    public $onAnswerCallback;

    /**
     * @var BotResponse
     */
    public $errorResponse;

    /**
     * @var bool
     */
    public $onErrorBackToRoot = false;

    public function __construct(
        string $text, 
        Closure $onAnswerCallback, 
        ?Closure $nextResponse = null, 
        ?BotResponse $errorResponse = null, 
        ?string $errorMessage = null, 
        bool $onErrorBackToRoot = false,
        bool $saveLog = false
    )
    {
        $this->text = $text;
        $this->saveLog = $saveLog;
        if($nextResponse != null)
            $this->nextResponse = new SerializableClosure($nextResponse);
        else $this->nextResponse = null;
        $this->errorResponse = $errorResponse;
        $this->errorMessage = $errorMessage;
        $this->onErrorBackToRoot = $onErrorBackToRoot;
        $this->onAnswerCallback = $onAnswerCallback;
    }
}

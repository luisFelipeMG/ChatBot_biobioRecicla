<?php

namespace App\Classes;

use Closure;
use Opis\Closure\SerializableClosure;

class BotResponse{          // Can be a question
    public $text;
    public $buttons;        // nullable
    public $saveLog;        // true - false / Save history

    /**
     * If true, so this bot response will be used as new root
     * @var bool
     */
    public bool $autoRoot = false;

    /**
     * @var ?BotResponse
     */
    public ?BotResponse $rootResponse = null;

    /**
     * Should return Bot Response
     * @var ?SerializableClosure
     */
    public $nextResponse = null;

    /**
     * @var string
     */
    public ?string $errorMessage = null;

    /**
     * @var array
     */
    public array $additionalParams = array();

    public function __construct(
        string $text, 
        ?array $buttons = null, 
        bool $saveLog = false, 
        ?Closure $nextResponse = null,
        bool $autoRoot = false,
        ?BotResponse $customRootResponse = null,
        array $additionalParams = [],
        string $errorMessage = null
    )
    {
        $this->text = $text;
        $this->saveLog = $saveLog;
        $this->buttons = $buttons;
        if($nextResponse != null)
            $this->nextResponse = new SerializableClosure($nextResponse);
        else $this->nextResponse = null;
        $this->rootResponse = $customRootResponse;
        $this->autoRoot = $autoRoot;
        $this->additionalParams = $additionalParams;
        $this->errorMessage = $errorMessage;
    }

}
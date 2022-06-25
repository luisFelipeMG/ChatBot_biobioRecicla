<?php

namespace App\Classes;

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
     * @var ?BotResponse
     */
    public ?BotResponse $nextResponse = null;

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
        ?BotResponse $nextResponse = null,
        bool $autoRoot = false,
        ?BotResponse $customRootResponse = null,
        array $additionalParams = [],
        string $errorMessage = null
    )
    {
        $this->text = $text;
        $this->saveLog = $saveLog;
        $this->buttons = $buttons;
        $this->nextResponse = $nextResponse;
        $this->rootResponse = $customRootResponse;
        $this->autoRoot = $autoRoot;
        $this->additionalParams = $additionalParams;
        $this->errorMessage = $errorMessage;
    }

}
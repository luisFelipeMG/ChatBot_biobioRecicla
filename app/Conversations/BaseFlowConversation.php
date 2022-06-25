<?php

namespace App\Conversations;

use App\Contact;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Conversations\Conversation;

use App\Classes\ConversationFlow;
use App\Classes\BotResponse;
use App\Classes\BotOpenQuestion;
use App\Classes\ChatButton;

abstract class BaseFlowConversation extends Conversation
{
    /**
     * @var ConversationFlow
     */
    protected ConversationFlow $conversationFlow;

    protected function start_flow(BotResponse $firstResponse, ?BotResponse $rootResponse = null){
        $this->conversationFlow->start_flow($firstResponse, $rootResponse);
    }

    abstract protected function init();
    
    /**
     * Start the conversation
     */
    public function run()
    {
        $this->conversationFlow = new ConversationFlow($this);
        $this->init();
    }
}


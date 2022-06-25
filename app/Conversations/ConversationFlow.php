<?php

namespace App\Conversations;

use App\Contact;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BotResponse{          // Can be a question
    public $text;
    public $buttons;        // nullable
    public $saveLog;        // true - false / Save history

    function __construct(string $text, ?array $buttons = null, bool $saveLog = false)
    {
        $this->text = $text;
        $this->saveLog = $saveLog;
        $this->buttons = $buttons;
    }

}

class HumanResponse{
    public $text;
    public $usedButton; // nullable
}

class ChatButton{
    public $text;
    public $botResponse;

    function __construct(string $text, BotResponse $botResponse)
    {
        $this->text = $text;
        $this->botResponse = $botResponse;
    }
}

class ChatReady{
    public $ready = true;
}

class ConversationFlow{
    // Saved responses
    private static $responses = array();

    /**
     * @var Contact
     */
    private static $contact;

    /**
     * @var bool
     */
    private static $logAnonymous;

    // Setter for contact
    public static function set_contact(Contact $newContact){
        ConversationFlow::$contact = $newContact;
    }

    // Setter for "logAnonymous"
    public static function set_log_anonymous(bool $isAnonymous){
        ConversationFlow::$logAnonymous = $isAnonymous;
    }

    public static function create_question(Conversation $context, BotResponse $botResponse, BotResponse $rootResponse){
        // Context is required
        if($context == null) return;
        
        // Add question or response to responses
        array_push($responses, $botResponse->text);
        // If should save log, save conversation log
        if($botResponse->saveLog) ConversationFlow::save_conversation_log();

        // If buttons are null, so display bot response text and then display root response (it's like chatbot menu)
        if($botResponse->buttons == null){
            $context->say($botResponse->text);
            ConversationFlow::create_question($context, $rootResponse, $rootResponse);
            return;
        }        

        // If there are buttons, so create question
        $question = Question::create($botResponse->text)
            ->fallback('Unable to ask question')
            ->callbackId('ask_reason') // Maybe this callback Id should be calculated according to $responses last id added
            ->addButtons(array_map( function($value){ return Button::create($value->text)->value($value->text);}, $botResponse->buttons ));

        // Finally ask question and wait response
        return $context->ask($question, function (Answer $answer) use ($context, $botResponse, $rootResponse){
            if ($answer->isInteractiveMessageReply()) {

                // Get selected pressed button
                $foundButtons = array_filter($botResponse->buttons, function($value, $key)  use($answer){
                    return $value->text == $answer->getValue();
                }, ARRAY_FILTER_USE_BOTH);

                // Just check if selected button is found
                if(count($foundButtons) > 0){
                    // Get first found button 
                    $foundButton = array_shift($foundButtons);
                        
                    // Add selected button to responses array
                    array_push($responses, $foundButton->text);

                    // If response should be saved, so save conversation log
                    if($botResponse->saveLog) $this->save_conversation_log();

                    // Then back to root response
                    ConversationFlow::create_question($context, $foundButton->botResponse, $rootResponse);
                }
            }
        });
    }

    public static function save_conversation_log(){
        // Decode contact in json
        $contactInJson = json_decode(ConversationFlow::$contact, true);
        // Add responses to array. So now we have an array with contact and responses
        $contactWithResponses = array_merge(ConversationFlow::$responses, $contactInJson);
        // Encode array to json. This is data to finally save
        $dataToSaveJson = json_encode($contactWithResponses);

        // Get prefix. Change if is anonymous or not
        $prefixToSave = ConversationFlow::$logAnonymous? 'conversation_log_anonymous' : 'conversation_log';

        // Finally put file in storage
        Storage::disk('public')->put(
            $prefixToSave.'_'.ConversationFlow::$contact->id.'.json',
            $dataToSaveJson
        );
    }
}
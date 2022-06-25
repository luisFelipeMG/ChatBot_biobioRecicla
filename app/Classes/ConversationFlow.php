<?php

namespace App\Classes;

use App\Contact;
use App\Classes\BotResponse;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use Closure;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ConversationFlow{

    // RootContext
    public $rootContext;

    // Saved responses
    public $responses = array();

    /**
     * @var Contact
     */
    private $contact;

    /**
     * @var bool
     */
    private $logAnonymous = true;

    /**
     * User section define conversation flow for business purposes
     * @var int
     */
    private $userSection;

    public function __construct($rootContext, bool $initLogAnonymous = true)
    {
        $this->rootContext = $rootContext;
        if($initLogAnonymous) $this->set_log_anonymous(true);
    }

    // Setter for contact
    protected function set_contact(Contact $newContact){
        $this->contact = $newContact;
    }

    public function get_contact(){ return $this->contact; }

    // Setter for "logAnonymous"
    public function set_log_anonymous(bool $isAnonymous){
        $this->logAnonymous = $isAnonymous;

        if($isAnonymous && $this->contact == null){
            $this->set_contact(Contact::create([
                'name'=> 'Anonymous',
                'phone'=> '',
                'mail'=> ''
            ]));
        }
    }

    // Setter for "userSection"
    public function set_user_section(int $newUserSection){
        $this->userSection = $newUserSection;
    }

    // Process chatbot button options
    public function button_value_to_process($value){
        return str_replace(',', '', preg_replace('/\s+/', '', strtolower($value)));
    }

    public function create_question($context, BotResponse $botResponse, ?BotResponse $rootResponse){        
        // Context is required
        if($context == null) return;
        if($context->getBot() == null) return;
        
        // Add question or response to responses
        array_push($this->responses, $botResponse->text);
        
        // If should save log, save conversation log
        if($botResponse->saveLog) $this->save_conversation_log();

        // Set root response to same bot response if auto root is true
        if($botResponse->autoRoot) $botResponse->rootResponse = clone $botResponse;
        // Set root response to bot response, ONLY if bot response hasn't root response
        else if($botResponse->rootResponse == null) $botResponse->rootResponse = $rootResponse;

        // Only declare root response to use
        $rootResponseToUse = $botResponse->rootResponse;
        
        // Check if is open question
        if($botResponse instanceof BotOpenQuestion){
            $thisContext = $this;
            $question = Question::create($botResponse->text)
                ->fallback('Unable to ask question')
                ->callbackId('ask_open');

            $rootContextToUse = $this->rootContext;

            return $context->ask($question, function(Answer $answer) use ($thisContext, $botResponse, $rootResponseToUse, $rootContextToUse){
                if(($botResponse->onAnswerCallback)($answer, $this)){
                    // Answer is correct so continue or back to root response
                    return $thisContext->create_question(
                        $this, 
                        ($botResponse->nextResponse) != null? 
                            ($botResponse->nextResponse)($rootContextToUse) 
                            : $rootResponseToUse, 
                        $rootResponseToUse
                    );
                }
                
                // If has error message, say error message
                if($botResponse->errorMessage != null) $this->say($botResponse->errorMessage);

                // Display: Error custom response; repeat open question or back root response
                return $thisContext->create_question(
                    $this, 
                    $botResponse->errorResponse ?? $botResponse->onErrorBackToRoot? $rootResponseToUse : $botResponse, 
                    $rootResponseToUse
                );
                
            }, $botResponse->additionalParams);
        }

        // If buttons are null, so display bot response text and then display root response (it's like chatbot menu)
        if($botResponse->buttons == null){
            $context->say($botResponse->text, $botResponse->additionalParams);

            if($botResponse->nextResponse != null) return $this->create_question($context, ($botResponse->nextResponse)($this->rootContext), $rootResponseToUse);
            if($rootResponseToUse != null) return $this->create_question($context, $rootResponseToUse, $rootResponseToUse);
        }

        // If there are buttons, so create question
        $question = Question::create($botResponse->text)
            ->fallback('Unable to ask question')
            ->callbackId('ask_reason') // Maybe this callback Id should be calculated according to $responses last id added
            ->addButtons(array_map( function($value){ return Button::create($value->text)->value($value->text);}, $botResponse->buttons ));

        // Finally ask question and wait response
        $thisContext = $this;
        $rootContextToUse = $this->rootContext;
        return $context->ask($question, function (Answer $answer) use ($thisContext, $context, $botResponse, $rootResponseToUse, $rootContextToUse){
            $foundButtons = array();

            if ($answer->isInteractiveMessageReply()) {
                // Get selected pressed button
                $foundButtons = array_filter($botResponse->buttons, function($value, $key)  use($answer){
                    return $value->text == $answer->getValue();
                }, ARRAY_FILTER_USE_BOTH);
            }
            else {
                // Get selected Typed button
                $foundButtons = array_filter($botResponse->buttons, function($value, $key)  use($thisContext, $answer){
                    return $thisContext->button_value_to_process($value->text) == $thisContext->button_value_to_process($answer->getText())
                        || str_contains($thisContext->button_value_to_process($value->text), $thisContext->button_value_to_process($answer->getText()));
                }, ARRAY_FILTER_USE_BOTH);
            }

            // Just check if selected button is found
            if(count($foundButtons) <= 0 || count($foundButtons) > 1){
                // If not found, display error message and repeat question
                if($botResponse->errorMessage != null) 
                    $this->say($botResponse->errorMessage, $botResponse->additionalParams);
                else $this->say("'".$answer->getText()."' no lo entiendo. Intente nuevamente.", $botResponse->additionalParams);

                return $thisContext->create_question(
                    $this, 
                    clone $botResponse, 
                    $rootResponseToUse
                );
            }

            // Get first found button 
            $foundButton = array_shift($foundButtons); 

            // Add selected button to responses array
            array_push($thisContext->responses, $foundButton->text);

            // If response should be saved, so save conversation log
            if($botResponse->saveLog) $thisContext->save_conversation_log();

            // Execute custom on pressed from found button
            if($foundButton->onPressed != null) ($foundButton->onPressed)();

            // Then go to bot response from found button
            return $thisContext->create_question(
                $this, 
                $foundButton->createBotResponse != null? ($foundButton->createBotResponse)($rootContextToUse) : $foundButton->botResponse, 
                $rootResponseToUse
            );
            
        }, $botResponse->additionalParams);
    }

    public function save_conversation_log(){

        $contactWithResponses = null;
        if($this->contact != null){
            // Decode contact in json
            $contactInJson = json_decode($this->contact, true);
            // Add responses to array. So now we have an array with contact and responses
            $contactWithResponses = array_merge($this->responses, $contactInJson);
        } 
        
        // Encode array to json. This is data to finally save
        $dataToSaveJson = json_encode($contactWithResponses ?? $this->responses);

        // Get prefix. Change if is anonymous or not
        $prefixToSave = $this->logAnonymous? 'conversation_log_anonymous' : 'conversation_log';

        // Disk to use
        $diskToUse = $this->logAnonymous? 'chatlogs_anonymous' : 'chatlogs_contact';

        // Finally put file in storage
        Storage::disk($diskToUse)->put(
            $prefixToSave.'_'.($this->contact != null? $this->contact->id : str_replace(':', '_', now())).'.json',
            $dataToSaveJson
        );
    }
}

<?php

namespace App\Conversations;

use App\Contact;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use Closure;
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

class ChatButton{
    public $text;
    public $botResponse;

    function __construct(string $text, BotResponse $botResponse)
    {
        $this->text = $text;
        $this->botResponse = $botResponse;
    }
}

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

    function __construct(
        string $text, 
        Closure $onAnswerCallback, 
        ?BotResponse $nextResponse, 
        ?BotResponse $errorResponse, 
        ?string $errorMessage, 
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

    /**
     * User section define conversation flow for business purposes
     * @var int
     */
    private static $userSection;

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

        // Check if is open question
        if($botResponse instanceof BotOpenQuestion){
            $context->ask($botResponse->text, function(Answer $answer) use ($botResponse, $context, $rootResponse){
                if(($botResponse->onAnswerCallback)($answer)){
                    // Answer is correct so continue or back to root response
                    ConversationFlow::create_question($context, $botResponse->nextResponse ?? $rootResponse, $rootResponse);
                }
                else{
                    // If has error message, say error message
                    if($botResponse->errorMessage != null) $context->say($botResponse->errorMessage);

                    // Display: Error custom response; repeat open question or back root response
                    ConversationFlow::create_question(
                        $context, 
                        $botResponse->errorResponse ?? $botResponse->onErrorBackToRoot? $rootResponse : $botResponse, 
                        $rootResponse
                    );
                }
            });
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


/// COMMENTS ONLY FOR DEBUG
/*public function create_question($preguntita, $respuestita){
        $question = Question::create($preguntita)//le preguntamos al usuario que quiere saber
            ->fallback('Unable to ask question')
            ->callbackId('ask_reason')
            ->addButtons([
                Button::create($respuestita)->value('respuesta'),//Opción de hora, con value hour
            ]);
            return $this->ask($question, function (Answer $answer) {
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() === 'respuesta') {//Le muestra la hora la usuario si el value es hour
                        $this->say();
                    }else if ($answer->getValue() === 'day'){//Le muestra la hora la usuario si el value es date
                        $this->say('Brindamos soluciones ambientales, para la gestión integral de residuos.');
                        $click2 = '¿Que tipo de servicios ofrecen?';
                        $contactos = json_decode($this->contacto, true);
                        //array_push($this->Responses, $contactos, $this->click1, $click2);
                        array_push($this->Responses, $this->click1, $click2);
                        $array_merge = array_merge($this->Responses, $contactos);
                        $Responsejson = json_encode($array_merge);
                        //echo $Responsejson;
                        Storage::disk('public')->put('history '.$this->contacto->id.'.json', $Responsejson);
                    }
                }
            });

            json:

            {
                "texto": "what is this?",
                "buttons": [
                    0: {
                        "respuesta": "hola?",
                        "desencadena": {
                            "texto": "kfsdkfds",
                            "buttons": [
                                ...
                            ]
                        }
                    },
                    1: {
                        "respuesta": "chao?",
                        "desencadena": {
                            "texto": "Wenisima"
                        }
                    }
                ]
            }
            

            array objetoPregunta[];

            Objeto pregunta:
            - Texto de pregunta : string
            - Botones : array

            Objeto respuesta humana:
            - Texto : ?string
            - Boton

            Boton:
            - Texto de respuesta de usuario : string
            - Lo que desencadena (Respuesta bot / Respuesta bot con pregunta) (Objeto respuesta  / Objeto pregunta) : objeto bot

            Objeto respuesta 
            - Texto : string

            Objeto bot:
            - Heredan:
                - Objeto pregunta
                - Objeto respuesta

        
            Hola pregunte nomas // pregunta
            - Boton 1 // respuesta usuario
                - Bot responde la pregunta con texto / listo entonces volver al principio // respuesta bot
            - Boton 2 // respuesta usuario
                - Bot pregunta // respuesta bot - pregunta
                - Boton 1 // respuesta usuario
                    - Finalmente te responde / listo entonces volver al principio // respuesta bot
                - Boton 2 // respuesta usuario
                    - Bot pregunta // respuesta bot - pregunta
                    - Tu responder con texto // respuesta usuario
                        - Ok, gracias / listo entonces volver al principio // respuesta bot
            - n botones
            

            
    }*/

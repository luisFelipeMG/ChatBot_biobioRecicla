<?php

namespace App\Conversations;

use App\Contact;
use Illuminate\Foundation\Inspiring;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BotResponse{ // Can be a question
    public $text;
    public $buttons;    // nullable
    public $save;       // true - false / Save history

    function __construct(string $text, ?array $buttons = null, bool $save = false)
    {
        $this->text = $text;
        $this->save = $save;
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

class BotConversation extends Conversation
{
    protected $firstname;

    protected $phone;

    protected $email;

    protected $contacto;

    protected $click1;

    protected $Responses = array();

    protected $Preguntas;

    protected $Bot_responses;
    public function askName()
    {
        $this->ask('Hola! Cual es su nombre? Para dirigirme a usted.', function(Answer $answer) {
            // Guardar resultado
            $this->firstname = $answer->getText();

            $this->say('Un placer conocerle '.$this->firstname);
            $bool = 0;
            $this->askCellphone($bool);
        });
    }
    
    public function askCellphone($bool)
    {
        if($bool == 1){
            $this->ask('Perdon, el número debe estar en el formato de "+56912345678", intente de nuevo por favor', function(Answer $answer) {
                $answer->getText(); // Guardar resultado
                if(\preg_match("/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im", $answer)){
                    $this->phone = $answer->getText();
                    $this->say('Gracias '.$this->firstname);
                    $bool = 0;
                    $this->askEmail($bool);
                } else{
                    $bool = 1;
                    $this->askCellphone($bool);
                }
                
            });
        }
        $this->ask('Una cosa mas... Cual es su número de telefono? Para que podamos contactarlo para una atención personalizada.', function(Answer $answer) {
            // /(\+56|0056|56)?[ -]*(9)[ -]*([0-9][ -]*){8}/
            $answer->getText(); // Guardar resultado
            if(\preg_match("/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im", $answer)){
                $this->phone = $answer->getText();
                $this->say('Gracias '.$this->firstname);
                $bool = 0;
                $this->askEmail($bool);
            } else{
                $bool = 1;
                $this->askCellphone($bool);
            }
        });
    }
    public function askEmail($bool)
    {
        if($bool == 1){
            $this->ask('Perdon, el email debe estar en el formato de "tumail@dominio.com", intente de nuevo por favor', function(Answer $answer) {
                $answer->getText(); // Guardar resultado
                if(\preg_match("/^(([^<>()\[\]\.,;:\s@\”]+(\.[^<>()\[\]\.,;:\s@\”]+)*)|(\”.+\”))@(([^<>()[\]\.,;:\s@\”]+\.)+[^<>()[\]\.,;:\s@\”]{2,})$/", $answer)){
                    $this->email = $answer->getText();
                    $this->contacto = Contact::create([
                        'name'=> $this->firstname,
                        'phone'=> $this->phone,
                        'mail'=> $this->email
                    ]);
                    $this->say('Gracias '.$this->firstname);
                    $bool = 0;
                    $this->test();
                } else{
                    $bool = 1;
                    $this->askEmail($bool);
                }
            });
        }
        $this->ask('Por último, necesitamos su email.', function(Answer $answer) {
            /*$contact = new Contact(); $contact->name = $this->firstname; $contact->phone = $this->phone; $contact->mail = $this->email; $contact->save();*/
            $answer->getText();
            if(\preg_match("/^(([^<>()\[\]\.,;:\s@\”]+(\.[^<>()\[\]\.,;:\s@\”]+)*)|(\”.+\”))@(([^<>()[\]\.,;:\s@\”]+\.)+[^<>()[\]\.,;:\s@\”]{2,})$/", $answer)){
                $this->email = $answer->getText();
                $this->contacto = Contact::create([
                    'name'=> $this->firstname,
                    'phone'=> $this->phone,
                    'mail'=> $this->email
                ]);
                $this->say('Gracias '.$this->firstname);
                $bool = 0;
                $this->test();
            } else{
                $bool = 1;
                $this->askEmail($bool);
            }
            }
        );
    }

    public function test(){
        $preguntaInicial = new BotResponse("Bienvenido! Qué desea saber?", [
            new ChatButton("¿En que consiste la empresa?", new BotResponse("Somos una empresa que busca mantener una relación armónica entre las personas, 
            la sociedad y la naturaleza, para contribuir a una mejor calidad de vida.")),
            new ChatButton("¿Qué tipo de servicios ofrecen?", new BotResponse("Brindamos soluciones ambientales, para la gestión integral de residuos.")),
            new ChatButton("Desea cotizar algun servicio que ofrecemos?", new BotResponse("OK! Qué servicio desea cotizar?",[
                new ChatButton("Gestión de residuos", new BotResponse("Excelente! Lo llevaremos a la página correspondiente", null, true)),
                new ChatButton("Puntos limpios", new BotResponse("Excelente! Lo llevaremos a la página correspondiente", null, true)),
                new ChatButton("Consultoría", new BotResponse("Excelente! Lo llevaremos a la página correspondiente", null, true)),
                new ChatButton("Educación Ambiental", new BotResponse("Excelente! Lo llevaremos a la página correspondiente", null, true)),
                new ChatButton("Biciclaje", new BotResponse("Excelente! Lo llevaremos a la página correspondiente", null, true))
            ]))
        ], true);

        $this->create_question($preguntaInicial, $preguntaInicial);
    }

    /**
     * First question
     */
    public function hello()
    {
        $this->Preguntas = array();
        $this->Responses = array();
        Log::debug('An informational message.');
        error_log('Some message here.');
        $out = new \Symfony\Component\Console\Output\ConsoleOutput();
        $out->writeln("Hello from Terminal");
        $question = Question::create("¡Hola! Elige una opción") //Saludamos al usuario
            ->fallback('Unable to ask question')
            ->callbackId('ask_reason')
            ->addButtons([
                Button::create('¿Quién eres?')->value('who'),//Primera opcion, esta tendra el value who
                Button::create('Tengo dudas con respecto a la empresa')->value('info'), //Segunda opcion, esta tendra el value info
            ]);
        //Cuando el usuario elija la respuesta, se enviará el value aquí:
        return $this->ask($question, function (Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() === 'who') {//Si es el value who, contestará con este mensaje
                    $this->say('Soy un chatbot, te ayudo a navegar por esta aplicación, 
                    solo debes escribir "Hola bot"');
                    $this->click1 = '¿Quién eres?';
                    //Si es el value info, llamaremos a la funcion options
                } else if ($answer->getValue() === 'info'){
                    $this->click1 = 'Tengo dudas con respecto a la empresa';
                    $this->options();
                }
            }
        });
    }

    public function options(){
        $question = Question::create("¿Qué quieres saber?")//le preguntamos al usuario que quiere saber
            ->fallback('Unable to ask question')
            ->callbackId('ask_reason')
            ->addButtons([
                Button::create('¿En que consiste la empresa?')->value('hour'),//Opción de hora, con value hour
                Button::create('¿Que tipo de servicios ofrecen?')->value('day'),//Opción de fecha, con value day
            ]);

            return $this->ask($question, function (Answer $answer) {
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() === 'hour') {//Le muestra la hora la usuario si el value es hour
                        $this->say('Somos una empresa que busca mantener una relación armónica entre las personas, 
                        la sociedad y la naturaleza, para contribuir a una mejor calidad de vida.');
                        $click2 = '¿En que consiste la empresa?';
                        $contactos = json_decode($this->contacto);
                        array_push($this->Responses, $contactos, $this->click1, $click2);
                        $Responsejson = json_encode($this->Responses);
                        Storage::disk('public')->put('history '.$this->contacto->id.'.json', $Responsejson->toJson());
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
    }
    

    public function create_question(BotResponse $botResponse, BotResponse $rootResponse){
        array_push($this->Responses, $botResponse->text);
        if($botResponse->save) $this->save_history();

        if($botResponse->buttons == null){
            $this->say($botResponse->text);
            $this->create_question($rootResponse, $rootResponse);
            return;
        }        

        $question = Question::create($botResponse->text)//le preguntamos al usuario que quiere saber
            ->fallback('Unable to ask question')
            ->callbackId('ask_reason')
            ->addButtons(array_map( function($value){ return Button::create($value->text)->value($value->text);}, $botResponse->buttons ));
        //array_push($this->Responses, $botResponse->text);

        return $this->ask($question, function (Answer $answer) use ($botResponse, $rootResponse){
            if ($answer->isInteractiveMessageReply()) {

                $foundButtons = array_filter($botResponse->buttons, function($value, $key)  use($answer){
                    return $value->text == $answer->getValue();
                }, ARRAY_FILTER_USE_BOTH);

                if(count($foundButtons) > 0){
                    $foundButton = array_shift($foundButtons);
                        
                    array_push($this->Responses, $foundButton->text);
                    if($botResponse->save) $this->save_history();
                    $this->create_question($foundButton->botResponse, $rootResponse);
                }
            }
        });
    }

    public function save_history(){
        $contactos = json_decode($this->contacto, true);
        $array_merge = array_merge($this->Responses, $contactos);
        $Responsejson = json_encode($array_merge);
        Storage::disk('public')->put('history '.$this->contacto->id.'.json', $Responsejson);
    }

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

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->askName();
    }
}
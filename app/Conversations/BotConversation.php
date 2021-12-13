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


class BotConversation extends Conversation
{
    protected $firstname;

    protected $phone;

    protected $email;

    protected $contacto;

    protected $click1;

    protected $Responses;

    protected $Preguntas;

    protected $Bot_responses;
    public function askName()
    {
        $this->ask('Hola! Cual es su nombre?', function(Answer $answer) {
            // Guardar resultado
            $this->firstname = $answer->getText();

            $this->say('Nice to meet you '.$this->firstname);
            $this->askCellphone();
        });
    }

    public function askCellphone()
    {
        $this->ask('Una cosa mas... Cual es su numero de telefono?', function(Answer $answer) {
            /* /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im  */
            // Guardar resultado
            $this->phone = $answer->getText();

            $this->say('Gracias '.$this->firstname);
            $this->askEmail();
        });
    }
    public function askEmail()
    {
        $this->ask('Una cosa mas... Cual es su email?', function(Answer $answer) {
            // Guardar resultado
            $this->email = $answer->getText();
            /*$contact = new Contact();
            $contact->name = $this->firstname;
            $contact->phone = $this->phone;
            $contact->mail = $this->email;
            $contact->save();*/
            $this->contacto = Contact::create([
                'name'=> $this->firstname,
                'phone'=> $this->phone,
                'mail'=> $this->email
            ]);
            $this->say('Gracias '.$this->firstname);
            $this->hello();
        });
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

    }*/

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->askName();
    }
}
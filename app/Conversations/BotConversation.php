<?php

namespace App\Conversations;

use App\Contact;
use Illuminate\Foundation\Inspiring;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;

define('HUMAN', 1);
define('BUSINESS', 0);

class BotConversation extends Conversation
{
    protected $firstname;

    protected $phone;

    protected $email;

    protected $contacto;

    protected $click1;

    public function askName()
    {
        $this->ask('Hola! Cual es su nombre? Para dirigirme a usted.', function(Answer $answer) {
            // Guardar resultado
            $this->firstname = $answer->getText();

            $this->say('Un placer conocerle '.$this->firstname);
            $bool = 0;
            $this->askWhat($bool);
        });
    }

    public function askWhat($bool){
        $question = Question::create($this->firstname.', es usted una persona individual o representa a una empresa? Debe hacer click en los siguentes botones para responder:')
        ->fallback('Incapaz de hacer la pregunta')
        ->callbackId('create_database')
        ->addButtons([
            Button::create('Soy una persona individual')->value('individual'),
            Button::create('Represento a una empresa')->value('empresa'),
        ]);

        $this->ask($question, function (Answer $answer) {
        // Detect if button was clicked:
        if ($answer->isInteractiveMessageReply()) {
            if ($answer->getValue() == 'individual'){
                $this->phone = "No es una empresa";
                $this->email = "No es una empresa";
                $this->contacto = Contact::create([
                    'name'=> $this->firstname,
                    'phone'=> $this->phone,
                    'mail'=> $this->email
                ]);
                $bool = 1;
                $this->test($bool);
            }else if($answer->getValue() == 'empresa'){
                $bool = 0;
                $this->askCellphone($bool);
            } // will be either 'yes' or 'no'
        }
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
        $question = Question::create($this->firstname.', esta usted de acuerdo con que nos proporcione su número de teléfono y email para que podamos contactarlo para una atención mas personalizada?')
        ->fallback('Incapaz de hacer la pregunta')
        ->callbackId('create_database')
        ->addButtons([
            Button::create('Si, me parece bien')->value('si'),
            Button::create('No estoy de acuerdo')->value('no'),
        ]);

        $this->ask($question, function (Answer $answer) {
        // Detect if button was clicked:
        if ($answer->isInteractiveMessageReply()) {
            if ($answer->getValue() == 'si'){
                $this->ask('Bien! Cúal es su número de teléfono?', function(Answer $answer) {
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
            }else if($answer->getValue() == 'no'){
                $this->say('OK! No hay problema!');
                $this->phone = "No dió número";
                $this->email = "No dió email";
                $this->contacto = Contact::create([
                    'name'=> $this->firstname,
                    'phone'=> $this->phone,
                    'mail'=> $this->email
                ]);
                $bool = 0;
                $this->test($bool);
            } // will be either 'yes' or 'no'
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
                $this->test($bool);
            } else{
                $bool = 1;
                $this->askEmail($bool);
            }
            }
        );
    }

    public function test(int $userSection){
        if($userSection == HUMAN){
            $preguntaInicial = new BotResponse("Bienvenido! Qué desea saber?", [
                new ChatButton("Tengo bastante plastico pero no se en donde dejarlo, que debo hacer con el?", new BotResponse("Puedes dejarlo en un punto limpio para reciclarlo!")),

            ], true);

            ConversationFlow::create_question($this, $preguntaInicial, $preguntaInicial);
        } else{
             $preguntaInicial = new BotResponse("Bienvenido! Qué desea saber?", [
                new ChatButton("¿En que consiste la empresa?", new BotResponse("Somos una empresa que busca mantener una relación armónica entre las personas, 
                la sociedad y la naturaleza, para contribuir a una mejor calidad de vida.")),
                new ChatButton("¿Qué tipo de servicios ofrecen?", new BotResponse("Brindamos soluciones ambientales, para la gestión integral de residuos.")),
                new ChatButton("Desea cotizar algun servicio que ofrecemos?", new BotResponse("OK! Qué servicio desea cotizar?",[
                    new ChatButton("Gestión de residuos", new BotResponse("Ingrese a este link: https://biobiorecicla.cl/cotizacion-empresas-instituciones/", null, true)),
                    new ChatButton("Puntos limpios", new BotResponse("Ingrese a este link: https://biobiorecicla.cl/condominios-comunidades/", null, true)),
                    new ChatButton("Consultoría", new BotResponse("Ingrese a este link: https://biobiorecicla.cl/cotizacion-empresas-instituciones/", null, true)),
                    new ChatButton("Educación Ambiental", new BotResponse("Ingrese a este link: https://biobiorecicla.cl/condominios-comunidades/", null, true)),
                    new ChatButton("Biciclaje", new BotResponse("Ingrese a este link: https://biobiorecicla.cl/conciencia-ambiental/", null, true))
                ]))
            ], true);

            ConversationFlow::create_question($this, $preguntaInicial, $preguntaInicial);
        }
       
    }
    
    /**
     * Start the conversation
     */
    public function run()
    {
        $this->askName();
    }
}
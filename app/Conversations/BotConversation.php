<?php

namespace App\Conversations;

use App\Contact;
use Illuminate\Foundation\Inspiring;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;

use App\Classes\ConversationFlow;
use App\Classes\BotResponse;
use App\Classes\BotOpenQuestion;
use App\Classes\ChatButton;

define('HUMAN', 1);
define('BUSINESS', 0);

class BotConversation extends Conversation
{
    protected $firstname;
    protected $phone;
    protected $email;

    /**
     * @var ConversationFlow
     */
    protected $conversationFlow;

    
    /**
     * Start the conversation
     */
    public function run()
    {
        // Init conversation flow
        $this->conversationFlow = new ConversationFlow();
        
        // Lista con preguntas persona natural
        $preguntasNatural = new BotResponse("Bienvenido! Qué desea saber?", [
            new ChatButton("Tengo bastante plastico pero no se en donde dejarlo, que debo hacer con el?", new BotResponse("Puedes dejarlo en un punto limpio para reciclarlo!")),

        ], true);

        // Lista con preguntas principales empresa
        $preguntasEmpresa = new BotResponse("Bienvenido! Qué desea saber?", [
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

        $emailQuestion = new BotOpenQuestion(
            'Por último necesitamos su email',
            function(Answer $answer, $context){
                if(\preg_match("/^(([^<>()\[\]\.,;:\s@\”]+(\.[^<>()\[\]\.,;:\s@\”]+)*)|(\”.+\”))@(([^<>()[\]\.,;:\s@\”]+\.)+[^<>()[\]\.,;:\s@\”]{2,})$/", $answer)){
                    $this->email = $answer->getText();
                    $this->conversationFlow->set_contact(Contact::create([
                        'name'=> $this->firstname,
                        'phone'=> $this->phone,
                        'mail'=> $this->email
                    ]));
                    $this->conversationFlow->set_log_anonymous(false);
                    return true;
                } 
                return false;
            },
            null,
            null,
            'El email debe estar en el formato de "tumail@dominio.com", intente de nuevo por favor'
        );

        $phoneQuestion = new BotOpenQuestion(
            'Cuál es su número de teléfono?',
            function(Answer $answer, $context){
                if(\preg_match("/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im", $answer)){
                    $this->phone = $answer->getText();
                    return true;
                } 
                return false;
            },
            $emailQuestion,
            null,
            'El número debe estar en el formato de "+56912345678", intente de nuevo por favor'
        );

        $businessQuestion = new BotResponse(
            'Es usted una persona natural o una empresa?',
            [
                new ChatButton('Persona natural', $preguntasNatural),
                new ChatButton(
                    'Empresa', 
                    null, 
                    fn() => new BotResponse(
                        $this->firstname.', esta usted de acuerdo con que nos proporcione su número de teléfono y email para que podamos contactarlo para una atención mas personalizada?',
                        [
                            new ChatButton('Si, me parece bien', $phoneQuestion),
                            new ChatButton('No estoy de acuerdo', $preguntasEmpresa)
                        ]
                    )
                )
            ]
        );

        $nameQuestion = new BotOpenQuestion(
            'Cuál es su nombre?',
            function(Answer $answer, $context){
                $this->firstname = $answer->getText();
                $context->say('Un placer conocerle '.$this->firstname);
                return true;
            },
            $businessQuestion,
            null,
            'Intente nuevamente'
        );

        $this->conversationFlow->create_question($this, $nameQuestion, $preguntasEmpresa);
    }
}


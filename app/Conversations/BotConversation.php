<?php

namespace App\Conversations;

use App\Contact;
use BotMan\BotMan\Messages\Incoming\Answer;
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
        $this->conversationFlow = new ConversationFlow($this);
        
        // Lista con preguntas persona natural
        $preguntasNatural = new BotResponse("Bienvenido! Qué desea saber?", [
            new ChatButton("Tengo bastante plastico pero no se en donde dejarlo, que debo hacer con el?", fn() => new BotResponse("Puedes dejarlo en un punto limpio para reciclarlo!")),
        ], true, null, true);

        // Lista con preguntas principales empresa
        $preguntasEmpresa = new BotResponse("Bienvenido! Qué desea saber?", [
            new ChatButton("¿En que consiste la empresa?", fn() => new BotResponse("Somos una empresa que busca mantener una relación armónica entre las personas, 
            la sociedad y la naturaleza, para contribuir a una mejor calidad de vida.")),
            new ChatButton("¿Qué tipo de servicios ofrecen?", fn() => new BotResponse("Brindamos soluciones ambientales, para la gestión integral de residuos.")),
            new ChatButton("Desea cotizar algun servicio que ofrecemos?", fn() => new BotResponse("OK! Qué servicio desea cotizar?",[
                new ChatButton("Gestión de residuos", fn() => new BotResponse("Ingrese a este link: https://biobiorecicla.cl/cotizacion-empresas-instituciones/", null, true)),
                new ChatButton("Puntos limpios", fn() => new BotResponse("Ingrese a este link: https://biobiorecicla.cl/condominios-comunidades/", null, true)),
                new ChatButton("Consultoría", fn() => new BotResponse("Ingrese a este link: https://biobiorecicla.cl/cotizacion-empresas-instituciones/", null, true)),
                new ChatButton("Educación Ambiental", fn() => new BotResponse("Ingrese a este link: https://biobiorecicla.cl/condominios-comunidades/", null, true)),
                new ChatButton("Biciclaje", fn() => new BotResponse("Ingrese a este link: https://biobiorecicla.cl/conciencia-ambiental/", null, true))
            ]))
        ], true);

        // Create email question. Will be used only with consent
        $emailQuestion = new BotOpenQuestion(
            'Por último necesitamos su email',
            // Check if answer is an email
            function(Answer $answer, $context){
                if(\preg_match("/^(([^<>()\[\]\.,;:\s@\”]+(\.[^<>()\[\]\.,;:\s@\”]+)*)|(\”.+\”))@(([^<>()[\]\.,;:\s@\”]+\.)+[^<>()[\]\.,;:\s@\”]{2,})$/", $answer)){
                    $this->email = $answer->getText();

                    // Get current used contact and update data
                    $currentContact = Contact::find($this->conversationFlow->get_contact()->id);
                    $currentContact->name = $this->firstname;
                    $currentContact->phone = $this->phone;
                    $currentContact->mail = $this->email;

                    // Save new data
                    $currentContact->save();

                    $this->conversationFlow->set_log_anonymous(false);
                    return true;
                } 
                return false;
            },
            null,
            null,
            'El email debe estar en el formato de "tumail@dominio.com", intente de nuevo por favor'
        );

        // Create phone question. Will be used only with consent
        $phoneQuestion = new BotOpenQuestion(
            'Cuál es su número de teléfono?',
            // Check if answer is phone number
            function(Answer $answer, $context){
                if(\preg_match("/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im", $answer)){
                    $this->phone = $answer->getText();
                    return true;
                } 
                return false;
            },
            // If is a phone number, go to "emailQuestion"
            fn() => $emailQuestion,
            null,
            // When is not a phone number, display this error message
            'El número debe estar en el formato de "+56912345678", intente de nuevo por favor'
        );

        // Create question: Is business?
        $businessQuestion = new BotResponse(
            'Es usted una persona natural o una empresa?',
            [
                new ChatButton(
                    'Persona natural', 
                    // Go to "preguntas natural"
                    fn() => $preguntasNatural,
                    fn() => $this->conversationFlow->set_user_section(HUMAN)
                ),
                new ChatButton(
                    'Empresa', 
                    // If is business, then ask about consent. 
                    // So if accepts, start with phone question. Otherwise, skip to "preguntasEmpresa"
                    fn($context) => new BotResponse(
                        $context->firstname.', esta usted de acuerdo con que nos proporcione su número de teléfono y email para que podamos contactarlo para una atención mas personalizada?',
                        [
                            new ChatButton('Si, me parece bien', fn() => $phoneQuestion),
                            new ChatButton('No estoy de acuerdo', fn() => $preguntasEmpresa)
                        ]
                    ),
                    fn() => $this->conversationFlow->set_user_section(BUSINESS)
                )
            ]
        );

        // Create name question
        $nameQuestion = new BotOpenQuestion(
            'Cuál es su nombre?',
            function(Answer $answer, $context){
                $this->firstname = $answer->getText();
                return true;
            },
            fn($context) => new BotResponse(
                'Un placer conocerle '.$context->firstname,
                null,
                false,
                fn() => $businessQuestion
            ),
            null,
            'Intente nuevamente'
        );

        // Start with "name question"
        $this->conversationFlow->create_question($this, $nameQuestion, $preguntasEmpresa);
    }
}


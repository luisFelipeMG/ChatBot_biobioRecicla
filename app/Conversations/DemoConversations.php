<?php

namespace App\Conversations;

use App\Contact;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Conversations\Conversation;

use App\Classes\ConversationFlow;
use App\Classes\BotResponse;
use App\Classes\BotOpenQuestion;
use App\Classes\ChatButton;

class DemoConversations extends Conversation
{
    /**
     * @var ConversationFlow
     */
    protected $conversationFlow;

    // Only code for demo
    function first_demo(){
        $this->conversationFlow = new ConversationFlow($this);

        $yourQuestion = new BotOpenQuestion(
            'Cuál es su edad?',
            fn(Answer $answer) => new BotResponse('Tu edad es '.$answer->getText())
        );

        $this->conversationFlow->start_flow($yourQuestion);
    }

    function buttons_demo(){
        $this->conversationFlow = new ConversationFlow($this);

        $yourQuestion = new BotResponse(
            'De qué ciudad es usted?',
            [
                new ChatButton('Concepción', fn() => new BotResponse('Oh, Concepción. Excelente!')),
                new ChatButton('Talcahuano', fn() => new BotResponse('Oh, Talcahuano. Perfecto!'))
            ]
        );

        $this->conversationFlow->start_flow($yourQuestion);
    }

    function correct_answer_demo(){
        $this->conversationFlow = new ConversationFlow($this);

        $yourQuestion = new BotOpenQuestion(
            'Cuánto es 2+2?',
            fn() => new BotResponse('Correcto!'),
            'Estás seguro?',
            fn(Answer $answer) => str_replace(' ', '', $answer->getText()) == '4' 
        );

        $this->conversationFlow->start_flow($yourQuestion);
    }

    protected $myVariable = '';
    function update_variable_demo(){
        $this->conversationFlow = new ConversationFlow($this);

        $yourQuestion = new BotOpenQuestion(
            'Cuál será tu nueva variable',
            function(Answer $answer, $context) { 
                $context->myVariable = $answer->getText();
                return new BotResponse('Ok! Tu nueva variable ahora es: '.$context->myVariable);
            }
        );
        
        $this->conversationFlow->start_flow($yourQuestion, $yourQuestion);
    }

    function use_multiple_responses_demo(){
        $this->conversationFlow = new ConversationFlow($this);

        $secondQuestion = new BotOpenQuestion(
            'Cuál es su color favorito?',
            fn() => $this->third_question_for_demo() // Use response from function
        );

        $firstQuestion = new BotResponse(
            'Prefiere gatos o perros?',
            [
                new ChatButton('Gatos', fn() => $secondQuestion), // Use response from variable
                new ChatButton('Perros', fn() => $secondQuestion)
            ]
        );

        $welcomeResponse = new BotResponse(
            'Bienvenido a la encuesta',
            null,
            false,
            fn() => $firstQuestion // Use response from variable
        );
        
        $this->conversationFlow->start_flow($welcomeResponse);
    }
    function third_question_for_demo() { return new BotResponse('Genial! Muchas gracias por responder la encuesta'); }
    
    /**
     * Start the conversation
     */
    public function run()
    {
        // Execute function you want to run demo
        $this->use_multiple_responses_demo();
        return;
    }
}


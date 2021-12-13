<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FailChatController extends Controller
{
    function index($bot){
        $bot->reply('Lo siento, no reconozco ese comando, intenta utilizar "Hola bot"');
        //Avisamos al usuario que ese comando no esta configurado en el chatbot
    }
}
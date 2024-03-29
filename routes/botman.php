<?php
use App\Http\Controllers\BotManController;

$botman = resolve('botman');

//$botman->say("Hola! Soy un bot que te puede guiar! Si tienes alguna duda solo escribe 'Hola bot'", null);

$botman->hears('Hola', 'App\Http\Controllers\ChatController@index');
//El primer parametro "hola bot" será el que active nuestro bot, llamará a la función
//index de nuestro controlador chatController.php y ésta a la función hello

$botman->fallback('App\Http\Controllers\FailChatController@index');
//Si lo que envia el usuario no lo conocemos, se ejecuta la función index del
//controlador FailChatController
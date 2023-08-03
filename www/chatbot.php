<?php

require_once 'init.php';


$bot = new \App\ChatBot(\App\System::getOption("common", 'tbtoken')) ;

$bot->onHook()  ;
// $bot->getUpdates()  ;

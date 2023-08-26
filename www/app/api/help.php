<?php

namespace App\API;

/**
 * возвращает  описание   API  по  адресу  /api/help
 */
class help
{
    public function __construct() {


        $path = _ROOT . "templates/apihelp.html";

        $template = @file_get_contents($path);
        echo $template;
        die;
    }

}

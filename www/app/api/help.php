<?php

namespace App\API;

/**
 * возвращает  описание   API  по  адресу  /api/help
 */
class help
{

    function __construct() {

        global $_config;

        $path = '';
        $name = ltrim($name, '\\');

        $lang = $_config['common']['lang'];

        if (strlen($_GET['lang']) > 0) {
            $lang = $_GET['lang'];
        }

        $templatepath = 'templates/';

        if (strlen($lang) > 0 && $lang != 'ru') {
            $templatepath = 'templates_' . $lang . '/';
        }
        $path = _ROOT . strtolower($templatepath) . "apihelp.html";

        $template = @file_get_contents($path);
        echo $template;
        die;
    }

}

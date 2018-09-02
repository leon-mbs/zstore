<?php

namespace App;

/**
 * Класс  приложения, выполняющий
 * жизненный  цикл  работы  сайта
 */
class Application extends \Zippy\WebApplication
{

    public function __construct($homepage) {
        parent::__construct($homepage);
    }

    /**
     * Возвращает  шаблон  страницы
     */
    public function getTemplate($name) {

        $path = '';
        $name = ltrim($name, '\\');
        $arr = explode('\\', $name);
        $templatepath = _ROOT . 'templates/';


        $className = str_replace("\\", "/", ltrim($name, '\\'));

        if (strpos($className, 'App/') === 0) {
            $path = $templatepath . (str_replace("App/", "", $className)) . ".html";
        }



        if (file_exists(strtolower($path)) == false) {
            throw new \Exception('Invalid template path: ' . strtolower($path));
        }
        $template = @file_get_contents(strtolower($path));

        return $template;
    }

    /**
     * Роутер.   
     *
     * @param mixed $uri
     */
    public function Route($uri) {


        if (preg_match('/^[-#a-zA-Z0-9\/_]+$/', $uri) == 0) {

            Application::Redirect404();
        }
        
        
    $api = explode('/', $uri);

    if ($api[0] == 'api' && count($api) > 2) {

        $class = $api[1];
        $params = array_slice($api, 3);

        if ($class == 'echo') {  //для  теста  /api/echo/параметр
            $response = "<echo>" . $api[2] . "</echo>";
        } else {

            try {


                require_once(_ROOT . DIRECTORY_SEPARATOR . strtolower("api" . DIRECTORY_SEPARATOR . $class . ".php"));

                $class = "\\App\\API\\" . $class;

                $page = new $class;

                //если RESTFul
                if ($page instanceof \App\RestFul) {
                    $page->Execute($params[0],$params[1],$params[2],$params[3]);
                    die;
                }


                $response = call_user_func_array(array($page, $api[2]), $params);
                
            } catch (Throwable $e) {


                $response = "<error>" . $e->getMessage() . "</error>";
            }
        }
        $xml = '<?xml version="1.0" encoding="utf-8"?>' . $response;

        header(`Content-Type: text/xml; charset=utf-8`);
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        echo $xml;

        die;
    }
        
        
        
    }

    /**
     * Редирект на  страницу с  ошибкой
     *
     */
    public static function RedirectError($message) {
        self::$app->getResponse()->Redirect("\\App\\Pages\\Error", $message);
    }

}

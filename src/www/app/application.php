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

            self::Redirect404();
        }


        $api = explode('/', $uri);

        if ($api[0] == 'api' && count($api) > 1) {

            $class = $api[1];

            if ($class == 'echo') {  //для  теста  /api/echo/параметр
                $response = "<echo>" . $api[2] . "</echo>";
            } else {

                try {

                    $file = _ROOT . "app/api/" . strtolower($class) . ".php";
                    require_once($file);

                    $class = "\\App\\API\\" . $class;

                    $page = new $class;

                    //если RESTFul
                    if ($page instanceof \App\RestFul) {
                        $params = array_slice($api, 2);
                        $page->Execute($params);
                        die;
                    }

                    $params = array_slice($api, 3);
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

        $arr = explode('/', $uri);





        $pages = array(
            "shop" => "\\App\\Shop\\Pages\\Main",
            "store" => "\\App\\Pages\\Main",
            "sp" => "\\App\\Shop\\Pages\\ProductView",
            "aboutus" => "\\App\\Shop\\Pages\\AboutUs",
            "delivery" => "\\App\\Shop\\Pages\\Delivery",
            "contact" => "\\App\\Shop\\Pages\\Contact",
            "simage" => "\\App\\Pages\\LoadImage",
            "scat" => "\\App\\Shop\\Pages\\Main",
            "pcat" => "\\App\\Shop\\Pages\\Catalog"
        );

        if (strlen($pages[$arr[0]]) > 0) {
            if (strlen($arr[2]) > 0) {
                self::$app->LoadPage($pages[$arr[0]], $arr[1], $arr[2]);
            } else
            if (strlen($arr[1]) > 0) {
                self::$app->LoadPage($pages[$arr[0]], $arr[1]);
            } else
            if (strlen($arr[0]) > 0) {
                self::$app->LoadPage($pages[$arr[0]]);
            }
        }
        if (strlen($pages[$uri]) > 0) {
            self::$app->LoadPage($pages[$uri]);
        }
    }

    /**
     * редирект по URL
     * 
     * @param mixed $message
     * @param mixed $uri
     */
    public static function RedirectURI($uri) {
        self::$app->getResponse()->toPage($uri);
    }

}

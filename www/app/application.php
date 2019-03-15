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
               echo $api[2];
               die;
            }                
 

                try {

                    $file = _ROOT . "app/api/" . strtolower($class) . ".php";
                    if(!file_exists($file)) {
                        $this->Redirect404();
                        die;
                    }
                    require_once($file);

                    $class = "\\App\\API\\" . $class;

                    $page = new $class;

                    //  RESTFul
                    if ($page instanceof \App\RestFul) {
                        $params = array_slice($api, 2);
                        $page->Execute($params);
                        die;
                    }
                    // JSON-RPC
                     if ($page instanceof \App\JsonRPC) {
                        $page->Execute();
                        die;
                    }                   
                    
                    //для произвольной страницы
                    $params = array_slice($api, 3);
                    echo call_user_func_array(array($page, $api[2]), $params);
                    die;                     
                
                } catch (Throwable $e) {
                    global $logger;
                    $logger->error($e->getMessage());
                    
                    die("Server error") ;
                }
            
            
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

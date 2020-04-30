<?php

namespace App;

/**
 * Класс  приложения, выполняющий
 * жизненный  цикл  работы  сайта
 */
class Application extends \Zippy\WebApplication
{

    /**
     * Возвращает  шаблон  страницы
     */
    public function getTemplate($name) {
        global $_config;


        $path = '';
        $name = ltrim($name, '\\');


        $templatepath = 'templates/';
        if ($_config['common']['lang'] == 'ua') {
            $templatepath = 'templates_ua/';
        }

        $className = str_replace("\\", "/", ltrim($name, '\\'));

        if (strpos($className, 'App/') === 0) {
            $path = $templatepath . (str_replace("App/", "", $className)) . ".html";
        }

        $path = _ROOT . strtolower($path);

        if (file_exists($path) == false) {
            throw new \Exception('Invalid template path: ' . $path);
        }
        $template = @file_get_contents($path);

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
                if (!file_exists($file)) {
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
            } catch (\Throwable $e) {
                global $logger;
                $logger->error($e->getMessage());

                die("Server error");
            }
        }

        $arr = explode('/', $uri);

        $pages = array(
            "store" => "\\App\\Pages\\Main",
            "shop" => "\\App\\Modules\\Shop\\Pages\\Main",
            "sp" => "\\App\\Modules\\Shop\\Pages\\ProductView",
            "aboutus" => "\\App\\Modules\\Shop\\Pages\\AboutUs",
            "delivery" => "\\App\\Modules\\Shop\\Pages\\Delivery",
            "contact" => "\\App\\Modules\\Shop\\Pages\\Contact",
            "scat" => "\\App\\Modules\\Shop\\Pages\\Main",
            "pcat" => "\\App\\Modules\\Shop\\Pages\\Catalog",
            "project" => "\\App\\Modules\\Issue\\Pages\\ProjectList",
            "issue" => "\\App\\Modules\\Issue\\Pages\\IssueList",
            "topic" => "\\App\\Modules\\Note\\Pages\\ShowTopic"
        );

        if (strlen($pages[$arr[0]]) > 0) {
            if (strlen($arr[2]) > 0) {
                self::$app->LoadPage($pages[$arr[0]], $arr[1], $arr[2]);
            } else {
                if (strlen($arr[1]) > 0) {
                    self::$app->LoadPage($pages[$arr[0]], $arr[1]);
                } else {
                    if (strlen($arr[0]) > 0) {
                        self::$app->LoadPage($pages[$arr[0]]);
                    }
                }
            }
        }
        if (strlen($pages[$uri]) > 0) {
            self::$app->LoadPage($pages[$uri]);
        }
    }

}

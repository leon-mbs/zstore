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
        

        $path = '';
        $name = ltrim($name, '\\');

        $templatepath = 'templates/';

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
             
            try {

                $file = _ROOT . "app/api/" . strtolower($class) . ".php";
                if (!file_exists($file)) {
                    $this->Redirect404();
                    die;
                }
                require_once($file);

                $class = "\\App\\API\\" . $class;
               // $method = $api[2];

                $page = new $class;
           
                if ($page instanceof \App\API\JsonRPC) {
                    $page->Execute();
                } else {
                   http_response_code (403); 
                }
                die;
                
            } catch(\Throwable $e) {
                global $logger;
                $logger->error($e->getMessage());

                die("Server error");
            }
        }

        $arr = explode('/', $uri);

        $pages = array(
            "store"          => "\\App\\Pages\\Main",
            "admin"          => "\\App\\Pages\\Main",
            "shop"           => "\\App\\Modules\\Shop\\Pages\\Catalog\\Main",
            "sp"             => "\\App\\Modules\\Shop\\Pages\\Catalog\\ProductView",
            "showreport"     => "\\App\\Pages\\ShowReport",
            "showdoc"        => "\\App\\Pages\\ShowDoc",
            "doclink"        => "\\App\\Pages\\Doclink",
            "scat"           => "\\App\\Modules\\Shop\\Pages\\Catalog\\Main",
            "pcat"           => "\\App\\Modules\\Shop\\Pages\\Catalog\\Catalog",
            "project"        => "\\App\\Modules\\Issue\\Pages\\ProjectList",
            "issue"          => "\\App\\Modules\\Issue\\Pages\\IssueList",
            "topic"          => "\\App\\Modules\\Note\\Pages\\ShowTopic"
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
            return;
        }
        if (strlen($pages[$uri]) > 0) {
            self::$app->LoadPage($pages[$uri]);
            return;
        }

        //кастомные страницы  в онлайн каталогк
        $shoppages =      \App\Modules\Shop\Helper::getPages() ;
        
        if ( in_array($uri,$shoppages)  ) {
            self::$app->LoadPage("\\App\\Modules\\Shop\\Pages\\CustomPage",$uri);
            return;
        }      
        //товары в онлайн каталоге
        $prod = \App\Modules\Shop\Entity\Product::loadSEF($uri);
        if ($prod instanceof \App\Entity\Item) {
            self::$app->LoadPage($pages['sp'], $prod->item_id);
            return;
        }
    }

    public static function RedirectError() {
        self::Redirect("\\App\\Pages\\Error");
    }

}

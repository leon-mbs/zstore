<?php


error_reporting(E_ALL ^ E_NOTICE);

define('_ROOT', __DIR__ . '/');
$http = $_SERVER["HTTPS"] == 'on' ? 'https' : 'http';
define('_BASEURL', $http . "://" . $_SERVER["HTTP_HOST"] . '/');

define('UPLOAD_USERS', 'uploads/users/');


date_default_timezone_set('Europe/Kiev');

require_once _ROOT . 'vendor/autoload.php';
include_once _ROOT . "vendor/adodb/adodb-php/adodb-exceptions.inc.php";



// логгер
$logger = new \Monolog\Logger("main");
$dateFormat = "Y n j, g:i a";
//$output = "%datetime% > %level_name% > %message% %context% %extra%\n";
$output = "%datetime%  %level_name% : %message% \n";
$formatter = new \Monolog\Formatter\LineFormatter($output, $dateFormat);
$h1 = new \Monolog\Handler\RotatingFileHandler(_ROOT . "logs/app.log", 10, $_config['common']['loglevel']);
$h2 = new \Monolog\Handler\RotatingFileHandler(_ROOT . "logs/error.log", 10, 400);
$h1->setFormatter($formatter);
$h2->setFormatter($formatter);
$logger->pushHandler($h1);
$logger->pushHandler($h2);
$logger->pushProcessor(new \Monolog\Processor\IntrospectionProcessor());


//чтение  конфигурации
$_config = parse_ini_file(_ROOT . 'config/config.ini', true);

//  phpQuery::$debug = true;

//Параметры   соединения  с  БД
\ZDB\DB::config($_config['db']['host'], $_config['db']['name'], $_config['db']['user'], $_config['db']['pass']);

//проверяем соединение
try{
   $conn =   \ZDB\DB::getConnect();
}catch(Throwable $e){
        echo  'Ошибка  соединения с  БД. Подробности  в логе.';
         
        $logger->error($e);
        die;
}

// автолоад классов  приложения
function app_autoload($className)
{
    $className = str_replace("\\", "/", ltrim($className, '\\'));



    if (strpos($className, 'App/') === 0) {
        $file = __DIR__ . DIRECTORY_SEPARATOR . strtolower($className) . ".php";
        if (file_exists($file)) {
            require_once($file);
        } else {
            die('Неверный класс ' . $className);
        }
    }
}

spl_autoload_register('app_autoload');


session_start();



 

@mkdir(_ROOT . "logs");




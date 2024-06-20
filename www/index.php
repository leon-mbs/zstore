<?php

require_once 'init.php';

global $_config;

if (strpos($_SERVER['REQUEST_URI'], 'index.php') > 1) {
    die('Сайт розміщено не в кореневій папці');
}

try {
    $user = null;

    if (($_COOKIE['remember'] ?? false) && \App\System::getUser()->user_id == 0) {
        $arr = explode('_', $_COOKIE['remember']);
        //   $_config = parse_ini_file(_ROOT . 'config/config.ini', true);
        if ($arr[0] > 0 && $arr[1] === md5($arr[0] . \App\Helper::getSalt())) {
            $user = \App\Entity\User::load($arr[0]);
        }

        if ($user instanceof \App\Entity\User) {
            \App\Session::getSession()->clean();

            \App\System::setUser($user);
            $user->lastactive = time();
            $user->save() ;
        }
    }

    $mainpage='\App\Pages\Main';
    $user=\App\System::getUser() ;
    if(strlen($user->mainpage)>0) {
        $mainpage =  $user->mainpage;
    }

    $app = new \App\Application();
    $modules = \App\System::getOptions('modules');

    if ($modules['shop'] == 1 && \App\System::getOption('shop', 'usemainpage')==1) {
        $app->Run('\App\Modules\Shop\Pages\Catalog\Main', 0);
    } else {
        $app->Run($mainpage);
    }


} catch (Throwable $e) {
    if ($e instanceof \ADODB_Exception) {

        \ZDB\DB::getConnect()->RollbackTrans(); // откат транзакции
    }
    $msg = $e->getMessage();
    $logger->error($e);
    if ($e instanceof Throwable) {
        echo $e->getMessage() . '<br>';
        echo $e->getLine() . '<br>';
        echo $e->getFile() . '<br>';
    }

}
 
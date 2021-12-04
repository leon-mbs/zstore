<?php
 
 

 require_once 'init.php';

global $_config;

if (strpos($_SERVER['REQUEST_URI'], 'index.php') > 1) {
    die('Сайт размещен не в  корневой папке');
}
        
try {
 

    if ($_COOKIE['remember'] && \App\System::getUser()->user_id == 0) {
        $arr = explode('_', $_COOKIE['remember']);
        $_config = parse_ini_file(_ROOT . 'config/config.ini', true);
        if ($arr[0] > 0 && $arr[1] === md5($arr[0] . $_config['common']['salt'])) {
            $user = \App\Entity\User::load($arr[0]);
        }

        if ($user instanceof \App\Entity\User) {

            \App\System::setUser($user);

            //  $_SESSION['user_id'] = $user->user_id; //для  использования  вне  Application
            //   $_SESSION['userlogin'] = $user->userlogin; //для  использования  вне  Application
        }
    }

    $mainpage='\App\Pages\Main';
    $user=\App\System::getUser() ;
    if(strlen($user->mainpage)>0){
         $mainpage =  $user->mainpage;
    }
    
    $app = new \App\Application();

    if ($_config['modules']['shop'] == 1 && \App\System::getOption('shop','usemainpage')==1 ) {
        $app->Run('\App\Modules\Shop\Pages\Main');
    } else {
        $app->Run($mainpage);
    }

    /* } catch (\ZippyERP\System\Exception $e) {
      Logger::getLogger("main")->error($e->getMessage(), e);
      \ZippyERP\System\Application::Redirect('\\ZippyERP\\System\\Pages\\Error', $e->getMessage());
      } catch (\Zippy\Exception $e) {
      Logger::getLogger("main")->error($e->getMessage(), e);
      \ZippyERP\System\Application::Redirect('\\ZippyERP\\System\\Pages\\Error', $e->getMessage());
      } catch (ADODB_Exception $e) {

      \ZippyERP\System\Application::Redirect('\\ZippyERP\\System\\Pages\\Error', $e->getMessage());
     */
} catch (Throwable $e) {
    if ($e instanceof ADODB_Exception) {

        \ZDB\DB::getConnect()->CompleteTrans(false); // откат транзакции
    }
    $msg = $e->getMessage();
    $logger->error($e);
    if ($e instanceof Throwable) {
        echo $e->getMessage() . '<br>';
        echo $e->getLine() . '<br>';
        echo $e->getFile() . '<br>';
    }
}  
 
  
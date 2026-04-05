<?php

namespace App\Modules\DF\Public;

use App\Application as App;
use App\Helper;
use App\System;
use App\Entity\Customer;
use Zippy\Html\Link\ClickLink;

class Base extends \Zippy\Html\WebPage
{
    protected $_c=null;
    
    public function __construct( ) {

        \Zippy\Html\WebPage::__construct();
        $modules = \App\System::getOptions("modules");
        if($modules['df'] != 1) {
            http_response_code(404);
            die;
        } 
        $customer_id = System::getCustomer();
   
     
        if (($_COOKIE['remembercust'] ?? null) && $customer_id == 0) {
            $arr = explode('_', $_COOKIE['remembercust']);

            if ($arr[0] > 0 && $arr[1] === md5($arr[0] . Helper::getSalt())) {
                $customer = \App\Entity\Customer::load($arr[0]);
                \App\System::setCustomer($customer->customer_id)  ;
               

            }


        }     
     
     
   
        if ($customer_id == 0) {
            App::Redirect("\\App\\Modules\\DF\\Public\\Userlogin");
            return;
        }  
     
  
        $this->_c= Customer::load($customer_id);
   

        $this->_tvars['custname']  = $this->_c->customer_name;

    }

 
 

    //вывод ошибки,  используется   в дочерних страницах
    public function setError($msg, $p1 = "", $p2 = "") {

        System::setErrorMsg($msg);
    }

    public function setSuccess($msg, $p1 = "", $p2 = "") {

        System::setSuccessMsg($msg);
    }

    public function setWarn($msg, $p1 = "", $p2 = "") {

        System::setWarnMsg($msg);
    }

    public function setInfo($msg, $p1 = "", $p2 = "") {

        System::setInfoMsg($msg);
    }

    final protected function isError() {
        return strlen(System::getErrorMsg()) > 0;
    }

 

    protected function afterRender() {
        if (strlen(System::getErrorMsg()) > 0) {
            App::$app->getResponse()->addJavaScript("toastr.error('" . System::getErrorMsg() . "','',{timeOut:6000})        ", true);
        }
        if (strlen(System::getWarnMsg()) > 0) {
            App::$app->getResponse()->addJavaScript("toastr.warning('" . System::getWarnMsg() . "','',{timeOut:3000})        ", true);
        }
        if (strlen(System::getSuccesMsg()) > 0) {
            App::$app->getResponse()->addJavaScript("toastr.success('" . System::getSuccesMsg() . "','',{timeOut:1000})        ", true);
        }
        if (strlen(System::getInfoMsg()) > 0) {
            App::$app->getResponse()->addJavaScript("toastr.info('" . System::getInfoMsg() . "')        ", true);
        }


        $this->setError('');
        $this->setSuccess('');

        $this->setInfo('');
        $this->setWarn('');
    }

    //Перезагрузить страницу  с  клиента
    //например для  сброса  адресной строки  после  команды удаления
    final protected function resetURL() {
        \App\Application::$app->setReloadPage();
    }

    public function LogoutClick($sender) {
        System::setCustomer(0);
        setcookie("remembercust", '', 0);
        
        App::Redirect("\\App\\Modules\\DF\\Public\\UserLogin", 0);

    }


}

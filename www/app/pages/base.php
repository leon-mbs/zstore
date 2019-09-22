<?php

namespace App\Pages;

use \Zippy\Binding\PropertyBinding;
use \Zippy\Html\Label;
use \Zippy\Html\Panel;
use \Zippy\Html\Link\ClickLink;
use \App\Helper;
use \App\Application as App;
use \App\System;
use \App\Entity\User;

class Base extends \Zippy\Html\WebPage {

    public function __construct($params = null) {
        global $_config;


        \Zippy\Html\WebPage::__construct();


        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
            return;
        }



        $this->add(new ClickLink('logout', $this, 'LogoutClick'));
        $this->add(new Label('username', $user->username));


        $this->add(new ClickLink("pageinfo"));
 

        $this->_tvars["docmenu"] = Helper::generateMenu(1);
        $this->_tvars["repmenu"] = Helper::generateMenu(2);
        $this->_tvars["regmenu"] = Helper::generateMenu(3);
        $this->_tvars["refmenu"] = Helper::generateMenu(4);
        $this->_tvars["shopmenu"] = Helper::generateMenu(5);

        $this->_tvars["islogined"] = $user->user_id > 0;
        $this->_tvars["isadmin"] = $user->userlogin == 'admin';
      
        $pi = $this->getPageInfo();
        $this->_tvars["picontent"] = $pi;
        if (strlen($pi) == 0) {
            $this->pageinfo->setVisible(false);
        }

        $options = System::getOptions('common');
      
        if(($options['defstore'] > 0 && $options['defmf'] > 0 )  == false)
        {
           $this->setError("Не заданы в <a href=\"/index.php?p=App/Pages/Options\">настройках</a> склад или  касса.");
        }
        
        $this->_tvars["useset"] = $options['useset'] == 1;
        $this->_tvars["usesnumber"] = $options['usesnumber'] == 1;
        $this->_tvars["usescanner"] = $options['usescanner'] == 1;

        $this->_tvars["smart"] = Helper::generateSmartMenu();
        
 
        $this->_tvars["shop"] = $_config['modules']['shop'] == 1;
        $this->_tvars["ocstore"] = $_config['modules']['ocstore'] == 1;
        $this->_tvars["note"] = $_config['modules']['note'] == 1;
        $this->_tvars["issue"] = $_config['modules']['issue'] == 1;
        
        $this->_tvars["hideblock"] = false;
        
      }

    public function LogoutClick($sender) {
        setcookie("remember", '', 0);
        System::setUser(new \App\Entity\User());
        $_SESSION['user_id'] = 0;
        $_SESSION['userlogin'] = 'Гость';

        //$page = $this->getOwnerPage();
        //  $page = get_class($page)  ;
        App::Redirect("\\App\\Pages\\UserLogin");
        ;
        ;
        //    App::$app->getresponse()->toBack();
    }

    public function getPageInfo() {
        $class = explode("\\", get_class($this));
        $classname = $class[count($class) - 1];
        return \App\Helper::getMetaNotes($classname);
    }

    //вывод ошибки,  используется   в дочерних страницах
    public function setError($msg) {
        System::setErrorMsg($msg);
    }

    public function setSuccess($msg) {
        System::setSuccesMsg($msg);
    }

    public function setWarn($msg) {
        System::setWarnMsg($msg);
    }

    public function setInfo($msg) {
        System::setInfoMsg($msg);
    }

    final protected function isError() {
        return strlen(System::getErrorMsg()) > 0;
    }

    public function beforeRender() {
        $user = System::getUser();
        $this->_tvars['notcnt'] = \App\Entity\Notify::isNotify($user->user_id);
        
        $this->_tvars['alerterror']= "";
        $this->_tvars['alertwarning']= "";
        $this->_tvars['alertsuccess']= "";
        $this->_tvars['alertinfo']=  "";     
        
        if (strlen(System::getErrorMsg()) > 0)
            $this->_tvars['alerterror']= System::getErrorMsg();
        if (strlen(System::getWarnMsg()) > 0)
            $this->_tvars['alertwarning']= System::getWarnMsg();
        if (strlen(System::getSuccesMsg()) > 0)
            $this->_tvars['alertsuccess']= System::getSuccesMsg();
        if (strlen(System::getInfoMsg()) > 0)
            $this->_tvars['alertinfo']= System::getInfoMsg();

    }

    protected function afterRender() {
 

        $this->setError('');
        $this->setSuccess('');

        $this->setInfo('');
        $this->setWarn('');
        
        
        
    }

    //Перезагрузить страницу  с  клиента
    //например для  сброса  адресной строки  после  команды удаления
    protected final function resetURL() {
        \App\Application::$app->setReloadPage();
    }

}

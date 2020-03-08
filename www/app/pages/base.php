<?php

namespace App\Pages;

use \Zippy\Binding\PropertyBinding;
use \Zippy\Html\Label;
use \Zippy\Html\Panel;
use \Zippy\Html\Link\ClickLink;
use \App\Helper;
use \App\Application as App;
use \App\System;
use \App\Session;
use \App\Entity\User;

class Base extends \Zippy\Html\WebPage {

    public $branch_id = 0;

    public function __construct($params = null) {
        global $_config;


        \Zippy\Html\WebPage::__construct();


        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
            return;
        }

        $this->branch_id = Session::getSession()->branch_id;
        $blist = \App\Entity\Branch::getList(System::getUser()->user_id);
        if (count($blist) == 1) {      //если  одна
            $this->branch_id = array_pop(array_keys($blist));
            Session::getSession()->branch_id = $this->branch_id;
        }
        //форма  филиалов       
        $this->add(new \Zippy\Html\Form\Form('nbform'));
        $this->nbform->add(new \Zippy\Html\Form\DropDownChoice('nbbranch', $blist, $this->branch_id))->onChange($this, 'onnbFirm');

        $this->add(new ClickLink('logout', $this, 'LogoutClick'));
        $this->add(new Label('username', $user->username));

        $this->_tvars["docmenu"] = Helper::generateMenu(1);
        $this->_tvars["repmenu"] = Helper::generateMenu(2);
        $this->_tvars["regmenu"] = Helper::generateMenu(3);
        $this->_tvars["refmenu"] = Helper::generateMenu(4);
        $this->_tvars["sermenu"] = Helper::generateMenu(5);

        $this->_tvars["islogined"] = $user->user_id > 0;
        $this->_tvars["isadmin"] = $user->userlogin == 'admin';

        $options = System::getOptions('common');

        $this->_tvars["useset"] = $options['useset'] == 1;
        $this->_tvars["usesnumber"] = $options['usesnumber'] == 1;
        $this->_tvars["usescanner"] = $options['usescanner'] == 1;
        $this->_tvars["useimages"] = $options['useimages'] == 1;
        $this->_tvars["usebranch"] = $options['usebranch'] == 1;
        if ($this->_tvars["usebranch"] == false) {
            $this->branch_id = 0;
            Session::getSession()->branch_id = 0;
        }
        $this->_tvars["smart"] = Helper::generateSmartMenu();


        $this->_tvars["shop"] = $_config['modules']['shop'] == 1;
        $this->_tvars["ocstore"] = $_config['modules']['ocstore'] == 1;
        $this->_tvars["woocomerce"] = $_config['modules']['woocomerce'] == 1;
        $this->_tvars["note"] = $_config['modules']['note'] == 1;
        $this->_tvars["issue"] = $_config['modules']['issue'] == 1;

        if (strpos(System::getUser()->modules, 'shop') === false && System::getUser()->userlogin != 'admin') {
            $this->_tvars["shop"] = false;
        }
        if (strpos(System::getUser()->modules, 'note') === false && System::getUser()->userlogin != 'admin') {
            $this->_tvars["note"] = false;
        }
        if (strpos(System::getUser()->modules, 'issue') === false && System::getUser()->userlogin != 'admin') {
            $this->_tvars["issue"] = false;
        }
        if (strpos(System::getUser()->modules, 'ocstore') === false && System::getUser()->userlogin != 'admin') {
            $this->_tvars["ocstore"] = false;
        }
        if (strpos(System::getUser()->modules, 'woocomerce') === false && System::getUser()->userlogin != 'admin') {
            $this->_tvars["woocomerce"] = false;
        }

        $this->_tvars["hideblock"] = false; //для скрытия блока разметки  в  шаблоне страниц 
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

    public function onnbFirm($sender) {
        $branch_id = $sender->getValue();
        Session::getSession()->branch_id = $branch_id;

        $page = get_class($this);
        App::Redirect($page);
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

        $this->_tvars['alerterror'] = "";
        $this->_tvars['alertwarning'] = "";
        $this->_tvars['alertsuccess'] = "";
        $this->_tvars['alertinfo'] = "";

        if (strlen(System::getErrorMsg()) > 0) {
            $this->_tvars['alerterror'] = System::getErrorMsg();
            $this->goAnkor('');
        }
        if (strlen(System::getWarnMsg()) > 0) {
            $this->_tvars['alertwarning'] = System::getWarnMsg();
            $this->goAnkor('');
        }
        if (strlen(System::getSuccesMsg()) > 0) {
            $this->_tvars['alertsuccess'] = System::getSuccesMsg();
            $this->goAnkor('');
        }
        if (strlen(System::getInfoMsg()) > 0) {
            $this->_tvars['alertinfo'] = System::getInfoMsg();
            $this->goAnkor('');
        }
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

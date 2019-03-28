<?php

namespace App\Shop\Pages;

use \Zippy\Binding\PropertyBinding;
use \Zippy\Html\Label;
use \Zippy\Html\Panel;
use \Zippy\Html\Link\ClickLink;
use \App\Helper;
use \App\Application as App;
use \App\System;
use \App\Entity\User;

class Base extends \Zippy\Html\WebPage
{

    public function __construct($params = null) {

        \Zippy\Html\WebPage::__construct();

        $user = System::getUser();
        $this->_tvars["islogined"] = $user->user_id > 0;

        $this->add(new \Zippy\Html\Link\BookmarkableLink('shopcart', "/index.php?p=/App/Shop/Pages/Order"))->setVisible(false);
        ;


        $this->op = System::getOptions("shop");
        if (!is_array($this->op))
            $this->op = array();
    }

    public function getPageInfo() {
        return '';
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

    protected function beforeRender() {
        
    }

    protected function afterRender() {
        if (strlen(System::getErrorMsg()) > 0)
            App::$app->getResponse()->addJavaScript("toastr.error('" . System::getErrorMsg() . "')        ", true);
        if (strlen(System::getWarnMsg()) > 0)
            App::$app->getResponse()->addJavaScript("toastr.warning('" . System::getWarnMsg() . "')        ", true);
        if (strlen(System::getSuccesMsg()) > 0)
            App::$app->getResponse()->addJavaScript("toastr.success('" . System::getSuccesMsg() . "')        ", true);
        if (strlen(System::getInfoMsg()) > 0)
            App::$app->getResponse()->addJavaScript("toastr.info('" . System::getInfoMsg() . "')        ", true);



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

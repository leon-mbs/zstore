<?php

namespace App\Modules\Shop\Pages;

use App\Application as App;
use App\Helper;
use App\System;
use \App\Modules\Shop\Entity\Product;

class Base extends \Zippy\Html\WebPage
{

    public function __construct($params = null) {

        \Zippy\Html\WebPage::__construct();
        global $_config;

        $shop = System::getOptions("shop");
        if (!is_array($shop)) {
            $shop = array();
        }
        $user = System::getUser();
        if ($shop["uselogin"] == 1) {
            if ($user->user_id == 0) {
                App::Redirect("\\App\\Modules\\Shop\\Pages\\Userlogin");
                return;
            }
        }


        //  $this->_tvars["islogined"] = $user->user_id > 0;
        $this->_tvars["currencyname"] = $shop["currencyname"];
        $this->_tvars["notcnt"] = false;
        $this->_tvars["phone"] = strlen($shop["phone"]) > 0 ? $shop["phone"] : false;

        $this->add(new \Zippy\Html\Form\Form('searchform'));
        $this->searchform->add(new \Zippy\Html\Form\AutocompleteTextInput('searchitem'))->onText($this, 'onSearch');
        $this->searchform->searchitem->onChange($this, 'onSelect');
        $this->add(new \Zippy\Html\Link\BookmarkableLink('shopcart', "/index.php?p=/App/Modules/Shop/Pages/Order"))->setVisible(false);
        $this->add(new \Zippy\Html\Link\BookmarkableLink('showcompare', "/index.php?p=/App/Modules/Shop/Pages/Compare"))->setVisible(false);

        $this->op = System::getOptions("shop");


        $this->add(new \Zippy\Html\Link\BookmarkableLink('logo', "/"))->setVisible(strlen($this->op['logo']) > 0);
        $this->logo->setValue($this->op['logo']);
        $this->_tvars["shopname"] = $this->op['shopname'];
        $this->_tvars["aboutus"] = strlen($this->op['aboutus']) > 0;
        $this->_tvars["contact"] = strlen($this->op['contact']) > 0;
        $this->_tvars["delivery"] = strlen($this->op['delivery']) > 0;
        $this->_tvars["news"] = strlen($this->op['news']) > 0;
        $this->_tvars["usefilter"] = strlen($this->op['usefilter']) > 0;
        $this->_tvars["usefeedback"] = strlen($this->op['usefeedback']) > 0;
        $this->_tvars["isfood"] = $this->op['ordertype'] == 2;

        $this->_tvars["np"] = $_config['modules']['np'] == 1 && $this->op['ordertype'] != 2;

        if ($this->op['ordertype'] == 1) {
            $this->_tvars["delivery"] = false;
        }
        if ($this->op['ordertype'] == 2) {

            $this->_tvars["usefilter"] = false;
            $this->_tvars["usefeedback"] = false;
        }

    }

    public function onSearch(\Zippy\Html\Form\AutocompleteTextInput $sender) {
        $r = array();

        $text = Product::qstr('%' . $sender->getText() . '%');
        $code = Product::qstr($sender->getText());
        $list = Product::findArray('itemname', " disabled <>1 and  detail not  like '%<noshop>1</noshop>%' and  cat_id in(select cat_id from  item_cat where detail not  like '%<noshop>1</noshop>%' ) and    (    itemname like {$text} or item_code like {$code} or bar_code like {$code}  ) ");
        foreach ($list as $k => $v) {
            $r[$k] = $v;
        }
        return $r;
    }

    public function onSelect(\Zippy\Html\Form\AutocompleteTextInput $sender) {
        $key = $sender->getKey();
        if ($key > 0) {
            App::Redirect("\\App\\Modules\\Shop\\Pages\\ProductView", $key);
        }
    }

    //вывод ошибки,  используется   в дочерних страницах
    public function setError($msg, $p1 = "", $p2 = "") {
        $msg = Helper::l($msg, $p1, $p2);
        System::setErrorMsg($msg);
    }

    public function setSuccess($msg, $p1 = "", $p2 = "") {
        $msg = Helper::l($msg, $p1, $p2);
        System::setSuccessMsg($msg);
    }

    public function setWarn($msg, $p1 = "", $p2 = "") {
        $msg = Helper::l($msg, $p1, $p2);
        System::setWarnMsg($msg);
    }

    public function setInfo($msg, $p1 = "", $p2 = "") {
        $msg = Helper::l($msg, $p1, $p2);
        System::setInfoMsg($msg);
    }

    final protected function isError() {
        return strlen(System::getErrorMsg()) > 0;
    }

    protected function beforeRender() {
        $basket = \App\Modules\Shop\Basket::getBasket();
        $this->shopcart->setVisible($basket->isEmpty() == false);
        $this->showcompare->setVisible(\App\Modules\Shop\CompareList::getCompareList()->isEmpty() == false);

        $this->_tvars["notcnt"] = $basket->getItemCount();
    }

    protected function afterRender() {
        if (strlen(System::getErrorMsg()) > 0) {
            App::$app->getResponse()->addJavaScript("toastr.error('" . System::getErrorMsg() . "')        ", true);
        }
        if (strlen(System::getWarnMsg()) > 0) {
            App::$app->getResponse()->addJavaScript("toastr.warning('" . System::getWarnMsg() . "')        ", true);
        }
        if (strlen(System::getSuccesMsg()) > 0) {
            App::$app->getResponse()->addJavaScript("toastr.success('" . System::getSuccesMsg() . "')        ", true);
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
    protected final function resetURL() {
        \App\Application::$app->setReloadPage();
    }

}

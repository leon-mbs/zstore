<?php

namespace App\Modules\Shop\Pages\Catalog;

use App\Application as App;
use App\Helper;
use App\System;
use App\Modules\Shop\Entity\Product;
use Zippy\Html\Link\ClickLink;

class Base extends \Zippy\Html\WebPage
{
    public function __construct( ) {

        \Zippy\Html\WebPage::__construct();
        global $_config;
        $modules = \App\System::getOptions("modules");
        if($modules['shop'] != 1) {
            http_response_code(404);
            die;
        }
        $shop = System::getOptions("shop");
        if (!is_array($shop)) {
            $shop = array();
        }
        $customer_id =   System::getCustomer();


        if (($_COOKIE['remembercust'] ?? null) && $customer_id == 0) {
            $arr = explode('_', $_COOKIE['remembercust']);

            if ($arr[0] > 0 && $arr[1] === md5($arr[0] . Helper::getSalt())) {
                $customer = \App\Entity\Customer::load($arr[0]);
                \App\System::setCustomer($customer->customer_id)  ;
                \App\System::getSession()->custname = $customer->customer_name;

            }


        }

        if ($shop["uselogin"] == 1) {
            if ($customer_id == 0) {
                App::Redirect("\\App\\Modules\\Shop\\Pages\\Catalog\\Userlogin");
                return;
            }
        }


        $this->_tvars["islogined"] = $customer_id > 0;

        $this->_tvars["custname"] = System::getSession()->custname;


        $this->_tvars["currencyname"] = $shop["currencyname"] ?? '';
        $this->_tvars["basketcnt"] = false;
        $this->_tvars["comparecnt"] = false;
        $this->_tvars["phone"] = strlen($shop["phone"]) > 0 ? $shop["phone"] : false;
        $this->_tvars["usepayment"] = false;// ($shop["paysystem"]??0) > 0 ;
        $this->_tvars["wp"] = ($shop["paysystem"]??0) == 1;
        $this->_tvars["lp"] = ($shop["paysystem"]??0) == 2;
        $this->_tvars["qr"] = (($shop["paysystem"]??0) == 3 || ($shop["addqr"]??0) ==1) ;
        $this->_tvars["np"] = ($modules['np']??0) == 1;

        $this->add(new \Zippy\Html\Form\Form('searchform'));
        $this->searchform->add(new \Zippy\Html\Form\AutocompleteTextInput('searchitem'))->onText($this, 'onSearch');
        $this->searchform->searchitem->onChange($this, 'onSelect');
     
        $this->add(new \Zippy\Html\Form\Form('searchformmob'));
        $this->searchformmob->add(new \Zippy\Html\Form\AutocompleteTextInput('searchitemmob'))->onText($this, 'onSearch');
        $this->searchformmob->searchitemmob->onChange($this, 'onSelect');

        $this->add(new \Zippy\Html\Link\BookmarkableLink('shopcart', "/index.php?p=/App/Modules/Shop/Pages/Catalog/Order"))->setVisible(false);
        $this->add(new \Zippy\Html\Link\BookmarkableLink('showcompare', "/index.php?p=/App/Modules/Shop/Pages/Catalog/Compare"))->setVisible(false);

        $this->op = System::getOptions("shop");

        $this->add(new ClickLink('logout', $this, 'LogoutClick'));

        $this->_tvars["shopname"] = $this->op['shopname'];
        $this->_tvars["usefilter"] = $this->op['usefilter'] == 1;
        $this->_tvars["usefeedback"] = $this->op['usefeedback'] == 1;
        $this->_tvars["nouseimages"] =  $this->op['nouseimages'] ==1;
        $this->_tvars["isfood"] = $this->op['ordertype'] == 2;
        $this->_tvars["logo"] = false;
        if(strlen($this->op['logo']??'')>0) {
            $this->_tvars["logo"] = $this->op['logo'];
        }

        $this->_tvars["np"] = $modules['np'] == 1 && $this->op['ordertype'] != 2;

        if ($this->op['ordertype'] == 1) {
            $this->_tvars["delivery"] = false;
        }
        if ($this->op['ordertype'] == 2) {

            $this->_tvars["usefilter"] = false;
            $this->_tvars["usefeedback"] = false;
        }

        if(!is_array($shop["pages"])) {
            $shop["pages"] =  array();
        }
       
        $this->_tvars['pages'] =array();
        foreach($shop["pages"] as $p) {
            $link = _BASEURL .trim($p->link, "/");

            $this->_tvars['pages'][]=array('link'=> $link  ,'title'=>$p->title);
        }
        $this->_tvars['isblog']  = intval(\App\Modules\Shop\Entity\Article::findCnt('isactive=1') ) > 0 ;
        if(strlen($_COOKIE['zippy_shop'] ?? '')==0) {
            \App\Helper::insertstat(\App\Helper::STAT_HIT_SHOP, 0, 0) ;
            setcookie("zippy_shop", "visited", time() + 60 * 60 * 24);

        }

        if(strlen($_COOKIE['zippy_shop_unique'] ?? '')==0) {
            \App\Helper::insertstat(\App\Helper::STAT_NEW_SHOP, 0, 0) ;
            setcookie("zippy_shop_unique", "visited", 0);

        }



    }

    public function onSearch(\Zippy\Html\Form\AutocompleteTextInput $sender) {
        $r = array();

        $text = Product::qstr('%' . $sender->getText() . '%');
        $code = Product::qstr($sender->getText());
        $list = Product::findArray('itemname', " disabled <>1 and  detail not  like '%<noshop>1</noshop>%' and  cat_id in(select cat_id from  item_cat where detail not  like '%<noshop>1</noshop>%' ) and    (    itemname like {$text} or description like {$text} or item_code like {$code} or bar_code like {$code}  ) ");
        foreach ($list as $k => $v) {
            $r[$k] = $v;
        }
        return $r;
    }

    public function onSelect(\Zippy\Html\Form\AutocompleteTextInput $sender) {
        $key = $sender->getKey();
        if ($key > 0) {
            App::Redirect("\\App\\Modules\\Shop\\Pages\\Catalog\\ProductView", $key);
        }
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

    protected function beforeRender() {
        $basket = \App\Modules\Shop\Basket::getBasket();
        $comp = \App\Modules\Shop\CompareList::getCompareList();

        $this->shopcart->setVisible($basket->isEmpty() == false);
        $this->showcompare->setVisible($comp->isEmpty() == false);

        $this->_tvars["basketcnt"] = $basket->getItemCount();

        $this->_tvars["comparecnt"] = $comp->getItemCount();
    }

    protected function afterRender() {
        if (strlen(System::getErrorMsg()) > 0) {
            App::$app->getResponse()->addJavaScript("toastr.error('" . System::getErrorMsg() . "',{'timeOut':'5000'})        ", true);
        }
        if (strlen(System::getWarnMsg()) > 0) {
            App::$app->getResponse()->addJavaScript("toastr.warning('" . System::getWarnMsg() . "',{'timeOut':'3000'})        ", true);
        }
        if (strlen(System::getSuccesMsg()) > 0) {
            App::$app->getResponse()->addJavaScript("toastr.success('" . System::getSuccesMsg() . "',{'timeOut':'1000'})        ", true);
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
        \App\Modules\Shop\Basket::getBasket()->Empty();
        App::Redirect("\\App\\Modules\\Shop\\Pages\\Catalog\\Main", 0);

    }


}

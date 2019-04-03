<?php

namespace App\Shop\Pages;

use \Zippy\Binding\PropertyBinding as Bind;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use App\System;
use App\Application as App;

class Options extends \App\Pages\Base
{
 

    public function __construct() {
        parent::__construct();
        if (System::getUser()->acltype == 2) {
            App::RedirectHome();
            return;            
        }

 

        $this->add(new Form('shop'))->onSubmit($this, 'saveShopOnClick');
        $this->shop->add(new DropDownChoice('shopdefstore', \App\Entity\Store::getList()));
        $this->shop->add(new DropDownChoice('shopdefcust', \App\Entity\Customer::getList()));
        $this->shop->add(new DropDownChoice('shopdefpricetype', \App\Entity\Item::getPriceTypeList()));

     


        $shop = System::getOptions("shop");
        if (!is_array($shop))
            $shop = array();
        $this->shop->shopdefstore->setValue($shop['defstore']);
        $this->shop->shopdefcust->setValue($shop['defcust']);
        $this->shop->shopdefpricetype->setValue($shop['defpricetype']);

   }

   
    public function saveShopOnClick($sender) {
        $shop = array();

        //todo контрагент магазина, кому  нотификацию

        $shop['defcust'] = $this->shop->shopdefcust->getValue();
        $shop['defstore'] = $this->shop->shopdefstore->getValue();
        $shop['defpricetype'] = $this->shop->shopdefpricetype->getValue();


        System::setOptions("shop", $shop);
        $this->setSuccess('Сохранено');
    }

  
}

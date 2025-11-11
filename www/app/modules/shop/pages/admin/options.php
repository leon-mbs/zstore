<?php

namespace App\Modules\Shop\Pages\Admin;

use App\Application as App;
use App\Entity\Item;
use App\Modules\Shop\Entity\Product;
use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\File;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Panel;
use Zippy\Html\Label;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;

class Options extends \App\Pages\Base
{
    public $_pages = array();

    public function __construct() {
        parent::__construct();
        if (strpos(System::getUser()->modules, 'shop') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");
            App::RedirectError();
            return;
        }

        $shop = System::getOptions("shop");

        $this->add(new Form('shop'))->onSubmit($this, 'saveShopOnClick');

        $this->shop->add(new DropDownChoice('shopordertype', array(), 0));


        $this->shop->add(new DropDownChoice('shopdefuser', \App\Entity\User::findArray('username','disabled<>1','username')));
        $this->shop->add(new DropDownChoice('shopdefpricetype', \App\Entity\Item::getPriceTypeList()));
        $this->shop->add(new DropDownChoice('shopdefbranch', \App\Entity\Branch::getList()));
        $this->shop->add(new TextInput('email'));
        $this->shop->add(new TextInput('shopname'));
        $this->shop->add(new TextInput('currencyname'));
        $this->shop->add(new TextInput('phone'));
        $this->shop->add(new TextInput('pagesize'));
        $this->shop->add(new File('logo'));
        $this->shop->add(new CheckBox('uselogin'));
        $this->shop->add(new CheckBox('usefilter'));
        $this->shop->add(new CheckBox('usefeedback'));
        $this->shop->add(new CheckBox('usemainpage'));
        $this->shop->add(new CheckBox('nouseimages'));
        $this->shop->add(new CheckBox('noshowempty'));


        $this->shop->add(new DropDownChoice('salesource', \App\Helper::getSaleSources(), "0"));

        $this->shop->add(new DropDownChoice('defmf',\App\Entity\MoneyFund::getList(2), 0 ));
        $this->shop->add(new DropDownChoice('defstore',\App\Entity\Store::getList(),  0));
    


        $this->add(new Form('pay'))->onSubmit($this, 'savePayOnClick');
        $this->pay->add(new DropDownChoice('mfqr',\App\Entity\MoneyFund::getList(2), 0 )) ;
     



         if (!is_array($shop)) {
            $shop = array();
        }
        $this->_pages =    $shop['pages'];
        if (!is_array($this->_pages)) {
            $this->_pages = array();
        }
        if (intval($shop['defuser'] ??0) ==0) {
            $admin=\App\Entity\User::getByLogin('admin') ;
            $shop['defuser']   = $admin->user_id;
        }

        $this->shop->shopdefuser->setValue($shop['defuser']);
        $this->shop->shopdefbranch->setValue($shop['defbranch']);
        $this->shop->shopordertype->setValue($shop['ordertype']);
        $this->shop->defmf->setValue($shop['defmf']??0);
        $this->shop->defstore->setValue($shop['defstore']??0);
        $this->shop->shopdefpricetype->setValue($shop['defpricetype']);
        $this->shop->salesource->setValue($shop['salesource']);

        $this->shop->currencyname->setText($shop['currencyname']);
        $this->shop->uselogin->setChecked($shop['uselogin']);
        $this->shop->usefilter->setChecked($shop['usefilter']);
        $this->shop->noshowempty->setChecked($shop['noshowempty']??false);


        $this->shop->usefeedback->setChecked($shop['usefeedback']??false);
        $this->shop->usemainpage->setChecked($shop['usemainpage']??false);
        $this->shop->nouseimages->setChecked($shop['nouseimages']??false);
        $this->shop->shopname->setText($shop['shopname']);
        $this->shop->email->setText($shop['email']);
        $this->shop->currencyname->setText($shop['currencyname']);
        $this->shop->phone->setText($shop['phone']);
        $this->shop->pagesize->setText($shop['pagesize'] ?? 25);

        $this->pay->mfqr->setValue($shop['mf_id']??0);
  



    }

    public function savePayOnClick($sender) {
        $shop = System::getOptions("shop");
        if (!is_array($shop)) {
            $shop = array();
        }
   
        $shop['mf_id'] =  intval($sender->mfqr->getValue());
       
     
        System::setOptions("shop", $shop);
        $this->setSuccess('Збережено');

    }

    
    public function saveShopOnClick($sender) {
        $shop = System::getOptions("shop");
        if (!is_array($shop)) {
            $shop = array();
        }


        $shop['defuser'] = $this->shop->shopdefuser->getValue();
        $shop['defbranch'] = $this->shop->shopdefbranch->getValue();
        $shop['ordertype'] = $this->shop->shopordertype->getValue();
        $shop['defpricetype'] = $this->shop->shopdefpricetype->getValue();
        $shop['defmf'] = $this->shop->defmf->getValue();
        $shop['defstore'] = $this->shop->defstore->getValue();
        $shop['salesource'] = $this->shop->salesource->getValue();

        $shop['email'] = $this->shop->email->getText();
        $shop['shopname'] = $this->shop->shopname->getText();
        $shop['currencyname'] = $this->shop->currencyname->getText();
        $shop['phone'] = $this->shop->phone->getText();
        $shop['pagesize'] = $this->shop->pagesize->getText();
        $shop['uselogin'] = $this->shop->uselogin->isChecked() ? 1 : 0;
        $shop['usefilter'] = $this->shop->usefilter->isChecked() ? 1 : 0;

        $shop['usefeedback'] = $this->shop->usefeedback->isChecked() ? 1 : 0;
        $shop['usemainpage'] = $this->shop->usemainpage->isChecked() ? 1 : 0;
        $shop['nouseimages'] = $this->shop->nouseimages->isChecked() ? 1 : 0;
        $shop['noshowempty'] = $this->shop->noshowempty->isChecked() ? 1 : 0;

        if(intval($shop['defbranch'])==0 &&  $this->_tvars["usebranch"]==true) {
            $this->setError('Не вказана філія');
            return;
        }
        

        $file = $sender->logo->getFile();
        if (strlen($file["tmp_name"]) > 0) {
            $imagedata = getimagesize($file["tmp_name"]);

            if (preg_match('/(gif|png|jpeg)$/', $imagedata['mime']) == 0) {
                $this->setError('Невірний формат');
                return;
            }

         

            $name = basename($file["name"]);
            move_uploaded_file($file["tmp_name"], _ROOT . "upload/" . $name);

            $shop['logo'] = "/upload/" . $name;
        }
        System::setOptions("shop", $shop);
        $this->setSuccess('Збережено');
    }




}

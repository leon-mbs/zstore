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


        $this->add(new Form('shop'))->onSubmit($this, 'saveShopOnClick');

        $this->shop->add(new DropDownChoice('shopordertype', array(), 0));
        

        $this->shop->add(new DropDownChoice('shopdefpricetype', \App\Entity\Item::getPriceTypeList()));
        $this->shop->add(new DropDownChoice('shopdefbranch', \App\Entity\Branch::getList()));
        $this->shop->add(new TextInput('email'));
        $this->shop->add(new TextInput('shopname'));
        $this->shop->add(new TextInput('currencyname'));
        $this->shop->add(new TextInput('phone'));
        $this->shop->add(new File('logo'));
        $this->shop->add(new CheckBox('uselogin'));
        $this->shop->add(new CheckBox('usefilter'));
        $this->shop->add(new CheckBox('usefeedback'));
        $this->shop->add(new CheckBox('usemainpage'));
        $this->shop->add(new CheckBox('nouseimages'));
        $this->shop->add(new DropDownChoice('salesource', \App\Helper::getSaleSources(), "0"));
        
        
        $this->add(new Form('pay'))->onSubmit($this, 'savePayOnClick');
        $this->pay->add(new DropDownChoice('paysystem',array() ))->onChange($this, 'onPaySystem');
        $this->pay->add(new DropDownChoice('mf', \App\Entity\MoneyFund::getList(2) ));
        $this->pay->add(new TextInput('lqpublic'  ));
        $this->pay->add(new TextInput('lqpriv'  ));
        $this->pay->add(new TextInput('wpsecret'  ));
        $this->pay->add(new TextInput('wpmacc'  ));
        $this->pay->add(new TextInput('wpsite'  ));
        
        
   
 
        $shop = System::getOptions("shop");
        if (!is_array($shop)) {
            $shop = array();
        }
        $this->_pages =    $shop['pages'];
        if (!is_array($this->_pages)) {
            $this->_pages = array();
        }   

        $this->shop->shopdefbranch->setValue($shop['defbranch']);
        $this->shop->shopordertype->setValue($shop['ordertype']);
        $this->shop->shopdefpricetype->setValue($shop['defpricetype']);
        $this->shop->salesource->setValue($shop['salesource']);
        $this->shop->currencyname->setText($shop['currencyname']);
        $this->shop->uselogin->setChecked($shop['uselogin']);
        $this->shop->usefilter->setChecked($shop['usefilter']);
        
        $this->shop->usefeedback->setChecked($shop['usefeedback']);
        $this->shop->usemainpage->setChecked($shop['usemainpage']);
        $this->shop->nouseimages->setChecked($shop['nouseimages']);
        $this->shop->shopname->setText($shop['shopname']);
        $this->shop->email->setText($shop['email']);
        $this->shop->currencyname->setText($shop['currencyname']);
        $this->shop->phone->setText($shop['phone']);
        
        $this->pay->paysystem->setValue($shop['paysystem']);
        $this->pay->mf->setValue($shop['mf_id']);
        $this->pay->lqpublic->setText($shop['lqpublic']);
        $this->pay->lqpriv->setText($shop['lqpriv']);
        $this->pay->wpsecret->setText($shop['wpsecret']);
        $this->pay->wpmacc->setText($shop['wpmacc']);
        $this->pay->wpsite->setText($shop['wpsite']);
        $this->onPaySystem(null);
        

    
    }

    public function savePayOnClick($sender) {
        $shop = System::getOptions("shop");
        if (!is_array($shop)) {
            $shop = array();
        }
        $shop['paysystem'] = $sender->paysystem->getValue();
        $shop['mf_id'] =  intval($sender->mf->getValue() ); 
        if($shop['mf_id']==0 && $shop['paysystem'] > 0) {
            $this->setError('Не обрано касу');
            return;
        }
        $shop['lqpriv'] =  $sender->lqpriv->getText() ; 
        $shop['lqpublic'] = $sender->lqpublic->getText() ; 
        $shop['wpsecret'] = $sender->wpsecret->getText() ; 
        $shop['wpmacc'] = $sender->wpmacc->getText() ; 
        $shop['wpsite'] = $sender->wpsite->getText() ; 

        System::setOptions("shop", $shop);
        $this->setSuccess('Збережено');
        
    }
    
    public function onPaySystem($sender) {
   
         $ps = intval($this->pay->paysystem->getValue()) ;
         $this->pay->mf->setVisible($ps>0);
         $this->pay->lqpriv->setVisible($ps==2);
         $this->pay->lqpublic->setVisible($ps==2);
         $this->pay->wpsecret->setVisible($ps==1);
         $this->pay->wpmacc->setVisible($ps==1);
         $this->pay->wpsite->setVisible($ps==1);

    }    
    
    public function saveShopOnClick($sender) {
        $shop = System::getOptions("shop");
        if (!is_array($shop)) {
            $shop = array();
        }


        $shop['defbranch'] = $this->shop->shopdefbranch->getValue();
        $shop['ordertype'] = $this->shop->shopordertype->getValue();
        $shop['defpricetype'] = $this->shop->shopdefpricetype->getValue();
        $shop['salesource'] = $this->shop->salesource->getValue();
        $shop['email'] = $this->shop->email->getText();
        $shop['shopname'] = $this->shop->shopname->getText();
        $shop['currencyname'] = $this->shop->currencyname->getText();
        $shop['phone'] = $this->shop->phone->getText();
        $shop['uselogin'] = $this->shop->uselogin->isChecked() ? 1 : 0;
        $shop['usefilter'] = $this->shop->usefilter->isChecked() ? 1 : 0;
        
        $shop['usefeedback'] = $this->shop->usefeedback->isChecked() ? 1 : 0;
        $shop['usemainpage'] = $this->shop->usemainpage->isChecked() ? 1 : 0;
        $shop['nouseimages'] = $this->shop->nouseimages->isChecked() ? 1 : 0;

        $file = $sender->logo->getFile();
        if (strlen($file["tmp_name"]) > 0) {
            $imagedata = getimagesize($file["tmp_name"]);

            if (preg_match('/(gif|png|jpeg)$/', $imagedata['mime']) == 0) {
                $this->setError('Невірний формат');
                return;
            }

            if ($imagedata[0] * $imagedata[1] > 10000000) {
                $this->setError('Занадто великий розмір зображення');
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

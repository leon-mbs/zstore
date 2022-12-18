<?php

namespace App\Pages;

use App\Application as App;
use App\Helper as H;
use App\System;
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;

use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\File;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

use Zippy\Html\Panel;

class Options extends \App\Pages\Base
{

    private $metadatads;
    public  $pricelist        = array();
    public  $_vallist       = array();
    public  $_salesourceslist = array();

    public function __construct() {
        parent::__construct();
        if (System::getUser()->userlogin != 'admin') {
            System::setErrorMsg(H::l('onlyadminpage'));
            App::RedirectError();
            return false;
        }

        $this->add(new Form('common'))->onSubmit($this, 'saveCommonOnClick');
        $this->common->add(new DropDownChoice('qtydigits'));
        $this->common->add(new DropDownChoice('amdigits'));
        $this->common->add(new DropDownChoice('dateformat'));
        
  
        $this->common->add(new DropDownChoice('phonel', array('10' => '10', '12' => '12'), '10'));
        $pt = array(
            "1" => H::l('opt_lastprice'),
            "2" => H::l('opt_partion')
        );
        $this->common->add(new DropDownChoice('partiontype', $pt, "1"));

        $this->common->add(new CheckBox('autoarticle'));
        $this->common->add(new CheckBox('usesnumber'));

        $this->common->add(new CheckBox('useval'));
        $this->common->add(new TextInput('shopname'));

        $this->common->add(new CheckBox('useimages'));
        $this->common->add(new CheckBox('usescanner'));
        $this->common->add(new CheckBox('usemobilescanner'));
        $this->common->add(new CheckBox('usebranch'));
        $this->common->add(new CheckBox('usecattree'));
        $this->common->add(new CheckBox('showactiveusers'));
        $this->common->add(new CheckBox('showchat'));


        
        $this->common->add(new CheckBox('printoutqrcode'));
        $this->common->add(new CheckBox('nocheckarticle'));
        
        $this->common->add(new CheckBox('allowminus'));
        $this->common->add(new CheckBox('noallowfiz'));
        $this->common->add(new CheckBox('capcha'));
        $this->common->add(new CheckBox('numberttn'));
        $this->common->add(new TextInput('price1'));
        $this->common->add(new TextInput('price2'));
        $this->common->add(new TextInput('price3'));
        $this->common->add(new TextInput('price4'));
        $this->common->add(new TextInput('price5'));
        $this->common->add(new TextInput('defprice'));

        $this->common->add(new TextInput('ts_break'));
        $this->common->add(new TextInput('ts_start'));
        $this->common->add(new TextInput('ts_end'));
        $this->common->add(new TextArea('checkslogan'));

        $common = System::getOptions("common");
        if (!is_array($common)) {
            $common = array();
        }

        $this->common->qtydigits->setValue($common['qtydigits']);
        $this->common->amdigits->setValue($common['amdigits']);
        $this->common->dateformat->setValue($common['dateformat']);
        $this->common->partiontype->setValue($common['partiontype']);
        
        $this->common->phonel->setValue($common['phonel']);

        $this->common->price1->setText($common['price1']);
        $this->common->price2->setText($common['price2']);
        $this->common->price3->setText($common['price3']);
        $this->common->price4->setText($common['price4']);
        $this->common->price5->setText($common['price5']);
        $this->common->defprice->setText($common['defprice']);
        $this->common->shopname->setText($common['shopname']);

        $this->common->autoarticle->setChecked($common['autoarticle']);

        $this->common->usesnumber->setChecked($common['usesnumber']);


        
        $this->common->printoutqrcode->setChecked($common['printoutqrcode']);
        $this->common->nocheckarticle->setChecked($common['nocheckarticle']);
        
        $this->common->showactiveusers->setChecked($common['showactiveusers']);
        $this->common->showchat->setChecked($common['showchat']);
        $this->common->usecattree->setChecked($common['usecattree']);
        $this->common->usescanner->setChecked($common['usescanner']);
        $this->common->usemobilescanner->setChecked($common['usemobilescanner']);
        $this->common->useimages->setChecked($common['useimages']);
        $this->common->usebranch->setChecked($common['usebranch']);
        $this->common->noallowfiz->setChecked($common['noallowfiz']);
        $this->common->allowminus->setChecked($common['allowminus']);
        $this->common->capcha->setChecked($common['capcha']);
        $this->common->numberttn->setChecked($common['numberttn']);
        $this->common->useval->setChecked($common['useval']);

        $this->common->ts_break->setText($common['ts_break'] == null ? '60' : $common['ts_break']);
        $this->common->ts_start->setText($common['ts_start'] == null ? '09:00' : $common['ts_start']);
        $this->common->ts_end->setText($common['ts_end'] == null ? '18:00' : $common['ts_end']);
        $this->common->checkslogan->setText($common['checkslogan']);

        //валюты
  
        $this->add(new Form('valform'));
        $this->valform->add(new SubmitLink('valadd' , $this,'onValAdd'));
        $this->valform->add(new SubmitButton('saveval'  ))->onClick($this, 'saveValOnClick');

        $this->valform->add(new CheckBox('valprice'));

        $val = System::getOptions("val");
        if (!is_array($val)) {
            $val = array();
        }
        if(!is_array($val['vallist'])) $val['vallist'] = array();
        
        $this->_vallist = $val['vallist'] ;
        $this->valform->add(new \Zippy\Html\DataList\DataView('vallist', new \Zippy\Html\DataList\ArrayDataSource($this, '_vallist'), $this, "onValRow"));
        $this->valform->vallist->Reload();
      
        $this->valform->valprice->setChecked($val['valprice']);

        //печать
        $this->add(new Form('printer'));


 
        $this->printer->add(new TextInput('pmaxname'));
        $this->printer->add(new DropDownChoice('pricetype', \App\Entity\Item::getPriceTypeList()));
        $this->printer->add(new DropDownChoice('barcodetype', array('EAN13' => 'EAN13', 'C128' => 'Code128', 'C39' => 'Code39'), 'Code128'));


        $this->printer->add(new CheckBox('pname'));
        $this->printer->add(new CheckBox('pcode'));
        $this->printer->add(new CheckBox('pbarcode'));
        $this->printer->add(new CheckBox('pprice'));
        $this->printer->add(new CheckBox('pqrcode'));
        $this->printer->add(new CheckBox('pcolor'));
        $this->printer->add(new SubmitButton('savep'))->onClick($this, 'savePrinterOnClick');

        $printer = System::getOptions("printer");
        if (!is_array($printer)) {
            $printer = array();
        }

   
        $this->printer->pmaxname->setText($printer['pmaxname']);
        $this->printer->pricetype->setValue($printer['pricetype']);
        $this->printer->barcodetype->setValue($printer['barcodetype']);


        $this->printer->pname->setChecked($printer['pname']);
        $this->printer->pcode->setChecked($printer['pcode']);
        $this->printer->pbarcode->setChecked($printer['pbarcode']);
        $this->printer->pqrcode->setChecked($printer['pqrcode']);
        $this->printer->pprice->setChecked($printer['pprice']);
        $this->printer->pcolor->setChecked($printer['pcolor']);


        
        //API
        $this->add(new Form('api'))->onSubmit($this, 'saveApiOnClick');

        $this->api->add(new TextInput('akey'));
        $this->api->add(new TextInput('aexp'));
        $this->api->add(new DropDownChoice('atype', array('1' => H::l('apijwt'), '2' => H::l('apibasic'), '3' => H::l('apinologin')), 1))->onChange($this, 'onApiType');
        $api = System::getOptions("api");
        if (!is_array($api)) {
            $api = array('exp' => 60, 'key' => 'qwerty', 'atype' => 1);
        }
        $this->api->akey->setText($api['key']);
        $this->api->aexp->setValue($api['exp']);
        $this->api->atype->setValue($api['atype']);

        $this->onApiType($this->api->atype);

        //SMS
        $this->add(new Form('sms'));

        $this->sms->add(new SubmitButton('smssubmit'))->onClick($this, 'saveSMSOnClick');
        $this->sms->add(new SubmitButton('smstest'))->onClick($this, 'testSMSOnClick');
        $this->sms->add(new Label('semysmssite'));
        $this->sms->add(new Label('smsclubsite'));
        $this->sms->add(new Label('smsflysite'));
        $this->sms->add(new TextInput('smsclubtoken'));
        $this->sms->add(new TextInput('smsclublogin'));
        $this->sms->add(new TextInput('smsclubpass'));
        $this->sms->add(new TextInput('smscluban'));
        $this->sms->add(new TextInput('smsclubvan'));
        $this->sms->add(new TextInput('smssemytoken'));
        $this->sms->add(new TextInput('smssemydevid'));
        $this->sms->add(new TextInput('smstestphone'));
        $this->sms->add(new TextInput('smstesttext'));
        $this->sms->add(new TextInput('flysmslogin'));
        $this->sms->add(new TextInput('flysmspass'));
        $this->sms->add(new TextInput('flysmsan'));
        $this->sms->add(new DropDownChoice('smstype', array('1' => "SemySMS",  '2' => "SMSClub",  '3' => 'SMS-Fly'), 0))->onChange($this, 'onSMSType');
        $sms = System::getOptions("sms");

        $this->sms->smssemytoken->setText($sms['smssemytoken']);
        $this->sms->smssemydevid->setText($sms['smssemydevid']);
        $this->sms->flysmslogin->setText($sms['flysmslogin']);
        $this->sms->flysmspass->setText($sms['flysmspass']);
        $this->sms->flysmsan->setText($sms['flysmsan']);
        $this->sms->smsclubtoken->setText($sms['smsclubtoken']);
        $this->sms->smsclublogin->setText($sms['smsclublogin']);
        $this->sms->smsclubpass->setText($sms['smsclubpass']);
        $this->sms->smscluban->setText($sms['smscluban']);
        $this->sms->smsclubvan->setText($sms['smsclubvan']);

        $this->sms->smstype->setValue($sms['smstype']);

        $this->onSMSType($this->sms->smstype);

        //общепит
        $food = System::getOptions("food");
        if (!is_array($food)) {
            $food = array();
        }
        $this->add(new Form('food'))->onSubmit($this, 'onFood');
        $this->food->add(new DropDownChoice('foodpricetype', \App\Entity\Item::getPriceTypeList(), $food['pricetype']));
        $this->food->add(new DropDownChoice('foodworktype', array(), $food['worktype']));
        $this->food->add(new CheckBox('fooddelivery', $food['delivery']));
        $this->food->add(new CheckBox('foodtables', $food['tables']));
        $this->food->add(new CheckBox('foodpack', $food['pack']));

        //источники  продаж
        
        $this->add(new Form('salesourcesform'));
        $this->salesourcesform->add(new SubmitButton('salesourcesave'))->onClick($this, 'OnSaveSaleSource');
        $this->salesourcesform->add(new SubmitLink('addnewsalesource'))->onClick($this, 'OnAddSaleSource');

        $this->salesourcesform->add(new DataView('salesourceslist', new ArrayDataSource(new Bind($this, '_salesourceslist')), $this, 'salesourceListOnRow'));

        $this->_salesourceslist = $common['salesources'];
        if (is_array($this->_salesourceslist) == false) {
            $this->_salesourceslist = array();
        }

        $this->salesourcesform->salesourceslist->Reload();

        //модули
        $modules = System::getOptions("modules");
         
        $this->add(new Form('modules'))->onSubmit($this, 'onModules');
        $this->modules->add(new CheckBox('modocstore', $modules['ocstore']));
        $this->modules->add(new CheckBox('modshop', $modules['shop']));
        $this->modules->add(new CheckBox('modnote', $modules['note']));
        $this->modules->add(new CheckBox('modissue', $modules['issue']));
        $this->modules->add(new CheckBox('modwoocomerce', $modules['woocomerce']));
        $this->modules->add(new CheckBox('modppo', $modules['ppo']));
        $this->modules->add(new CheckBox('modnp', $modules['np']));
        $this->modules->add(new CheckBox('modpromua', $modules['promua']));
        $this->modules->add(new CheckBox('modpaperless', $modules['paperless']));
       
        
        
    }

    public function saveCommonOnClick($sender) {
        $common = System::getOptions("common");
        if (!is_array($common)) {
            $common = array();
        }

        $common['qtydigits'] = $this->common->qtydigits->getValue();
        $common['amdigits'] = $this->common->amdigits->getValue();
        $common['dateformat'] = $this->common->dateformat->getValue();
        $common['partiontype'] = $this->common->partiontype->getValue();
        
        $common['phonel'] = $this->common->phonel->getValue();

        $common['price1'] = trim($this->common->price1->getText() );
        $common['price2'] = trim($this->common->price2->getText() );
        $common['price3'] = trim($this->common->price3->getText() );
        $common['price4'] = trim($this->common->price4->getText() );
        $common['price5'] = trim($this->common->price5->getText() );
        $common['defprice'] = $this->common->defprice->getText();
        $common['shopname'] = $this->common->shopname->getText();
        $common['ts_break'] = $this->common->ts_break->getText();
        $common['ts_start'] = $this->common->ts_start->getText();
        $common['ts_end'] = $this->common->ts_end->getText();
        $common['checkslogan'] = trim($this->common->checkslogan->getText() );

 
        $common['autoarticle'] = $this->common->autoarticle->isChecked() ? 1 : 0;

        $common['usesnumber'] = $this->common->usesnumber->isChecked() ? 1 : 0;
        $common['usescanner'] = $this->common->usescanner->isChecked() ? 1 : 0;
        $common['usemobilescanner'] = $this->common->usemobilescanner->isChecked() ? 1 : 0;
        $common['useimages'] = $this->common->useimages->isChecked() ? 1 : 0;

        $common['printoutqrcode'] = $this->common->printoutqrcode->isChecked() ? 1 : 0;
        
        $common['nocheckarticle'] = $this->common->nocheckarticle->isChecked() ? 1 : 0;

        $common['showactiveusers'] = $this->common->showactiveusers->isChecked() ? 1 : 0;
        $common['showchat'] = $this->common->showchat->isChecked() ? 1 : 0;
        $common['usecattree'] = $this->common->usecattree->isChecked() ? 1 : 0;
        $common['usebranch'] = $this->common->usebranch->isChecked() ? 1 : 0;
        $common['noallowfiz'] = $this->common->noallowfiz->isChecked() ? 1 : 0;
        $common['allowminus'] = $this->common->allowminus->isChecked() ? 1 : 0;
        $common['useval'] = $this->common->useval->isChecked() ? 1 : 0;
        $common['capcha'] = $this->common->capcha->isChecked() ? 1 : 0;
        $common['numberttn'] = $this->common->numberttn->isChecked() ? 1 : 0;

        System::setOptions("common", $common);

        $this->_tvars["useval"] = $common['useval'] == 1;

        $this->setSuccess('saved');
        System::setCache('labels', null);
        App::Redirect("\\App\\Pages\\Options");
        
    }

 
 
    
    public function savePrinterOnClick($sender) {
        $printer = array();
 

        $printer['pmaxname'] = $this->printer->pmaxname->getText();
        $printer['pricetype'] = $this->printer->pricetype->getValue();
        $printer['barcodetype'] = $this->printer->barcodetype->getValue();


        $printer['pname'] = $this->printer->pname->isChecked() ? 1 : 0;
        $printer['pcode'] = $this->printer->pcode->isChecked() ? 1 : 0;
        $printer['pbarcode'] = $this->printer->pbarcode->isChecked() ? 1 : 0;
        $printer['pqrcode'] = $this->printer->pqrcode->isChecked() ? 1 : 0;
        $printer['pprice'] = $this->printer->pprice->isChecked() ? 1 : 0;
        $printer['pcolor'] = $this->printer->pcolor->isChecked() ? 1 : 0;
        System::setOptions("printer", $printer);
        $this->setSuccess('saved');
    }

    public function onApiType($sender) {
        $type = $this->api->atype->getValue();
        $this->api->aexp->setVisible($type == 1);
        $this->api->akey->setVisible($type == 1);

      //  $this->goAnkor('atype');
    }

    public function saveApiOnClick($sender) {
        $api = array();
        $api['exp'] = $this->api->aexp->getText();
        $api['key'] = $this->api->akey->getText();
        $api['atype'] = $this->api->atype->getValue();

        System::setOptions("api", $api);
        $this->setSuccess('saved');
    }

    public function onSMSType($sender) {
        $type = $this->sms->smstype->getValue();
        $this->sms->smssemytoken->setVisible($type == 1);
        $this->sms->smssemydevid->setVisible($type == 1);
        $this->sms->smsclubtoken->setVisible($type == 2);
        $this->sms->smsclublogin->setVisible($type == 2);
        $this->sms->smsclubpass->setVisible($type == 2);
        $this->sms->smscluban->setVisible($type == 2);
        $this->sms->smsclubvan->setVisible($type == 2);
        $this->sms->flysmslogin->setVisible($type == 3);
        $this->sms->flysmspass->setVisible($type == 3);
        $this->sms->flysmsan->setVisible($type == 3);

        $this->sms->semysmssite->setVisible($type == 1);
        $this->sms->smsclubsite->setVisible($type == 2);
        $this->sms->smsflysite->setVisible($type == 3);

        //  $this->goAnkor('atype');
    }

    public function saveSMSOnClick($sender) {
        $sms = array();
        $sms['smsclubtoken'] = $this->sms->smsclubtoken->getText();
        $sms['smsclublogin'] = $this->sms->smsclublogin->getText();
        $sms['smsclubpass'] = $this->sms->smsclubpass->getText();
        $sms['smscluban'] = $this->sms->smscluban->getText();
        $sms['smsclubvan'] = $this->sms->smsclubvan->getText();
        $sms['smssemytoken'] = $this->sms->smssemytoken->getText();
        $sms['smssemydevid'] = $this->sms->smssemydevid->getText();
        $sms['flysmslogin'] = $this->sms->flysmslogin->getText();
        $sms['flysmspass'] = $this->sms->flysmspass->getText();
        $sms['flysmsan'] = $this->sms->flysmsan->getText();
        $sms['smstype'] = $this->sms->smstype->getValue();

        System::setOptions("sms", $sms);
        $this->setSuccess('saved');
    }


    public function testSMSOnClick($sender) {

        $res = \App\Entity\Subscribe::sendSMS($this->sms->smstestphone->getText(), $this->sms->smstesttext->getText());
        if (strlen($res) == 0) {
            $this->setSuccess('success');
            $res = \App\Entity\Subscribe::sendViber($this->sms->smstestphone->getText(), $this->sms->smstesttext->getText());
            
        } else {
            $this->setError($res);
        }
        
    }


    
    public function onFood($sender) {
        $food = array();
        $food['worktype'] = $sender->foodworktype->getValue();
        $food['pricetype'] = $sender->foodpricetype->getValue();
        $food['delivery'] = $sender->fooddelivery->isChecked() ? 1 : 0;
        $food['tables'] = $sender->foodtables->isChecked() ? 1 : 0;

        $food['pack'] = $sender->foodpack->isChecked() ? 1 : 0;


        System::setOptions("food", $food);
        $this->setSuccess('saved');
    }


    public function OnAddSaleSource($sender) {
        $ls = new \App\DataItem();
        $ls->name = '';
        $ls->id = time();
        $this->_salesourceslist[$ls->id] = $ls;
        $this->salesourcesform->salesourceslist->Reload();
        $this->goAnkor('salesourcesform');
    }


    public function salesourceListOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new TextInput('salesourcename', new Bind($item, 'name')));
        $row->add(new ClickLink('delsalesource', $this, 'onDelSaleSource'));
    }

    public function onDelSaleSource($sender) {
        $item = $sender->getOwner()->getDataItem();

        $this->_salesourceslist = array_diff_key($this->_salesourceslist, array($item->id => $this->_salesourceslist[$item->id]));

        $this->salesourcesform->salesourceslist->Reload();
        $this->goAnkor('salesourcesform');
    }

    public function OnSaveSaleSource($sender) {
        
        $common = System::getOptions('common');
        $common['salesources']  = $this->_salesourceslist;
        System::setOptions("common", $common);


        $this->setSuccess('saved');
    }
    
    
    public function onValRow($row) {
        $val = $row->getDataitem();
        $row->add(new TextInput('valcode', new Bind($val, 'code') ));
        $row->add(new TextInput('valname', new Bind($val, 'name')));
        $row->add(new TextInput('valrate', new Bind($val, 'rate')));
        $row->add(new ClickLink('valdel' , $this,'onValDel'));
 
    }    
  
    public function onValDel($sender) {
        $val = $sender->getOwner()->getDataItem() ;
        $this->_vallist = array_diff_key($this->_vallist, array($val->id => $this->_vallist[$val->id]));
    
        $this->valform->vallist->Reload();
    
    }
    public function onValAdd($sender) {
        $val=new  \App\DataItem() ;
        $val->code='';
        $val->name='';
        $val->rate='';
        $val->id=time();
        
        
        $this->_vallist[$val->id] = $val;
        $this->valform->vallist->Reload();
    
    }
    
    public function saveValOnClick($sender) {
        $val = array();
 
        $val['vallist'] = $this->_vallist;
        $val['valprice'] = $this->valform->valprice->isChecked() ? 1 : 0;

        System::setOptions("val", $val);
        $this->setSuccess('saved');
    }
    
    public function onModules($sender) {
         $modules = System::getOptions("modules");
         $modules['ocstore'] = $sender->modocstore->isChecked() ? 1:0;
         $modules['shop'] = $sender->modshop->isChecked() ? 1:0;
         $modules['woocomerce'] = $sender->modwoocomerce->isChecked() ? 1:0;
         $modules['ppo'] = $sender->modppo->isChecked() ? 1:0;
         $modules['np'] = $sender->modnp->isChecked() ? 1:0;
         $modules['promua'] = $sender->modpromua->isChecked() ? 1:0;
         $modules['paperless'] = $sender->modpaperless->isChecked() ? 1:0;
         $modules['issue'] = $sender->modissue->isChecked() ? 1:0;
         $modules['note'] = $sender->modnote->isChecked() ? 1:0;
         
         System::setOptions("modules", $modules);
         $this->setSuccess('saved');      
         App::Redirect("\\App\\Pages\\Options");
         
    }    
    
}

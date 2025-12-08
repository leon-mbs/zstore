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
    public $pricelist        = array();
    public $_vallist       = array();
    public $_salesourceslist = array();

    public function __construct() {
        parent::__construct();
        if (System::getUser()->rolename != 'admins') {
            System::setErrorMsg('До сторінки має доступ тільки адміністратори');
            App::RedirectError();
            return  ;
        }

        $this->add(new Form('common'))->onSubmit($this, 'saveCommonOnClick');
        $this->common->add(new DropDownChoice('qtydigits'));
        $this->common->add(new DropDownChoice('amdigits'));
        $this->common->add(new DropDownChoice('dateformat'));


        $this->common->add(new DropDownChoice('phonel', array('10' => '10', '12' => '12'), '10'));


        $this->common->add(new TextInput('shopname'));

        $this->common->add(new CheckBox('sell2'));
        $this->common->add(new CheckBox('sellcheck'));
   
     
      
        $this->common->add(new TextInput('ts_break'));
        $this->common->add(new TextInput('ts_start'));
        $this->common->add(new TextInput('ts_end'));


        $common = System::getOptions("common",true);
        if (!is_array($common)) {
            $common = array();
        }

        $this->common->qtydigits->setValue($common['qtydigits']);
        $this->common->amdigits->setValue($common['amdigits']);
        $this->common->dateformat->setValue($common['dateformat']);

        $this->common->phonel->setValue($common['phonel']);

        $this->common->shopname->setText($common['shopname']);

        $this->common->sell2->setChecked($common['sell2']);
        $this->common->sellcheck->setChecked($common['sellcheck']);


       
        $this->common->ts_break->setText($common['ts_break'] == null ? '60' : $common['ts_break']);
        $this->common->ts_start->setText($common['ts_start'] == null ? '09:00' : $common['ts_start']);
        $this->common->ts_end->setText($common['ts_end'] == null ? '18:00' : $common['ts_end']);


        $this->add(new Form('business'))->onSubmit($this, 'saveBusinessOnClick');

        $pt = array(
            "0" => "По факту",
            "1" => "Передплата",
            "2" => "Післяплата"
        );
        $this->business->add(new DropDownChoice('paytypein', $pt, "1"));
        $this->business->add(new DropDownChoice('paytypeout', $pt, "1"));
        $st = array(
            "1" => "За серіями (партіями) виробника",
            "2" => "За серіями та датою придатності",
            "3" => "За серійними номерами  виробів"
        );
        
        $this->business->add(new DropDownChoice('usesnumber',$st));
        
        
        $this->business->add(new TextInput('price1'));
        $this->business->add(new TextInput('price2'));
        $this->business->add(new TextInput('price3'));
        $this->business->add(new TextInput('price4'));
        $this->business->add(new TextInput('price5'));
        $this->business->add(new TextInput('defprice'));
        $this->business->add(new CheckBox('allowminus'));
        $this->business->add(new CheckBox('allowminusmf'));
        $this->business->add(new CheckBox('noallowfiz'));
        $this->business->add(new CheckBox('useval'));
        $this->business->add(new CheckBox('printoutqrcode'));
        $this->business->add(new CheckBox('storeemp'));
        $this->business->add(new CheckBox('usescanner'));
        $this->business->add(new CheckBox('usescale'));

   
        $this->business->add(new DropDownChoice('deliverytype',[],1));
    


        $this->business->add(new TextArea('checkslogan'));
        $this->business->add(new \Zippy\Html\Form\Date('actualdate'));

 

        $this->business->paytypein->setValue($common['paytypein']);
        $this->business->paytypeout->setValue($common['paytypeout']);
        $this->business->price1->setText($common['price1']);
        $this->business->price2->setText($common['price2']);
        $this->business->price3->setText($common['price3']);
        $this->business->price4->setText($common['price4']);
        $this->business->price5->setText($common['price5']);
        $this->business->defprice->setText($common['defprice']);
        $this->business->storeemp->setChecked($common['storeemp']);
        $this->business->allowminus->setChecked($common['allowminus']);
        $this->business->allowminusmf->setChecked($common['allowminusmf']);
        $this->business->noallowfiz->setChecked($common['noallowfiz']);
        $this->business->useval->setChecked($common['useval']);
        $this->business->printoutqrcode->setChecked($common['printoutqrcode']);

        $this->business->usesnumber->setValue($common['usesnumber']??0);
        $this->business->usescanner->setChecked($common['usescanner']);
        $this->business->usescale->setChecked($common['usescale']);
  


        $this->business->deliverytype->setValue($common['deliverytype'] ?? 1);
       

        $this->business->checkslogan->setText($common['checkslogan']);
        $this->business->actualdate->setDate($common['actualdate'] ??  strtotime( date('Y'). '-01-01') );



        //валюты

        $this->add(new Form('valform'));
        $this->valform->add(new SubmitLink('loadrate', $this, 'onValCource'));
        $this->valform->add(new SubmitLink('valadd', $this, 'onValAdd'));
        $this->valform->add(new SubmitButton('saveval'))->onClick($this, 'saveValOnClick');

        $this->valform->add(new CheckBox('valprice'));

        $val = System::getOptions("val",true);
        if (!is_array($val)) {
            $val = array();
        }
        if(!is_array($val['vallist'])) {
            $val['vallist'] = array();
        }

        $this->_vallist = $val['vallist'] ;
        $this->valform->add(new \Zippy\Html\DataList\DataView('vallist', new \Zippy\Html\DataList\ArrayDataSource($this, '_vallist'), $this, "onValRow"));
        $this->valform->vallist->Reload();

        $this->valform->valprice->setChecked($val['valprice']);

        //печать
        $this->add(new Form('printer'));



        $this->printer->add(new TextInput('pmaxname'));
        $this->printer->add(new DropDownChoice('pricetype', \App\Entity\Item::getPriceTypeList()));
        $this->printer->add(new DropDownChoice('barcodetype', array('EAN13' => 'EAN13', 'C128' => 'Code128', 'C39' => 'Code39'), 'Code128'));


        $this->printer->add(new CheckBox('pprice'));
        $this->printer->add(new CheckBox('pcode'));
        $this->printer->add(new CheckBox('pbarcode'));
        $this->printer->add(new CheckBox('pqrcode'));
        $this->printer->add(new SubmitButton('savep'))->onClick($this, 'savePrinterOnClick');

        $printer = System::getOptions("printer",true);
        if (!is_array($printer)) {
            $printer = array();
        }


        $this->printer->pmaxname->setText($printer['pmaxname']);
        $this->printer->pricetype->setValue($printer['pricetype']);
        $this->printer->barcodetype->setValue($printer['barcodetype']);


        $this->printer->pprice->setChecked($printer['pprice']);
        $this->printer->pcode->setChecked($printer['pcode']);
        $this->printer->pbarcode->setChecked($printer['pbarcode']);
        $this->printer->pqrcode->setChecked($printer['pqrcode']);



        //API
        $this->add(new Form('api'))->onSubmit($this, 'saveApiOnClick');

  
        $this->api->add(new TextInput('aexp'));
        $this->api->add(new DropDownChoice('atype', array('1' => "Авторизація з JWT (Bearer)", '2' => "Basic авторизація", '3' => "Автоматична авторизація"), 1))->onChange($this, 'onApiType');
        $api = System::getOptions("api",true);
        if (!is_array($api)) {
            $api = array('exp' => 60, 'key' => 'qwerty', 'atype' => 1);
        }
 
        $this->api->aexp->setValue($api['exp']);
        $this->api->atype->setValue($api['atype']);

        $this->onApiType($this->api->atype);

        //SMS
        $this->add(new Form('sms'));

        $this->sms->add(new SubmitButton('smssubmit'))->onClick($this, 'saveSMSOnClick');
        $this->sms->add(new SubmitButton('smstest'))->onClick($this, 'testSMSOnClick');
 
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

        $this->sms->add(new TextArea('smscustscript'));
        $this->sms->add(new DropDownChoice('smscustlang', array('js' => "JavaScript",  'php' => "PHP"), 'js')) ;

        $this->sms->add(new DropDownChoice('smstype', array('1' => "SemySMS",  '2' => "SMSClub",   '3' => 'SMS-Fly', '4' => 'Кастомний скрипт'), 0))->onChange($this, 'onSMSType');
       
        $sms = System::getOptions("sms",true);

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

        $this->sms->smscustlang->setValue($sms['smscustlang']);
        $this->sms->smscustscript->setText( base64_decode($sms['smscustscript'] ));

        $this->sms->smstype->setValue($sms['smstype']);

        $this->onSMSType($this->sms->smstype);

 
        //телеграм бот

        $this->add(new Form('tbform'))->onSubmit($this, "onBot");
        $this->tbform->add(new TextInput('tbtoken', $common['tbtoken'] ?? ''));



        //источники  продаж

        $this->add(new Form('salesourcesform'));
        $this->salesourcesform->add(new SubmitButton('salesourcesave'))->onClick($this, 'OnSaveSaleSource');
        $this->salesourcesform->add(new SubmitLink('addnewsalesource'))->onClick($this, 'OnAddSaleSource');

        $this->salesourcesform->add(new DataView('salesourceslist', new ArrayDataSource(new Bind($this, '_salesourceslist')), $this, 'salesourceListOnRow'));

        $this->_salesourceslist = $common['salesources'] ??'';
        if (is_array($this->_salesourceslist) == false) {
            $this->_salesourceslist = array();
        }

        $this->salesourcesform->salesourceslist->Reload();

        
    }

    public function saveCommonOnClick($sender) {
        $common = System::getOptions("common",true);
        if (!is_array($common)) {
            $common = array();
        }

        $common['qtydigits'] = $this->common->qtydigits->getValue();
        $common['amdigits'] = $this->common->amdigits->getValue();
        $common['dateformat'] = $this->common->dateformat->getValue();

        $common['phonel'] = $this->common->phonel->getValue();

        $common['shopname'] = $this->common->shopname->getText();
        $common['ts_break'] = $this->common->ts_break->getText();
        $common['ts_start'] = $this->common->ts_start->getText();
        $common['ts_end'] = $this->common->ts_end->getText();
        $common['sell2'] = $this->common->sell2->isChecked() ? 1 : 0;
        $common['sellcheck'] = $this->common->sellcheck->isChecked() ? 1 : 0;

    
        System::setOptions("common", $common);


        $this->setSuccess('Збережено');

        App::Redirect("\\App\\Pages\\Options");

    }

    public function saveBusinessOnClick($sender) {
        $common = System::getOptions("common",true);
        if (!is_array($common)) {
            $common = array();
        }

        $common['paytypein'] = $this->business->paytypein->getValue();
        $common['paytypeout'] = $this->business->paytypeout->getValue();

        $common['price1'] = trim($this->business->price1->getText());
        $common['price2'] = trim($this->business->price2->getText());
        $common['price3'] = trim($this->business->price3->getText());
        $common['price4'] = trim($this->business->price4->getText());
        $common['price5'] = trim($this->business->price5->getText());
        $common['defprice'] = $this->business->defprice->getText();

        $common['noallowfiz'] = $this->business->noallowfiz->isChecked() ? 1 : 0;
        $common['storeemp'] = $this->business->storeemp->isChecked() ? 1 : 0;
        $common['allowminus'] = $this->business->allowminus->isChecked() ? 1 : 0;
        $common['allowminusmf'] = $this->business->allowminusmf->isChecked() ? 1 : 0;
        $common['useval'] = $this->business->useval->isChecked() ? 1 : 0;

        $common['checkslogan'] = trim($this->business->checkslogan->getText());
        $common['actualdate'] = $this->business->actualdate->getDate();
        $common['printoutqrcode'] = $this->business->printoutqrcode->isChecked() ? 1 : 0;
        $common['usescanner'] = $this->business->usescanner->isChecked() ? 1 : 0;
        $common['usescale'] = $this->business->usescale->isChecked() ? 1 : 0;
 
        $common['usesnumber'] = $this->business->usesnumber->GetValue() ;
        
        $common['deliverytype'] = $this->business->deliverytype->getValue() ;
 

        System::setOptions("common", $common);
        $this->_tvars["useval"] = $common['useval'] == 1;

        $this->setSuccess('Збережено');

        App::Redirect("\\App\\Pages\\Options");


    }

    public function onBot($sender) {


        $common = System::getOptions("common",true);
        if (!is_array($common)) {
            $common = array();
        }

        $common['tbtoken'] = $sender->tbtoken->getText()  ;
      //  $common['tbname'] = $sender->tbname->getText()  ;
        if( strlen($common['tbtoken'] )==0 ) {
            $this->setWarn("Не задано токен") ;
            System::setOptions("common", $common);            
            return;
        }
        
        $url= _BASEURL. 'chatbot.php' ;

        $bot = new \App\ChatBot($common['tbtoken']) ;
//        $res = $bot->doGet('getWebhookInfo') ;
        $res = $bot->doGet('deleteWebhook') ;
        $res = $bot->doGet('setWebhook', array('url'=>$url)) ;
        if($res['error_code'] == 404) {
            $this->setError("Невірний токен") ;
            return;
        }
        if($res['ok'] != true) {
            $this->setError($res['error_code']. ' ' .$res['description']) ;
            return;
        }

        H::log("set hook ".$url);

        System::setOptions("common", $common);
        $this->setSuccess('Збережено');

    }

    public function savePrinterOnClick($sender) {
        $printer = array();


        $printer['pmaxname'] = $this->printer->pmaxname->getText();
        $printer['pricetype'] = $this->printer->pricetype->getValue();
        $printer['barcodetype'] = $this->printer->barcodetype->getValue();


        $printer['pprice'] = $this->printer->pprice->isChecked() ? 1 : 0;
        $printer['pcode'] = $this->printer->pcode->isChecked() ? 1 : 0;
        $printer['pbarcode'] = $this->printer->pbarcode->isChecked() ? 1 : 0;
        $printer['pqrcode'] = $this->printer->pqrcode->isChecked() ? 1 : 0;
        System::setOptions("printer", $printer);
        $this->setSuccess('Збережено');
    }

    public function onApiType($sender) {
        $type = $this->api->atype->getValue();
        $this->api->aexp->setVisible($type == 1);
     

        //  $this->goAnkor('atype');
    }

    public function saveApiOnClick($sender) {
        $api = array();
        $api['exp'] = $this->api->aexp->getText();
 
        $api['atype'] = $this->api->atype->getValue();

        System::setOptions("api", $api);
        $this->setSuccess('Збережено');
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

     
        $this->sms->smscustlang->setVisible($type == 4);
        $this->sms->smscustscript->setVisible($type == 4);

        $this->sms->smstestphone->setVisible($type >0 );
        $this->sms->smstesttext->setVisible($type >0 );
        $this->sms->smstest->setVisible($type >0 );

      //  $this->goAnkor('smstype');
    }

    public function saveSMSOnClick($sender) {
        $sms = array();
        $sms['smstype'] = $this->sms->smstype->getValue();

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
        $sms['smscustlang'] = $this->sms->smscustlang->getValue();
        $sms['smscustscript'] = base64_encode($this->sms->smscustscript->getText() );

        System::setOptions("sms", $sms);
        $this->setSuccess('Збережено');
    }

    public function testSMSOnClick($sender) {
     
        $res = \App\Entity\Subscribe::sendSMS($this->sms->smstestphone->getText(), $this->sms->smstesttext->getText());
        if (strlen($res) == 0) {
           
            $res = \App\Entity\Subscribe::sendViber($this->sms->smstestphone->getText(), $this->sms->smstesttext->getText());

        } else {
            $this->setError($res);
        }

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

        $common = System::getOptions('common',true);
        $common['salesources']  = $this->_salesourceslist;
        System::setOptions("common", $common);


        $this->setSuccess('Збережено');
    }

    public function onValRow($row) {
        $val = $row->getDataitem();
        $row->add(new TextInput('valcode', new Bind($val, 'code')));
        $row->add(new TextInput('valname', new Bind($val, 'name')));
        $row->add(new TextInput('valrate', new Bind($val, 'rate')));
        $row->add(new ClickLink('valdel', $this, 'onValDel'));

    }

    public function onValCource($sender) {
        $xml=@simplexml_load_string(file_get_contents("https://bank.gov.ua/NBU_Exchange/exchange?date=".date("d.m.Y")  ) ) ;
        if($xml==false) return;
        $vl = $this->_vallist;
        $this->_vallist=[];
        foreach($xml->children() as $row){
            $code=(string)$row->CurrencyCodeL[0];
            $amount=doubleval($row->Amount[0]);
            $unit=doubleval($row->Units[0]);
            $rate=   @number_format($amount/$unit, 3, '.', '')  ;
            foreach($vl as $v){
               if($v->code == $code ) {
                  $v->rate  = $rate;
               }
               $this->_vallist[$v->id]=$v;
            }
        }
        
        $this->valform->vallist->Reload();
        $this->goAnkor('valform') ;
        
    }

    public function onValDel($sender) {
        $val = $sender->getOwner()->getDataItem() ;
        $this->_vallist = array_diff_key($this->_vallist, array($val->id => $this->_vallist[$val->id]));

        $this->valform->vallist->Reload();
        $this->goAnkor('valform') ;

    }

    public function onValAdd($sender) {
        $val=new  \App\DataItem() ;
        $val->code='';
        $val->name='';
        $val->rate='';
        $val->id=time();


        $this->_vallist[$val->id] = $val;
        $this->valform->vallist->Reload();
        $this->goAnkor('valform') ;
    }

    public function saveValOnClick($sender) {
        $val = array();

        $val['vallist'] = $this->_vallist;
        $val['valprice'] = $this->valform->valprice->isChecked() ? 1 : 0;

        System::setOptions("val", $val);
        $this->setSuccess('Збережено');
    }

 
}

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
        $this->business->add(new CheckBox('usescannermob'));
        $this->business->add(new CheckBox('usescale'));

   
        $this->business->add(new DropDownChoice('deliverytype',[],1));
        $this->business->add(new DropDownChoice('storepart',\App\Entity\Store::getList(),0));
    


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

        $this->business->storepart->setValue($common['storepart']??0);
        $this->business->usesnumber->setValue($common['usesnumber']??0);
        $this->business->usescanner->setChecked($common['usescanner']);
        $this->business->usescannermob->setChecked($common['usescannermob']);
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
        $common['usescannermob'] = $this->business->usescannermob->isChecked() ? 1 : 0;
        if($common['usescanner']==0)  {
           $common['usescannermob'] = 0; 
        }
        $common['usescale'] = $this->business->usescale->isChecked() ? 1 : 0;
 
        $common['storepart'] = $this->business->storepart->getValue() ;
        $common['usesnumber'] = $this->business->usesnumber->getValue() ;
        
        $common['deliverytype'] = $this->business->deliverytype->getValue() ;
 

        System::setOptions("common", $common);
        $this->_tvars["useval"] = $common['useval'] == 1;

        $this->setSuccess('Збережено');

        App::Redirect("\\App\\Pages\\Options");


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

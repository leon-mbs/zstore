<?php

namespace App\Modules\OCStore;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Html\DataList\DataView;
use Zippy\Binding\PropertyBinding as Prop;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Link\ClickLink;
use App\Application as App;

class Options extends \App\Pages\Base
{
    
    private $_m=null;
    public $_list = [];
    
    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'ocstore') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }
        
        $ss = \App\Helper::getSaleSources();
        
        $this->_list = Helper::getList();   //перенос  из  старых  настроек  внутри
         
        $this->add(new Panel("listpan"));
        $this->listpan->add(new ClickLink("addnew",$this,"onAdd"));
     
        $this->listpan->add(new DataView('list', new ArrayDataSource(new Prop($this, '_list')), $this, 'OnRow')) ;
        $this->listpan->list->Reload();
         
        $form = $this->add(new Form("cform"));
        $form->setVisible(false);
        $form->add(new TextInput('site'));
        $form->add(new TextInput('apiname'));
     
        $form->add(new CheckBox('insertcust' ));
        $form->add(new CheckBox('dostore' ));
        $form->add(new CheckBox('reserve' ));
        $form->add(new CheckBox('usettn' ));

        $form->add(new CheckBox('v4' ));
        $form->add(new TextArea('key', ));

        $form->add(new DropDownChoice('defpricetype',\App\Entity\Item::getPriceTypeList() ));
        $form->add(new DropDownChoice('salesource', $ss ));
        $form->add(new DropDownChoice('defmf',\App\Entity\MoneyFund::getList() ));
        $form->add(new DropDownChoice('defstore',\App\Entity\Store::getList() ));
      
        $pt=[];
        $pt[1] = 'Оплата на стороні IM ';
        $pt[2] = 'Постоплата';
        $pt[3] = 'Оплата касовим чеком або РФ';
      
        $form->add(new DropDownChoice('defpaytype',$pt ));

        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $form->add(new ClickLink('cancel'))->onClick($this, 'onCancel');
    }


   public function onAdd($sender) {
       $this->_m = new \App\DataItem() ;  
       $this->_m->id=1;
       if(count($this->_list)>0) {
           $this->_m->id = intval( max(  array_keys($this->_list) )) +1  ;
       }
       $this->cform->clean();
       $this->cform->setVisible(true) ; 
       $this->listpan->setVisible(false) ; 
   }   

   public function onCancel($sender) {
       $this->cform->setVisible(false) ; 
       $this->listpan->setVisible(true) ; 
   }   
   

   public function OnRow($row) {
        $oc = $row->getDataItem();

        $row->add(new Label('rsite', $oc->site));
        $row->add(new Label('rsalesourcname', $oc->salesourcename));
        $row->add(new ClickLink('redit', $this,'edit' ));
        $row->add(new ClickLink('rdel', $this,'del' ));
        $row->add(new ClickLink('rtest', $this,'test' ));
        
   }    
 
   public function edit($sender) {
        $this->_m = $sender->getOwner()->getDataItem();
        $this->cform->setVisible(true) ; 
        $this->listpan->setVisible(false) ; 

        $this->cform->site->setText($this->_m->site) ;
        $this->cform->apiname->setText($this->_m->apiname) ;
        $this->cform->key->setText($this->_m->key) ;
        $this->cform->salesource->setValue($this->_m->salesource) ;
        $this->cform->defpricetype->setValue($this->_m->pricetype) ;
        $this->cform->defmf->setValue($this->_m->mf) ;
        $this->cform->defstore->setValue($this->_m->storeid) ;
        $this->cform->defpaytype->setValue($this->_m->paytype) ;
        $this->cform->insertcust->setChecked($this->_m->insertcust) ;
        $this->cform->dostore->setChecked($this->_m->dostore) ;
        $this->cform->reserve->setChecked($this->_m->reserve) ;
        $this->cform->v4->setChecked($this->_m->ocv4) ;
        $this->cform->usettn->setChecked($this->_m->usettn) ;
        
    } 
  
    public function del($sender) {
        $m = $sender->getOwner()->getDataItem();
        
        unset($this->_list[$m->id]) ;
        
        $modules = System::getOptions("modules");

        $modules['oclist'] =  $this->_list;

        System::setOptions("modules", $modules);

        $this->listpan->list->Reload();
      
    } 
        
    public function saveOnClick($sender) {
       
        $this->_m->apiname = $this->cform->apiname->getText();
        $this->_m->key = $this->cform->key->getText();
        $this->_m->site = $this->cform->site->getText();

        $this->_m->pricetype = $this->cform->defpricetype->getValue();
        $this->_m->salesource = $this->cform->salesource->getValue();
        $this->_m->salesourcename = $this->cform->salesource->getValueName();
        $this->_m->mf = $this->cform->defmf->getValue();
        $this->_m->storeid = $this->cform->defstore->getValue();
        $this->_m->paytype = intval($this->cform->defpaytype->getValue() );
        $this->_m->paytypename = $this->cform->defpaytype->getValueName() ;

        $this->_m->ocv4 = $this->cform->v4->isChecked() ? 1 : 0;   //читается  как  ocv4  в  helper, orders, items
        $this->_m->insertcust = $this->cform->insertcust->isChecked() ? 1 : 0;
        $this->_m->reserve = $this->cform->reserve->isChecked() ? 1 : 0;
        $this->_m->dostore = $this->cform->dostore->isChecked() ? 1 : 0;
        $this->_m->usettn = $this->cform->usettn->isChecked() ? 1 : 0;
         
        if (strlen($this->_m->pricetype) < 2) {

            $this->setError('Не вказано тип ціни');
            return;
        }

        if ( $this->_m->paytype==0) {

            $this->setError('Не вказано тип оплати');
            return;
        }
        if ( $this->_m->paytype < 2 && $this->_m->mf==0) {

            $this->setError('Не вказано касу');
            return;
        }
        if ( $this->_m->reserve==1 && $this->_m->storeid==0) {

            $this->setError('Не вказано склад');
            return;
        }
        if ( $this->_m->dostore==1 && $this->_m->storeid==0) {

            $this->setError('Не вказано склад');
            return;
        }
       
         
        $this->_m->site = trim($this->_m->site, '/');
        $this->_list[$this->_m->id]=$this->_m ;

        $modules = System::getOptions("modules");
         
        $modules['oclist'] =  $this->_list;

        System::setOptions("modules", $modules);

        $this->listpan->list->Reload();
      
        $this->cform->setVisible(false) ; 
        $this->listpan->setVisible(true) ; 
     
    }
 
    public function test($sender) {
        $m = $sender->getOwner()->getDataItem();

      $c=  \App\Modules\OCStore\Helper::connect($m);
      if( $c)  {
          $this->setSuccess('Ok') ;
      }
 
   }    
   
   
}

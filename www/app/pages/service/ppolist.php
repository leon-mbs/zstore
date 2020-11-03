<?php

namespace App\Pages\Service;

use App\Entity\Item;
use App\Helper as H;
use App\DataItem;
use App\System;
use Zippy\Binding\PropertyBinding as Prop;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Panel;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\WebApplication as App;

class PPOList extends \App\Pages\Base
{

    public $_ppolist = array();
    public $_shlist = array();
    public $_doclist = array();

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'ppo') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(H::l('noaccesstopage'));

            App::RedirectHome();
            return;
        }
        $modules = System::getOptions("modules");


        $this->add(new Panel('opan'));
        
        $this->opan->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->opan->filter->add(new DropDownChoice('searchcomp', \App\Entity\Firm::findArray('firm_name', 'disabled<>1', 'firm_name'), 0));

 
        $this->opan->add(new DataView('ppolist', new ArrayDataSource(new Prop($this, '_ppolist')), $this, 'ppoOnRow'));
 
        $this->add(new Panel('shpan'))->setVisible(false); 
        $this->shpan->add(new ClickLink('backo',$this,'onBacko')) ;
        $this->shpan->add(new DataView('shlist', new ArrayDataSource(new Prop($this, '_shlist')), $this, 'shOnRow'));
     
        $this->add(new Panel('docpan'))->setVisible(false); 
        $this->docpan->add(new ClickLink('backsh',$this,'onBacksh')) ;
        $this->docpan->add(new Label('docshow'))->setVisible(false);  ;
        $this->docpan->add(new DataView('doclist', new ArrayDataSource(new Prop($this, '_doclist')), $this, 'docOnRow'));
       
    }

    public function filterOnSubmit($sender) {
        $this->_ppolist = array();
        $modules = System::getOptions("modules");
        $cid = $this->opan->filter->searchcomp->getValue();
        if($cid == 0) return;
        $res = H::send(json_encode(array('Command'=>'Objects')),'cmd',$cid)  ;
        $res = json_decode($res ) ;    
        if(is_array($res->TaxObjects)){
            $this->_ppolist = array();
            foreach($res->TaxObjects as $item){
               foreach($item->TransactionsRegistrars as $tr){
                   $it = new   DataItem(array('org'=>$item)) ;
                   $it->tr = $tr;
                   $this->_ppolist[] =  $it    ;
               }
            }
            
        
            $this->opan->ppolist->Reload();    
        }   
        
        
  
    }

    public function ppoOnRow($row) {
        

        $item =  $row->getDataItem()  ;
     
        $row->add(new Label('name', $item->org->Name));
   
        $row->add(new Label('org', $item->org->OrgName));
        $row->add(new Label('tin', $item->org->Tin));
        $row->add(new Label('ipn', $item->org->Ipn));
        $row->add(new Label('fn', $item->tr->NumFiscal));
        $row->add(new Label('ln', $item->tr->NumLocal));
        $row->add(new Label('rn', $item->tr->Name));
        $row->add(new ClickLink('objdet', $this,'onObj'));
       
    }

  
    public function onObj($sender) {
        $ppo = $sender->getOwner()->getDataItem();
        $this->_shlist = array();
        $from = \Carbon\Carbon::now()->addMonth(-1)->startOfMonth()->format('c') ;
        $to = \Carbon\Carbon::now()->format('c') ;
        $cid = $this->opan->filter->searchcomp->getValue();
       
        $res = H::send(json_encode(array('Command'=>'Shifts','NumFiscal'=>$ppo->tr->NumFiscal,'From'=>$from,'To'=>$to)),'cmd',$cid)  ;
        $res = json_decode($res ) ;    
        foreach($res->Shifts as $sh) {
             $it = new   DataItem(array('openname'=>$sh->OpenName,
                                        'closename'=>$sh->CloseName,
                                        'opened'=>$sh->Opened,
                                        'closed'=>$sh->Closed,
                                        'ShiftId'=>$sh->ShiftId ,
                                        'NumFiscal'=>$ppo->tr->NumFiscal
                                        )) ;
                                        
             
             $this->_shlist[]=$it;
        }
        
        $this->shpan->shlist->Reload(); 
         
        $this->opan->setVisible(false);        
        $this->shpan->setVisible(true);        
    }
    public function onBacko($sender) {
        $this->opan->setVisible(true);        
        $this->shpan->setVisible(false);        
    }

   public function shOnRow($row) {
        

        $item =  $row->getDataItem()  ;
     
        $row->add(new Label('openname', $item->openname));
        $row->add(new Label('closename', $item->closename));
        $row->add(new Label('opened',  date('Y-m-d H:i',strtotime($item->opened))));
        $row->add(new Label('closed',  date('Y-m-d H:i',strtotime($item->closed))));
      
        $row->add(new ClickLink('shdet', $this,'onSh'));
   }    


    public function onSh($sender) {
        $sh = $sender->getOwner()->getDataItem();
        $this->_doclist = array();
        $cid = $this->opan->filter->searchcomp->getValue();
       
        $res = H::send(json_encode(array('Command'=>'Documents','NumFiscal'=>$sh->NumFiscal,'ShiftId'=>$sh->ShiftId )),'cmd',$cid)  ;
        $res = json_decode($res ) ;    
        foreach($res->Documents as $doc) {
             $it = new   DataItem(array('NumFiscal'=>$doc->NumFiscal,
                                        'NumLocal'=>$doc->NumLocal,
                                        'RegNumFiscal'=>$sh->NumFiscal,
                                        'DocClass'=>$doc->DocClass,
                                        'CheckDocType'=>$doc->CheckDocType
                                        )) ;
                                        
             
             $this->_doclist[]=$it;
        }
        
        $this->docpan->doclist->Reload(); 
         
        $this->shpan->setVisible(false);        
        $this->docpan->setVisible(true);        
    }
    public function onBacksh($sender) {
        $this->shpan->setVisible(true);        
        $this->docpan->setVisible(false);        
    }

    
    
    public function docOnRow($row) {
        

        $item =  $row->getDataItem()  ;
     
        $row->add(new Label('NumFiscal', $item->NumFiscal));
        $row->add(new Label('NumLocal', $item->NumLocal));
        $row->add(new Label('DocClass', $item->DocClass));
        $row->add(new Label('CheckDocType', $item->CheckDocType));
    
        $row->add(new ClickLink('docdet', $this,'onDoc'));
   }      
   
    public function onDoc($sender) {
        $doc = $sender->getOwner()->getDataItem();
        $cid = $this->opan->filter->searchcomp->getValue();
       
        $res = H::send(json_encode(array('Command'=>$doc->DocClass,'RegistrarNumFiscal'=>$doc->RegNumFiscal,'NumFiscal'=>$doc->NumFiscal )),'cmd',$cid,true)  ;
        $res = mb_convert_encoding($res , "utf-8" ,"windows-1251" )  ;           
        $this->docpan->docshow->setText($res);
        $this->docpan->docshow->setVisible(true);        
        $this->goAnkor('docshow')      ;
    }  
}

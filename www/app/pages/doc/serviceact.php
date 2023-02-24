<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\System;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\MoneyFund;
use App\Entity\Service;
use App\Entity\Item;
use App\Entity\Store;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода  акта выполненных работ
 */
class ServiceAct extends \App\Pages\Base
{

    public  $_servicelist = array();
    public  $_itemlist = array();
    private $_doc;
    private $_basedocid   = 0;

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();
   
        $common = System::getOptions("common");
   
        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->_servicelist = $this->_doc->unpackDetails('detaildata');
            $this->_itemlist = $this->_doc->unpackDetails('detail2data');
        }
        else {
            $this->_doc = Document::create('ServiceAct');
            $this->_doc->document_number = $this->_doc->nextNumber();
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid)->cast();
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'ServiceAct') {
                        $this->_servicelist = $basedoc->unpackDetails('detaildata');
                        $this->_itemlist = $basedoc->unpackDetails('detail2data');
                    }
                }
            }          
        }   
   
   
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }   
   
   
        /*
        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnCustomerFirm');

        $this->docform->add(new DropDownChoice('firm', \App\Entity\Firm::getList(), H::getDefFirm()))->onChange($this, 'OnCustomerFirm');
        $this->docform->add(new DropDownChoice('contract', array(), 0))->setVisible(false);;
        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));

        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('gar'));
        $this->docform->add(new TextInput('device'));
        $this->docform->add(new TextInput('devsn'));

        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), 0));

        $this->docform->add(new TextInput('editpayamount'));
        $this->docform->add(new SubmitButton('bpayamount'))->onClick($this, 'onPayAmount');


        $this->docform->add(new TextInput('payed', 0));
        $this->docform->add(new Label('payamount', 0));

        $this->docform->add(new Label('discount'));
        $this->docform->add(new TextInput('editpaydisc'));
        $this->docform->add(new SubmitButton('bpaydisc'))->onClick($this, 'onPayDisc');
        $this->docform->add(new Label('paydisc', 0));

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitLink('additemrow'))->onClick($this, 'addItemrowOnClick');
        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('paydoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Label('total'));
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new DropDownChoice('editservice', Service::findArray("service_name", "disabled<>1", "service_name")))->onChange($this, 'OnChangeServive', true);

        $this->editdetail->add(new TextInput('editqty'));
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextArea('editdesc'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');

        
        $this->add(new Form('edititemdetail'))->setVisible(false);
        $this->edititemdetail->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutoItem');
        $this->edititemdetail->edititem->onChange($this, 'OnChangeItem', true);

        $this->edititemdetail->add(new TextInput('edititemqty'));
        $this->edititemdetail->add(new TextInput('edititemprice'));
        $this->edititemdetail->add(new Label('qtystock'));

        $this->edititemdetail->add(new Button('cancelrowitem'))->onClick($this, 'cancelrowOnClick');
        $this->edititemdetail->add(new SubmitButton('saverowitem'))->onClick($this, 'saveitemrowOnClick');
           
        
        //добавление нового кантрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new TextInput('editemail'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        if ($docid > 0) { //загружаем   содержимое   документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->gar->setText($this->_doc->headerdata['gar']);
            $this->docform->store->setValue($this->_doc->headerdata['store']);
  
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);
            $this->docform->payamount->setText($this->_doc->payamount);
            $this->docform->editpayamount->setText($this->_doc->payamount);
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);

            if ($this->_doc->payed == 0 && $this->_doc->headerdata['payed'] > 0) {
                $this->_doc->payed = $this->_doc->headerdata['payed'];
            }

            $this->docform->payed->setText(H::fa($this->_doc->payed));

            $this->docform->device->setText($this->_doc->device);
            $this->docform->devsn->setText($this->_doc->devsn);
            $this->docform->paydisc->setText($this->_doc->headerdata['paydisc']);
            $this->docform->editpaydisc->setText($this->_doc->headerdata['paydisc']);

            $this->docform->total->setText($this->_doc->amount);

            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);
            $this->docform->firm->setValue($this->_doc->firm_id);
            $this->OnCustomerFirm(null);
            $this->docform->contract->setValue($this->_doc->headerdata['contract_id']);

            $this->_servicelist = $this->_doc->unpackDetails('detaildata');
            $this->_itemlist = $this->_doc->unpackDetails('detail2data');
        } else {
            $this->_doc = Document::create('ServiceAct');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);

                if ($basedoc->meta_name == 'Task') {
                    $this->docform->customer->setKey($basedoc->customer_id);
                    $this->docform->customer->setText($basedoc->customer_name);
                    $this->_servicelist = array();
                    foreach($basedoc->unpackDetails('detaildata') as $v ) {
                       $this->_servicelist[$v->service_id]= $v ;    
                    }
                    
                }
                if ($basedoc->meta_name == 'Invoice') {
                    $this->docform->customer->setKey($basedoc->customer_id);
                    $this->docform->customer->setText($basedoc->customer_name);

                    $this->_servicelist = array();
                    foreach($basedoc->unpackDetails('detaildata') as $v ) {
                       if($v->service_id>0) {
                           $this->_servicelist[$v->service_id]= $v ;                               
                       }

                    }
                    foreach($basedoc->unpackDetails('detaildata') as $v ) {
                       if($v->item_id>0) {
                           $this->_itemlist[$v->item_id]= $v ;                               
                       }

                    }
                }
                if ($basedoc->meta_name == 'ServiceAct') {
                    $this->docform->customer->setKey($basedoc->customer_id);
                    $this->docform->customer->setText($basedoc->customer_name);

                    $this->_servicelist = array();
                    foreach($basedoc->unpackDetails('detaildata') as $v ) {
                       $this->_servicelist[$v->service_id]= $v ;    
                    }
                    $this->_itemlist = array();
                    foreach($basedoc->unpackDetails('detail2data') as $v ) {
                       $this->_itemlist[$v->item_id]= $v ;    
                    }
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_servicelist')), $this, 'detailOnRow'))->Reload();
        $this->docform->add(new DataView('detail2', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detail2OnRow'))->Reload();
        $this->calcTotal();
       
        */
       

    }
     
    public  function loaddata($args,$post){
            $ret =[];
            $ret['doc'] = [];
            $ret['doc']['document_date']   =  date('Y-m-d', $this->_doc->document_date) ;
            $ret['doc']['document_number']   =   $this->_doc->document_number ;
            $ret['doc']['notes']   =   $this->_doc->notes ;
            $ret['doc']['firm_id']   =   $this->_doc->firm_id ?? 0;
            $ret['doc']['customer_id']   =   $this->_doc->customer_id ?? 0;
            $ret['doc']['customer_name']   =   $this->_doc->customer_name ;
            $ret['doc']['store']   =   $this->_doc->headerdata['store'] ?? 0;
            $ret['doc']['contract_id']   =   $this->_doc->headerdata['contract_id'] ?? 0;
            $ret['doc']['device']   =   $this->_doc->headerdata['device'] ?? '';
            $ret['doc']['devsn']   =   $this->_doc->headerdata['devsn'] ?? '';
            $ret['doc']['gar']   =   $this->_doc->headerdata['gar'] ?? '';
            $ret['doc']['amount']   = H::fa( $this->_doc->amount);
            $ret['doc']['payamount']   = H::fa( $this->_doc->payamount);
            $ret['doc']['paydisc']   = H::fa( $this->_doc->headerdata['paydisc']);
            $ret['doc']['payment']   = H::fa( $this->_doc->headerdata['payment']);
            $ret['doc']['payed']   = H::fa( $this->_doc->headerdata['payed']);

            $ret['doc']['services'] = [];
            foreach($this->_servicelist as $ser) {
                $ret['doc']['services'][]  = array(
                   'service_id'=>$ser->service_id,
                   'service_name'=>$ser->service_name ,
                   'desc'=>$ser->desc ,
                   'price'=>H::fa($ser->price) ,
                   'quantity'=>H::fqty($ser->quantity) ,
                   'amount'=>H::fa($ser->quantity * $ser->price) 
                   
                );
            }

            $ret['doc']['items'] = [];
            foreach($this->_itemlist as $item) {
                $ret['doc']['items'][]  = array(
                   'item_id'=>$item->item_id,
                   'itemname'=>$item->itemname ,
                   'item_code'=>$item->item_code ,
                   'price'=>H::fa($item->price) ,
                   'quantity'=>H::fqty($item->quantity) ,
                   'amount'=>H::fa($item->quantity * $item->price) 
                   
                );
            }
            //для  комбобокса
            $ret['servicelist'] = \App\Util::tokv(\App\Entity\Service::getList()) ; 
            
            return json_encode($ret, JSON_UNESCAPED_UNICODE);   
    }   


    public  function save($args,$post){
         $post = json_decode($post) ;
         if (false == \App\ACL::checkEditDoc($this->_doc,false,false)) {

             return json_encode(['error'=>'Нема прав редагування документу' ], JSON_UNESCAPED_UNICODE);              
         }

         $this->_doc->document_number = $post->doc->document_number;
         $this->_doc->document_date = strtotime( $post->doc->document_date);
         $this->_doc->notes = $post->doc->notes;
         $this->_doc->firm_id = $post->doc->firm_id;
         $this->_doc->customer_id = $post->doc->customer_id;
            
         $this->_doc->amount = $post->doc->total;
         $this->_doc->payamount = $post->doc->payamount;
         $this->_doc->payed = $post->doc->payed;
         $this->_doc->headerdata['payed'] = $post->doc->payed;
         $this->_doc->headerdata['store'] = $post->doc->store;
         $this->_doc->headerdata['devsn'] = $post->doc->devsn;
         $this->_doc->headerdata['device'] = $post->doc->device;
         $this->_doc->headerdata['gar'] = $post->doc->gar;
         $this->_doc->headerdata['contract_id'] = $post->doc->contract_id;
         $this->_doc->headerdata['payment'] = $post->doc->payment;
         $this->_doc->headerdata['paydisc'] = $post->doc->paydisc;
         $this->_doc->headerdata['bonus'] = $post->doc->bonus;
    
         if (false == $this->_doc->checkUniqueNumber()) {
             return json_encode(['error'=>'Не унікальний номер документу. Створено новий.','newnumber'=>$this->_doc->nextNumber()], JSON_UNESCAPED_UNICODE);              
         }     

         
         $i=0;
     
         $this->_itemlist=[];
         foreach($post->doc->items as $it) {
             $item = Item::load($it->item_id);
     
             $item->quantity = $it->quantity;
             $item->price = $it->price;
                          
             $this->_itemlist[++$i]=$item;
         }
         $this->_doc->packDetails('detail2data', $this->_itemlist);
        
         $i=0;
         $this->_servicelist=[];
         foreach($post->doc->services as $s) {
             $ser = Service::load($s->service_id);
     
             $ser->quantity = $s->quantity;
             $ser->price = $s->price;
                          
             $this->_servicelist[++$i]=$ser;
         }
         
         $this->_doc->packDetails('detaildata', $this->_servicelist);
         
         
         
         $conn = \ZDB\DB::getConnect();
         $conn->BeginTrans();
         try {
            if ($this->_basedocid > 0) {
                $this->_doc->parent_id = $this->_basedocid;
                $this->_basedocid = 0;
            }
       
   
  
            $this->_doc->save();

            if ($post->op != 'savedoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }


                if ($post->op == 'execdoc' || $post->op == 'paydoc') {
                    $this->_doc->updateStatus(Document::STATE_INPROCESS);
                     
                }
                if($this->_doc->payamount > $this->_doc->payed &&   $post->op == 'paydoc' ) {
                      $this->_doc->updateStatus(Document::STATE_WP);                    
                }                
                
                
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }

            $conn->CommitTrans();


         } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);

            return json_encode(['error'=>$ee->getMessage()], JSON_UNESCAPED_UNICODE);              

            
         }        
    
         return json_encode([], JSON_UNESCAPED_UNICODE);          
           
     }   
}

<?php

namespace App\Pages\Register;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use Zippy\Html\Panel;
use App\Entity\Pay;

/**
 * журнал  заказов
 */
class OrderList extends \App\Pages\Base
{
    private $_doc = null;
    private $_issms = false; //подключен  смс  сервис
    public $_itemlist =[];

    /**
     *

     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('OrderList')) {
            \App\Application::RedirectHome() ;
        }
        $this->_issms = (System::getOption('sms', 'smstype')??0) >0 ;

        $this->add(new Panel("listpanel"));

        $this->listpanel->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');

        $this->listpanel->filter->add(new TextInput('searchnumber'));
        $this->listpanel->filter->add(new TextInput('searchtext'));
        $this->listpanel->filter->add(new DropDownChoice('status', array(0 => 'Вiдкритi', 1 => 'Новi',2 => 'До сплати', 3 => 'Всi'), 0));
        $this->listpanel->filter->add(new DropDownChoice('salesource', H::getSaleSources(), 0));

        $doclist = $this->listpanel->add(new DataView('doclist', new OrderDataSource($this), $this, 'doclistOnRow'));

        $this->listpanel->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(H::getPG());
        $this->listpanel->add(new ClickLink('csv', $this, 'oncsv'));


        $this->add(new Panel("statuspan"))->setVisible(false);

        $this->statuspan->add(new Form('statusform'));

        $this->statuspan->statusform->add(new SubmitButton('bclose'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('binp'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('brd'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('brec'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bsent'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bscan'))->onClick($this, 'statusOnSubmit');


        $this->statuspan->statusform->add(new SubmitButton('bpos'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bgi'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bginv'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bco'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bref'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bcopy'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bttn'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('btask'))->onClick($this, 'statusOnSubmit');


        $this->statuspan->statusform->add(new \Zippy\Html\Link\RedirectLink('btopay'));

        $this->statuspan->add(new \App\Widgets\DocView('docview'));

        $this->statuspan->add(new Form('moveform'));
        $this->statuspan->moveform->add(new DropDownChoice('brmove', \App\Entity\Branch::getList(), \App\ACL::getCurrentBranch()))->onChange($this, "onBranch", true);
        $this->statuspan->moveform->add(new DropDownChoice('usmove', array(), 0));
        $this->statuspan->moveform->add(new SubmitButton('bmove'))->onClick($this, 'MoveOnSubmit');

        $this->statuspan->add(new Form('resform'))->setVisible(false);

        $this->statuspan->resform->add(new SubmitButton('bres'))->onClick($this, 'resOnSubmit');
        $this->statuspan->resform->add(new SubmitButton('bunres'))->onClick($this, 'resOnSubmit');
        $this->statuspan->resform->add(new DropDownChoice('store', \App\Entity\Store::getList(), H::getDefStore()));


        $this->listpanel->doclist->Reload();

  
        $this->add(new Panel("editpanel"))->setVisible(false);
        $this->editpanel->add(new Label("editdn"));
        $this->editpanel->add(new Label("editchat"));
        $this->editpanel->add(new Form("editform"));
        $this->editpanel->editform->add(new SubmitButton('editcancel'))->onClick($this, 'editOnSubmit');
        $this->editpanel->editform->add(new SubmitButton('editsave'))->onClick($this, 'editOnSubmit');
        $this->editpanel->editform->add(new SubmitButton('editready'))->onClick($this, 'editOnSubmit');
        $this->editpanel->editform->add(new DataView('edititemlist', new \Zippy\Html\DataList\ArrayDataSource($this, '_itemlist'), $this, 'editlistOnRow'));
        $this->editpanel->editform->add(new TextInput('editbarcode'));
        $this->editpanel->editform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');


    }

    public function filterOnSubmit($sender) {

        $this->statuspan->setVisible(false);
      
        $this->listpanel->doclist->Reload();

    }

    public function doclistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $doc = $row->getDataItem();

        $n = $doc->document_number;
        if(strlen($doc->headerdata['ocorder'] ?? '')>0) {
            $n = $n . " (OC '{$doc->headerdata['ocorder']}')"  ;
        }
        if(strlen($doc->headerdata['wcorder'] ?? '')>0) {
            $n = $n . " (WC '{$doc->headerdata['wcorder']}')"  ;
        }
        if(strlen($doc->headerdata['puorder'] ?? '')>0) {
            $n = $n . " (PU '{$doc->headerdata['puorder']}')"  ;
        }


        $row->add(new ClickLink('number', $this, 'showOnClick'))->setValue($n);


        $row->add(new Label('date', H::fd($doc->document_date)));
        $row->add(new Label('onotes', $doc->notes));
        $row->add(new Label('emp', $doc->username));


        $row->add(new  \Zippy\Html\Link\BookmarkableLink('customer'))->setValue($doc->customer_name);
        $row->customer->setAttribute('onclick', "customerInfo({$doc->customer_id});") ;
        $row->add(new Label('amount', H::fa(($doc->payamount > 0) ? $doc->payamount : ($doc->amount > 0 ? $doc->amount : ""))));


        $row->add(new Label('ispay'))->setVisible($doc->getHD('paytype') != 3);
        
        if($doc->getHD('waitpay')==1){
            $row->ispay->setAttribute('class','fa fa-credit-card text-warning');
            $row->ispay->setAttribute('title','До сплати');            
        }   else {
            $row->ispay->setAttribute('class','fa fa-credit-card text-success');            
            $row->ispay->setAttribute('title','Оплачено');            
        }
        $row->add(new Label('isreserved'))->setVisible($doc->hasStore());

        $stname = Document::getStateName($doc->state);

        $row->add(new Label('state', $stname));
        $row->state->setText('<span class="badge badge-secondary">' . $stname . '</span>', true);
        if ($doc->state == Document::STATE_NEW) {
            $row->state->setText('<span class="badge badge-info">' . $stname . '</span>', true);
        }
        if ($doc->state == Document::STATE_READYTOSHIP || $doc->state == Document::STATE_INSHIPMENT || $doc->state == Document::STATE_DELIVERED) {
            $row->state->setText('<span class="badge badge-success">' . $stname . '</span>', true);
        }
        if ($doc->state == Document::STATE_INPROCESS) {
            $row->state->setText('<span class="badge badge-primary">' . $stname . '</span>', true);
        }

        if ($doc->state == Document::STATE_CLOSED || $doc->state == Document::STATE_EXECUTED) {
            $row->state->setText('<span class="badge badge-secondary">' . $stname . '</span>', true);
        }
        if ($doc->state == Document::STATE_FAIL) {
            $row->state->setText('<span class="badge badge-danger">' . $stname . '</span>', true);
        }

        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        if ($doc->state < Document::STATE_EXECUTED || $doc->state == Document::STATE_INPROCESS) {
            $row->edit->setVisible(true);
        } else {
            $row->edit->setVisible(false);
        }
        $row->setAttribute('data-did', $doc->document_id);
     

        $ch = $this->checkChat($doc);
        if($ch) {
            $row->add(new Label('cchat'))->setVisible(true);
            $row->cchat->setAttribute('onclick', "opencchat({$doc->document_id})");
            $row->cchat->setAttribute('class', "fa fa-comments");

            $m = \App\Entity\Message::getFirst("item_id={$doc->document_id} and item_type=" .\App\Entity\Message::TYPE_CUSTCHAT, "message_id desc");
            if($m != null) {
                if($m->user_id >0) { //отправлен  вопрос
                    $row->cchat->setAttribute('class', "fa fa-comments text-warn");
                }
                if($m->user_id ==0) { //получен  ответ
                    $row->cchat->setAttribute('class', "fa fa-comments text-success");
                }
            }
        }
    }

    private function checkChat($doc) {
        $ret = false ;
        if($this->_issms && ($doc->state < Document::STATE_EXECUTED || $doc->state == Document::STATE_INPROCESS)) {

            $phone= $doc->headerdata['phone'] ??'';
            if($phone=='') {
                $c =  \App\Entity\Customer::load($doc->customer_id) ;
                $phone = $c->phone;
            }

            if(strlen($phone)>0) {
                $ret = true;
            }
        }
        return $ret;
    }

    public function resOnSubmit($sender) {
        if ($sender->id == "bres") {
            $store = $this->statuspan->resform->store->getValue();
            if($store == 0) {
                return;
            }

            $conn = \ZDB\DB::getConnect();
            $conn->BeginTrans();

            try {
                $this->_doc->headerdata['store'] = $store;
                $this->_doc->headerdata['storename'] = $this->statuspan->resform->store->getValueName();
                $this->_doc->save() ;
                $this->_doc->reserve();

                $conn->CommitTrans();

            } catch(\Exception $e) {
                $this->setError($e->getMessage()) ;
                $conn->RollbackTrans();
                return;
            }

            $this->statuspan->resform->bres->setVisible(false);
            $this->statuspan->resform->store->setVisible(false);
            $this->statuspan->resform->bunres->setVisible(true);

        }
        if ($sender->id == "bunres") {

            $this->_doc->unreserve();
            $this->statuspan->resform->bunres->setVisible(false);

        }
        $this->listpanel->doclist->Reload(false);
        $this->statuspan->setVisible(false);    
        $this->_doc = null;            
        $this->listpanel->doclist->Reload(false);
    }


    public function statusOnSubmit($sender) {
        if (\App\ACL::checkChangeStateDoc($this->_doc, true, true) == false) {
            return;
        }

        $state = $this->_doc->state;

      //проверяем  что есть ТТН
        $list = $this->_doc->getChildren('TTN');
        $ttn = count($list) > 0;
        $list = $this->_doc->getChildren('GoodsIssue');
        $gi = count($list) > 0;
        //  $list = $this->_doc->getChildren('Invoice');
        //   $invoice = count($list) > 0;
        $list = $this->_doc->getChildren('POSCheck');
        $pos = count($list) > 0;

    
    
        if ($sender->id == "btask") {
            $task = count($this->_doc->getChildren('Task')) > 0;

            if ($task) {

                $this->setWarn('Вже існує документ Наряд');
            }
            App::Redirect("\\App\\Pages\\Doc\\Task", 0, $this->_doc->document_id);
            return;
        }
        if ($sender->id == "bttn") {
            if ($ttn) {
                $this->setWarn('У замовлення вже є відправки');
            }
            App::Redirect("\\App\\Pages\\Doc\\TTN", 0, $this->_doc->document_id);
            return;
        }
        if ($sender->id == "bcopy") {

            App::Redirect("\\App\\Pages\\Doc\\Order", 0, $this->_doc->document_id);
            return;
        }
        if ($sender->id == "bpos") {
            if ($pos) {
                $this->setWarn('Вже існує документ Чек');
            }
            App::Redirect("\\App\\Pages\\Service\\ARMPos", 0, $this->_doc->document_id);
            return;
        }

        if ($sender->id == "bgi") {

            App::Redirect("\\App\\Pages\\Doc\\GoodsIssue", 0, $this->_doc->document_id);
            return;
        }
        if ($sender->id == "bginv") {

            App::Redirect("\\App\\Pages\\Doc\\Invoice", 0, $this->_doc->document_id);
            return;
        }
        if ($sender->id == "bco") {

            App::Redirect("\\App\\Pages\\Doc\\OrderCust", 0, $this->_doc->document_id);
            return;
        }

        if ($sender->id == "bscan") {
            $this->openedit();
            return;
        }
      
        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();

        try {

 
            if ($sender->id == "binp") {
                $this->_doc->updateStatus(Document::STATE_INPROCESS);
            }
            if ($sender->id == "brd") {
                $this->_doc->updateStatus(Document::STATE_READYTOSHIP);
            }
            if ($sender->id == "brec") {
                $this->_doc->updateStatus(Document::STATE_DELIVERED);
            }
            if ($sender->id == "bsent") {
                $this->_doc->updateStatus(Document::STATE_INSHIPMENT);
            }

            if ($sender->id == "bref") {
                $this->_doc->setHD('waitpay',0) ;
                $this->_doc->updateStatus(Document::STATE_FAIL);
                $this->_doc->setHD('waitpay',0); 
                $this->_doc->save();     

                $this->setWarn('Замовлення анульовано');
            }

            if ($sender->id == "bclose") {

  

                if($this->_doc->payamount >0 && $this->_doc->payamount>$this->_doc->payed && $gi == false) {
                    $this->setWarn('"Замовлення закрито без оплати"');
                }

                if($ttn== false && $gi == false) {
                    $this->setWarn('Замовлення закрито без доставки');
                }

                $this->_doc->updateStatus(Document::STATE_CLOSED);
                $this->_doc->setHD('waitpay',0); 
                $this->_doc->save();     

            }
            $conn->CommitTrans();

        } catch(\Exception $e) {
            $this->setError($e->getMessage()) ;
            $conn->RollbackTrans();
            return;
        }
        
        
        
        $this->statuspan->setVisible(false);    
        $this->_doc = null;            
        $this->listpanel->doclist->Reload(false);
//        $this->updateStatusButtons();
    }

    public function updateStatusButtons() {
        $common = System::getOptions("common");

        $this->statuspan->statusform->bclose->setVisible(true);

        $state = $this->_doc->state;

        //доставлен
        $closed = $this->_doc->checkStates(array(Document::STATE_CLOSED)) > 0;
        //выполняется
        $inproc = $this->_doc->checkStates(array(Document::STATE_INPROCESS)) > 0;
        //аннулирован
        $ref = $this->_doc->checkStates(array(Document::STATE_REFUSED)) > 0;

        $this->statuspan->statusform->btopay->setVisible(false);
        $this->statuspan->statusform->brd->setVisible(false);
        $this->statuspan->statusform->brec->setVisible(false);
        $this->statuspan->statusform->bsent->setVisible(false);
        $this->statuspan->statusform->bscan->setVisible(false);
        $this->statuspan->moveform->setVisible(false);

        $this->statuspan->resform->setVisible(false);


        $this->statuspan->statusform->bscan->setAttribute('onclick', "openscan({$this->_doc->document_id})");


        //новый
        if ($state < Document::STATE_EXECUTED) {
            $this->statuspan->statusform->btask->setVisible(false);

            $this->statuspan->statusform->bclose->setVisible(false);
            $this->statuspan->statusform->bref->setVisible(false);
            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->bcopy->setVisible(false);
            $this->statuspan->statusform->bpos->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(false);
            $this->statuspan->statusform->bginv->setVisible(false);
            $this->statuspan->statusform->bco->setVisible(false);
            $this->statuspan->statusform->binp->setVisible(true);
            $this->statuspan->statusform->brd->setVisible(false);
            $this->statuspan->statusform->brec->setVisible(false);
            $this->statuspan->statusform->bsent->setVisible(false);
            $this->statuspan->statusform->bscan->setVisible(false);
        } else {

            $this->statuspan->statusform->bclose->setVisible(true);
            $this->statuspan->statusform->bref->setVisible(true);
            $this->statuspan->statusform->binp->setVisible(false);
            $this->statuspan->statusform->bco->setVisible(true);
            $this->statuspan->statusform->btask->setVisible(true);
        }


        if ($ref) {
            $this->statuspan->statusform->bclose->setVisible(false);
            $this->statuspan->statusform->bref->setVisible(false);
            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->bpos->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(false);
            $this->statuspan->statusform->bginv->setVisible(false);
            $this->statuspan->statusform->bscan->setVisible(false);
        }

        if ($state == Document::STATE_INPROCESS) {
            $this->statuspan->statusform->bsent->setVisible(true);
            $this->statuspan->statusform->brd->setVisible(true);
            $this->statuspan->statusform->bscan->setVisible(true);

            $this->statuspan->statusform->bttn->setVisible(true);
            $this->statuspan->statusform->bpos->setVisible(true);
            $this->statuspan->statusform->bgi->setVisible(true);
            $this->statuspan->statusform->bginv->setVisible(true);
        }
        if ($state == Document::STATE_READYTOSHIP) {
            $this->statuspan->statusform->bsent->setVisible(true);
            $this->statuspan->statusform->brec->setVisible(true);
            $this->statuspan->statusform->bscan->setVisible(true);

            $this->statuspan->statusform->bttn->setVisible(true);
            $this->statuspan->statusform->bpos->setVisible(true);
            $this->statuspan->statusform->bgi->setVisible(true);
            $this->statuspan->statusform->bginv->setVisible(true);      
        }
      
        if ($state == Document::STATE_INSHIPMENT) {

            $this->statuspan->statusform->brec->setVisible(true);
            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->bpos->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(false);
            $this->statuspan->statusform->bginv->setVisible(false);
            $this->statuspan->statusform->btask->setVisible(false);
        }
        if ($state == Document::STATE_READYTOSHIP) {

            $this->statuspan->statusform->bttn->setVisible(true);
            $this->statuspan->statusform->bpos->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(true);
            $this->statuspan->statusform->bginv->setVisible(true);
            $this->statuspan->statusform->btask->setVisible(false);
        }
        if ($state == Document::STATE_DELIVERED) {

            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->bpos->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(false);
            $this->statuspan->statusform->bginv->setVisible(false);
            $this->statuspan->statusform->btask->setVisible(false);
            $this->statuspan->statusform->bref->setVisible(false);
        }
        //закрыт
        if ($state == Document::STATE_CLOSED) {

            $this->statuspan->statusform->bclose->setVisible(false);
            $this->statuspan->statusform->btask->setVisible(false);
            $this->statuspan->statusform->bpos->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(false);
            $this->statuspan->statusform->bginv->setVisible(false);
            $this->statuspan->statusform->binp->setVisible(false);
            $this->statuspan->statusform->bref->setVisible(false);
            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->brd->setVisible(false);
            $this->statuspan->statusform->bsent->setVisible(false);
            $this->statuspan->statusform->bscan->setVisible(false);
        }

        if ($state == Document::STATE_WP) {

            if($this->_doc->getHD('waitpay')==1) {
                $this->statuspan->statusform->btopay->setVisible(true);
                $this->statuspan->statusform->btopay->setLink("App\\PAges\\Register\\PayBayList", array($this->_doc->document_id));
            }

        }

        if ($state == Document::STATE_INPROCESS || $state == Document::STATE_FINISHED || $state == Document::STATE_READYTOSHIP) {
            $this->statuspan->resform->setVisible(true);
            $reserved = $this->_doc->hasStore();
            $this->statuspan->resform->bres->setVisible(!$reserved);
            $this->statuspan->resform->store->setVisible(!$reserved);
            $this->statuspan->resform->bunres->setVisible($reserved);
        }

        if ($this->_doc->payamount > 0 && $this->_doc->payamount > $this->_doc->payed) {
            // $this->statuspan->statusform->bclose->setVisible(false);
        }
        if ($state < 5) {
            $this->statuspan->statusform->bref->setVisible(true);
        }

        if($this->_doc->hasPayments() == false && ($state<4 || $state==Document::STATE_INPROCESS)) {
            $this->statuspan->moveform->setVisible(true);
        }


        $this->_tvars['askclose'] = false;
        if ($inproc == false || $closed == false) {
            $this->_tvars['askclose'] = true;
        }


        //проверяем  что уже есть отправка
        $list = $this->_doc->getChildren('TTN');

        if(count($list)>0) {
            $this->statuspan->resform->setVisible(false);
        }

       
        $list = $this->_doc->getChildren('GoodsIssue');

        if(count($list)>0) {
            $this->statuspan->resform->setVisible(false);
        }
    
    
        if ($this->_doc->hasPayments()) {
            $this->statuspan->statusform->bpos->setVisible(false);
        }
        
        $pt= $this->_doc->getHD('paytype');
        
        if ($pt ==1 ||  $pt==2) {
            $this->statuspan->statusform->bpos->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(false);
            $this->statuspan->statusform->bginv->setVisible(false);
        }
        if ($pt ==3  ) {
            $this->statuspan->statusform->bttn->setVisible(false);
        
        }
        
        
    }

    //просмотр
    public function showOnClick($sender) {
     
        $this->_doc = $sender->owner->getDataItem();
        if (false == \App\ACL::checkShowDoc($this->_doc, true)) {
            return;
        }
        $options = System::getOptions('common');
        
        
        $this->_doc = Document::load($this->_doc->document_id); 
          
        $this->_doc = $this->_doc->cast();

        $this->statuspan->setVisible(true);
        $this->statuspan->statusform->setVisible(true);
        $this->statuspan->statusform->setVisible(true);
        $this->statuspan->docview->setDoc($this->_doc);

       // $this->listpanel->doclist->Reload(false);
        $this->updateStatusButtons();
        $this->goAnkor('dankor');
        $this->_tvars['askclose'] = false;
        $conn= \ZDB\DB::getConnect() ;

        $stl = array() ;
        foreach($conn->Execute("select store_id,storename from stores") as $row) {
            $stl[$row['store_id']]=$row['storename'];
        }
        $this->_tvars['isciprod']=false;
        $this->_tvars['sitems'] = [];
        $this->_tvars['citems'] = [];
        foreach($this->_doc->unpackDetails('detaildata') as $it) {
            $ait=array('itemname'=>$it->itemname,'itemcode'=>$it->item_code,'itemqty'=>$it->quantity);

            //на  складе
            $ait['citemsstore']  =  array();
            $onstores=0;
            foreach($it->getQuantityAllStores( ) as $s=>$q) {
                
                if(0 < doubleval($q)) {
                    $ait['citemsstore'][] = array('itstore'=>$stl[$s],'itqty'=>H::fqty($q));
                    $onstores += $q;
                }
            }
            //у  поставщика
            $ait['citemscust']  =  array();
            foreach(\App\Entity\CustItem::find("item_id={$it->item_id} ") as $ci) {
                $cer = array('itcust'=>$ci->customer_name,'itcustcode'=>$ci->cust_code);
                $cer['itcustprice']  = H::fa($ci->price);
                $cer['itcustupdated']  = H::fd($ci->updatedon);

                $cer['itcustqty']  = doubleval($ci->quantity)> 0 ? H::fqty($ci->quantity) : "";


                $ait['citemscust'][]=$cer;
            }
         
           //готово  к производству
            $ait['ciprod']  =  array();
           

            if($options['useprod']==1) {
                $prod=[];
                $itpr=\App\Entity\Item::getFirst("disabled<> 1 and  item_id = {$it->item_id} and  item_id in(select pitem_id from item_set)") ;
                if($itpr instanceof \App\Entity\Item)  {
                    $max = 1000000;
                    $parts = \App\Entity\ItemSet::find("pitem_id=".$itpr->item_id) ;

                    foreach($parts as $part) {
                        $pi = \App\Entity\Item::load($part->item_id);
                        if($pi==null) {
                            continue;
                        }
                        $pqty = $pi->getQuantity();
                        if($pqty==0) {
                            $max=0;
                            break;
                        }
                        $t = $pqty/$part->qty;
                        if($t<$max) {
                            $max = $t;
                        }

                    }
                    if($max>0 && $max < 1000000) {
                        $ait['prqty']= H::fqty($max);
                        $this->_tvars['isciprod']=true;  //если хоть один  готов
                    }

                

                }         
               
            }
            
            $need=$it->quantity - $onstores;
            if($need >0) {
               $ait['toco']  =  "addItemToCO({$it->item_id},{$need})";
            } else {
               $ait['toco']  =  "addItemToCO({$it->item_id})";                
            }


            $this->_tvars['citems'][]=$ait;
       
           //в закупке
              
            $sitems=[];
            
            $corders= Document::find("meta_name='OrderCust' and state in(5,7) ")  ;
            
            foreach($corders as $o) {
               
                  foreach($o->unpackDetails('detaildata') as $cit) {
                       if($it->item_id==$cit->item_id) {
                           $r=[] ;
                           $r['dnum']  = $o->document_number;
                           $r['dd']  = ($o->headerdata['delivery_date'] ?? 0) >0 ? H::fd($o->headerdata['delivery_date']) :'';
                           $r['dc']  = $o->customer_name;
                           $sitems[$o->document_id] = $r;
                           break;
                       }
                  }
                
           }
       
           foreach($sitems as $_si ) {
              $this->_tvars['sitems'][] = $_si ;
           }

        }

        $this->_tvars['issitems']= count($sitems) >0;

        $this->statuspan->moveform->brmove->setValue($this->_doc->branch_id) ;
        $this->onBranch($this->statuspan->moveform->brmove);
        $this->statuspan->moveform->usmove->setValue($this->_doc->user_id);
        
        $this->addJavaScript(" $(\"[data-did={$this->_doc->document_id}]\").addClass( 'table-success') ",true)  ;
 
    }

    public function editOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
        if (false == \App\ACL::checkEditDoc($doc, true)) {
            return;
        }
        $cc = $doc->canCanceled();
        if (strlen($cc) > 0) {
            $this->setError($cc);

            return;
        }
        if($doc->hasStore()) {
           $doc->setHD('doreserv',1);
        }
        $doc->updateStatus(Document::STATE_CANCELED);
        $doc->payed = 0;
        $doc->save();
        App::Redirect("\\App\\Pages\\Doc\\Order", $doc->document_id);
    }

    public function oncsv($sender) {
        $list = $this->listpanel->doclist->getDataSource()->getItems(-1, -1, 'document_id');

        $header = array();
        $data = array();

        $i = 0;
        foreach ($list as $d) {
            $i++;
            $data['A' . $i] = H::fd($d->document_date);
            $data['B' . $i] = $d->document_number;
            $data['C' . $i] = $d->customer_name;
            $data['D' . $i] = $d->amount;
            $data['E' . $i] = Document::getStateName($d->state);
            $data['F' . $i] = $d->notes;
        }

        H::exportExcel($data, $header, 'orderlist.xlsx');
    }

    public function onBranch($sender) {
        $id = $sender->getValue();
        $users = array(0=> "Не обрано" );

        foreach(\App\Entity\User::getByBranch($id) as $id=>$u) {
            $users[$id] = $u ;
        }

        $this->statuspan->moveform->usmove->setOptionList($users);
    }

    public function moveOnSubmit($sender) {
        $br = intval($this->statuspan->moveform->brmove->getValue());
        $us = $this->statuspan->moveform->usmove->getValue();
        if($br>0) {
            $this->_doc->branch_id = $br;
        }
        if($us>0) {
            $this->_doc->user_id = $us;
        }

        if($br>0 || $us>0) {
            $this->_doc->save();
            $this->listpanel->doclist->Reload();

            $this->statuspan->setVisible(false);

        }

    }

    public function openedit() {
        $this->editpanel->setVisible(true);
        $this->listpanel->setVisible(false);
        $this->statuspan->setVisible(false);
      
        $this->_doc = Document::load($this->_doc->document_id);
        $this->editpanel->editchat->setAttribute('onclick', "opencchat({$this->_doc->document_id})");

        $ch = $this->checkChat($this->_doc);
        $this->editpanel->editchat->setVisible($ch);
        
        $this->editpanel->editdn->setText($this->_doc->document_number);
        $this->_itemlist = [];
        foreach($this->_doc->unpackDetails('detaildata')  as $it) {

            $it->checked = $it->checked ?? false;
            $it->checkedqty =   0;
            $this->_itemlist[] = $it;

        }

        $this->editpanel->editform->edititemlist->Reload();

    }

    public function editlistOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new  Label('editlistname', $item->itemname));
        $row->add(new  Label('editlistcode', $item->item_code));
        $row->add(new  Label('editlistbarcode', $item->bar_code));
        $row->add(new  Label('editlistqty', $item->quantity));
        $row->add(new CheckBox('checkscan', new \Zippy\Binding\PropertyBinding($item, 'checked')));

    }

    public function addcodeOnClick($sender) {
        $code = trim($this->editpanel->editform->editbarcode->getText());

        $code0 = ltrim($code, '0');

        $this->editpanel->editform->editbarcode->setText('');
        if ($code == '') {
            return;
        }

        foreach ($this->_itemlist as $ri => $_item) {
            if ($_item->bar_code == $code || $_item->item_code == $code || $_item->bar_code == $code0 || $_item->item_code == $code0) {
                if($this->_itemlist[$ri]->checkedqty ==  $this->_itemlist[$ri]->quantity) {
                    $this->setWarn('Лишній товар') ;
                    $this->addJavaScript("new Audio('/assets/error.mp3').play()", true);
                               
                    return;
                }
                $this->_itemlist[$ri]->checkedqty += 1;
                if($this->_itemlist[$ri]->checkedqty ==  $this->_itemlist[$ri]->quantity) {
                    $this->_itemlist[$ri]->checked = true;
                }

                $this->editpanel->editform->edititemlist->Reload();
                $this->addJavaScript("new Audio('/assets/good.mp3').play()", true);

                return;
            }
        }
        $this->setWarn('Товар не знайдено') ;
        $this->addJavaScript("new Audio('/assets/error.mp3').play()", true);


    }

   
    public function editOnSubmit($sender) {


            $conn = \ZDB\DB::getConnect();
            $conn->BeginTrans();

            try {
 

                foreach ($this->_itemlist as   $_item) {
                    if($sender->id == "editready" && $_item->checked != true) {
                        $this->setError('Не зібрані всі позиції') ;

                        return;
                    }
                }


                $this->_doc->packDetails('detaildata', $this->_itemlist)  ;
                $this->_doc->save();
                if ($sender->id == "editready") {
                    $this->_doc->updateStatus(Document::STATE_READYTOSHIP);
                    $this->listpanel->doclist->Reload(false);

                }
             $conn->CommitTrans();

        } catch(\Exception $e) {
            $this->setError($e->getMessage()) ;
            $conn->RollbackTrans();
            return;
        }
        $this->editpanel->setVisible(false);
        $this->listpanel->setVisible(true);

    }
    //vue

    /**
    * список  ТМЦ в  заказе
    *
    * @param mixed $args
    */
    public function getCChatItems($args) {
        $doc = Document::load($args[0]) ;
        $ret=[];
        $ret['itemlist'] =[];
        foreach ($doc->unpackDetails('detaildata') as $item) {
            $ret['itemlist'][] = array(
              'itemname'=>$item->itemname,
              'item_code'=>$item->item_code,
              'quantity'=>H::fqty($item->quantity),
              'price'=> H::fa($item->price)
            ) ;

        }
      
        $this->jsonOK($ret) ;

    }

    /**
    * список  сообщений по  заказу
    *
    * @param mixed $args[0]  -document_id
    */
    public function getCChatMessages($args) {

        $ret=[];
        $list = \App\Entity\Message::find("item_id={$args[0]} and item_type=" .\App\Entity\Message::TYPE_CUSTCHAT, "message_id asc");

        $ret['msglist'] = [];

        foreach($list as $msg) {
            $m=[];
            $m['isseller']  = $msg->user_id >0;
            $m['message']  = $msg->message;
            $m['checked']  = $msg->checked==1;
            $m['msgdate'] = date('Y-m-d H:i', $msg->created);


            $ret['msglist'][] = $m;

            if(!$m['isseller']) {
                $msg->checked = 1;
                $msg->save();
            }


        }

       $this->jsonOK($ret) ;
   
    }
    /**
    * отправка сообшения заказчику
    *
    * @param mixed $args
    */
    public function sendMessage($args, $post) {
        $doc = Document::load($args[0]) ;
        $message = json_decode($post)   ;

        $issms = (\App\System::getOption('sms', 'smstype')??0) >0 ;
        if($issms == 0) {
           return  $this->jsonError("Не знайдений сервіс смс") ;
        }

        $phone= $doc->headerdata['phone'] ??'';
        if($phone=='') {
            $c =  \App\Entity\Customer::load($doc->customer_id) ;
            $phone = $c->phone ?? '';
        }
        if($phone == '') {
           return  $this->jsonError("Не знайдений телефон") ;

        }

        $link = _BASEURL . 'cchat/' . $args[0]. '/'. $doc->headerdata['hash'];
       
        $fn = (\App\System::getOption('common', 'shopname')??'')  ;

        $text = "Маємо запитання  по  вашому  замовленню. Відповісти за адресою ".$link;

        $r = \App\Entity\Subscribe::sendSMS($phone, $text) ;
        if($r!="") {
          return  $this->jsonError($r) ;
        }

        $msg = new \App\Entity\Message() ;
        $msg->message=$message;
        $msg->user_id= \App\System::getUser()->user_id;
        $msg->item_id=$doc->document_id;
        $msg->item_type=\App\Entity\Message::TYPE_CUSTCHAT;
        $msg->save() ;

        return $this->jsonOK() ;
             

    }


}

/**
 *  Источник  данных  для   списка  документов
 */
class OrderDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();
        $filter=$this->page->listpanel->filter;

          
        $where = "     meta_name  = 'Order'   ";

        $salesource =$filter->salesource->getValue();
        if ($salesource > 0) {
            $where .= " and   content like '%<salesource>{$salesource}</salesource>%'  ";

        }

        $status = $filter->status->getValue();
        if ($status == 0) {
            $where .= " and  state not in (9,17,15)   ";
        }
        if ($status == 1) {
            $where .= " and  state =1 ";
        }
        if ($status == 2) {
            $where .= " and   (state = 21 or content like '%<waitpay>1</waitpay>%') ";
        }


        $st = trim($filter->searchtext->getText());
        if (strlen($st) > 2) {
            $st = $conn->qstr('%' . $st . '%');

            $where = "  meta_name  = 'Order'  and  content like {$st} ";
        }
        $sn = trim($filter->searchnumber->getText());
        if (strlen($sn) > 1) { // игнорируем другие поля
            $sn = $conn->qstr('%' . $sn . '%');
            $where = "  meta_name  = 'Order' and  document_number like  {$sn} ";
        }

        return $where;
    }

    public function getItemCount() {
        return Document::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $docs = Document::find($this->getWhere(), "document_id desc", $count, $start);
        //         $docs = Document::find($this->getWhere(), "priority desc,document_id desc", $count, $start);

        return $docs;
    }

    public function getItem($id) {

    }

}

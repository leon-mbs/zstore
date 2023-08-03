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
use Zippy\Html\Panel;
use App\Entity\Pay;

/**
 * журнал  заказов
 */
class OrderList extends \App\Pages\Base
{
    private $_doc = null;
    private $_issms = false; //подключен  смс  сервис

    /**
     *
     * @param mixed $docid Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('OrderList')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');

        $this->filter->add(new TextInput('searchnumber'));
        $this->filter->add(new TextInput('searchtext'));
        $this->filter->add(new DropDownChoice('status', array(0 => 'Вiдкритi', 1 => 'Новi',2 => 'До сплати', 3 => 'Всi'), 0));
        $this->filter->add(new DropDownChoice('salesource', H::getSaleSources(), 0));

        $doclist = $this->add(new DataView('doclist', new OrderDataSource($this), $this, 'doclistOnRow'));

        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(H::getPG());

        $this->add(new Panel("statuspan"))->setVisible(false);

        $this->statuspan->add(new Form('statusform'));

        $this->statuspan->statusform->add(new SubmitButton('bclose'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('binp'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('brd'))->onClick($this, 'statusOnSubmit');

        $this->statuspan->statusform->add(new SubmitButton('bpos'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bgi'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bco'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bref'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bcopy'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bttn'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('btask'))->onClick($this, 'statusOnSubmit');


        $this->statuspan->statusform->add(new \Zippy\Html\Link\RedirectLink('btopay'));

        $this->statuspan->add(new \App\Widgets\DocView('docview'));

        $this->statuspan->add(new Form('moveform'));
        $this->statuspan->moveform->add(new DropDownChoice('brmove', \App\Entity\Branch::getList(), \App\Acl::getCurrentBranch()))->onChange($this, "onBranch", true);
        $this->statuspan->moveform->add(new DropDownChoice('usmove', array(), 0));
        $this->statuspan->moveform->add(new SubmitButton('bmove'))->onClick($this, 'MoveOnSubmit');

        $this->statuspan->add(new Form('resform'))->setVisible(false);

        $this->statuspan->resform->add(new SubmitButton('bres'))->onClick($this, 'resOnSubmit');
        $this->statuspan->resform->add(new SubmitButton('bunres'))->onClick($this, 'resOnSubmit');
        $this->statuspan->resform->add(new DropDownChoice('store', \App\Entity\Store::getList(), H::getDefStore()));


        $this->doclist->Reload();
        $this->add(new ClickLink('csv', $this, 'oncsv'));


        $this->add(new Form('payform'))->onSubmit($this, 'payOnSubmit');
        $this->payform->add(new DropDownChoice('payment', \App\Entity\MoneyFund::getList(), H::getDefMF()));
        $this->payform->add(new DropDownChoice('pos', \App\Entity\Pos::findArray('pos_name', "details like '%<usefisc>1</usefisc>%' "), 0));
        $this->payform->add(new TextInput('pamount'));
        $this->payform->add(new TextInput('pcomment'));
        $this->payform->add(new CheckBox('closeorder'));
        $this->payform->add(new Date('pdate', time()));
        $this->payform->setVisible(false);

        $this->_issms = (System::getOption('sms','smstype')??0) >0 ;


    }

    public function filterOnSubmit($sender) {

        $this->statuspan->setVisible(false);
        $this->payform->setVisible(false);

        $this->doclist->Reload();
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


        $row->add(new Label('isreserved'))->setVisible($doc->hasStore());

        $stname = Document::getStateName($doc->state);

        $row->add(new Label('state', $stname));
        if ($doc->state == Document::STATE_NEW) {
            $row->state->setText('<span class="badge badge-info">' . $stname . '</span>', true);
        }
        if ($doc->state == Document::STATE_READYTOSHIP || $doc->state == Document::STATE_INSHIPMENT || $doc->state == Document::STATE_DELIVERED
        ) {
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
        if ($doc->document_id == ($this->_doc->document_id ?? 0)) {
            $row->setAttribute('class', 'table-success');
        }
        
        
        
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
        $this->doclist->Reload(false);

    }

    public function statusOnSubmit($sender) {
        if (\App\Acl::checkChangeStateDoc($this->_doc, true, true) == false) {
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

        if ($sender->id == "binp") {
            $this->_doc->updateStatus(Document::STATE_INPROCESS);
        }
        if ($sender->id == "brd") {
            $this->_doc->updateStatus(Document::STATE_READYTOSHIP);
        }
        if ($sender->id == "bref") {
            $this->_doc->updateStatus(Document::STATE_FAIL);

            $this->setWarn('Замовлення анульовано');
        }
        if ($sender->id == "btask") {
            $task = count($this->_doc->getChildren('Task')) > 0;

            if ($task) {

                $this->setWarn('Вже існує документ Наряд');
            }
            App::Redirect("\\App\\Pages\\Doc\\Task", 0, $this->_doc->document_id);
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
            App::Redirect("\\App\\Pages\\Doc\\POSCheck", 0, $this->_doc->document_id);
            return;
        }

        if ($sender->id == "bgi") {
            if ($invoice) {
                $this->setWarn('Вже існує документ Видаткова накладна');
            }
            App::Redirect("\\App\\Pages\\Doc\\GoodsIssue", 0, $this->_doc->document_id);
            return;
        }
        if ($sender->id == "bco") {

            App::Redirect("\\App\\Pages\\Doc\\OrderCust", 0, $this->_doc->document_id);
            return;
        }


        if ($sender->id == "bclose") {



            if($this->_doc->payamount >0 && $this->_doc->payamount>$this->_doc->payed) {
                $this->setWarn('"Замовлення закрито без оплати"');
            }

            if($ttn== false && $gi == false) {
                $this->setWarn('Замовлення закрито без доставки');
            }


            $this->_doc->updateStatus(Document::STATE_CLOSED);
            $this->statuspan->setVisible(false);
        }

        $this->doclist->Reload(false);
        $this->updateStatusButtons();
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
        $this->statuspan->moveform->setVisible(false);

        $this->statuspan->resform->setVisible(false);


        //новый
        if ($state < Document::STATE_EXECUTED) {
            $this->statuspan->statusform->btask->setVisible(false);

            $this->statuspan->statusform->bclose->setVisible(false);
            $this->statuspan->statusform->bref->setVisible(false);
            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->bcopy->setVisible(false);
            $this->statuspan->statusform->bpos->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(false);
            $this->statuspan->statusform->bco->setVisible(false);
            $this->statuspan->statusform->binp->setVisible(true);
            $this->statuspan->statusform->brd->setVisible(false);
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
            $this->statuspan->statusform->brd->setVisible(false);
        }

        if ($state == Document::STATE_INPROCESS) {
            $this->statuspan->statusform->brd->setVisible(true);

            $this->statuspan->statusform->bttn->setVisible(true);
            $this->statuspan->statusform->bpos->setVisible(true);
            $this->statuspan->statusform->bgi->setVisible(true);
        }
        if ($state == Document::STATE_INSHIPMENT) {

            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->bpos->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(false);
            $this->statuspan->statusform->btask->setVisible(false);
        }
        if ($state == Document::STATE_READYTOSHIP) {

            $this->statuspan->statusform->bttn->setVisible(true);
            $this->statuspan->statusform->bpos->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(true);
            $this->statuspan->statusform->btask->setVisible(false);
        }
        if ($state == Document::STATE_DELIVERED) {

            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->bpos->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(false);
            $this->statuspan->statusform->btask->setVisible(false);
            $this->statuspan->statusform->bref->setVisible(false);
        }
        //закрыт
        if ($state == Document::STATE_CLOSED) {

            $this->statuspan->statusform->bclose->setVisible(false);
            $this->statuspan->statusform->btask->setVisible(false);
            $this->statuspan->statusform->bpos->setVisible(false);
            $this->statuspan->statusform->bgi->setVisible(false);
            $this->statuspan->statusform->binp->setVisible(false);
            $this->statuspan->statusform->bref->setVisible(false);
            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->brd->setVisible(false);
        }

        if ($state == Document::STATE_WP) {

            if($this->_doc->payamount > 0 &&  $this->_doc->payamount >  $this->_doc->payed) {
                $this->statuspan->statusform->btopay->setVisible(true);
                $this->statuspan->statusform->btopay->setLink("App\\PAges\\Register\\PayBayList", array($this->_doc->document_id));
            }

        }

        if ($state == Document::STATE_INPROCESS) {
            $this->statuspan->resform->setVisible(true);
            $reserved = $this->_doc->hasStore();
            $this->statuspan->resform->bres->setVisible(!$reserved);
            $this->statuspan->resform->store->setVisible(!$reserved);
            $this->statuspan->resform->bunres->setVisible($reserved);
        }
   
        if ($this->_doc->payamount > 0 && $this->_doc->payamount > $this->_doc->payed) {
            // $this->statuspan->statusform->bclose->setVisible(false);
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

        if (count($list) > 0 && $common['numberttn'] <> 1) {
            $this->statuspan->statusform->bttn->setVisible(false);
        }
        $list = $this->_doc->getChildren('GoodsIssue');

        if(count($list)>0) {
            $this->statuspan->resform->setVisible(false);
        }

        if (count($list) > 0 && $common['numberttn'] <> 1) {
            $this->statuspan->statusform->bgi->setVisible(false);
        }


        $list = $this->_doc->getChildren('Invoice');
        if (count($list) > 0) {
            $this->statuspan->statusform->bpos->setVisible(false);
        }
    }

    //просмотр
    public function showOnClick($sender) {
        $this->payform->setVisible(false);

        $this->_doc = $sender->owner->getDataItem();
        if (false == \App\ACL::checkShowDoc($this->_doc, true)) {
            return;
        }
        $this->_doc = $this->_doc->cast();

        $this->statuspan->setVisible(true);
        $this->statuspan->statusform->setVisible(true);
        $this->statuspan->statusform->setVisible(true);
        $this->statuspan->docview->setDoc($this->_doc);

        $this->doclist->Reload(false);
        $this->updateStatusButtons();
        $this->goAnkor('dankor');
        $this->_tvars['askclose'] = false;
        $conn= \zdb\db::getConnect() ;

        $stl = array() ;
        foreach($conn->Execute("select store_id,storename from stores") as $row) {
            $stl[$row['store_id']]=$row['storename'];
        }

        $this->_tvars['citems'] = array();
        foreach($this->_doc->unpackDetails('detaildata') as $it) {
            $ait=array('itemname'=>$it->itemname,'itemcode'=>$it->item_code,'itemqty'=>$it->quantity);

            $ait['citemsstore']  =  array();

            foreach($stl as $k=>$v) {
                $qty = $it->getQuantity($k);
                if(0 < doubleval($qty)) {
                    $ait['citemsstore'][] = array('itstore'=>$v,'itqty'=>H::fqty($qty));
                }
            }
            $ait['citemscust']  =  array();
            foreach(\App\Entity\CustItem::find("item_id={$it->item_id} ") as $ci) {
                $cer = array('itcust'=>$ci->customer_name,'itcustcode'=>$ci->cust_code,'itcustcomment'=>$ci->comment);
                $cer['itcustprice']  = H::fa($ci->price);
                $cer['itcustupdated']  = H::fd($ci->updatedon);

                $cer['itcustqty']  = doubleval($ci->quantity)> 0 ? H::fqty($ci->quantity) : "";


                $ait['citemscust'][]=$cer;
            }



            $this->_tvars['citems'][]=$ait;


        }

        $this->statuspan->moveform->brmove->setValue($this->_doc->branch_id) ;
        $this->onBranch($this->statuspan->moveform->brmove);
        $this->statuspan->moveform->usmove->setValue($this->_doc->user_id);
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
        $doc->updateStatus(Document::STATE_CANCELED);
        $doc->payed = 0;
        $doc->save();
        App::Redirect("\\App\\Pages\\Doc\\Order", $doc->document_id);
    }

    public function oncsv($sender) {
        $list = $this->doclist->getDataSource()->getItems(-1, -1, 'document_id');

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
            $this->doclist->Reload();
            $this->statuspan->setVisible(false);

        }

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

        $where = "     meta_name  = 'Order'  ";

        $salesource = $this->page->filter->salesource->getValue();
        if ($salesource > 0) {
            $where .= " and   content like '%<salesource>{$salesource}</salesource>%' ";

        }

        $status = $this->page->filter->status->getValue();
        if ($status == 0) {
            $where .= " and  state not in (9,17) ";
        }
        if ($status == 1) {
            $where .= " and  state =1 ";
        }
        if ($status == 2) {
            $where .= " and  state =21 ";
        }


        $st = trim($this->page->filter->searchtext->getText());
        if (strlen($st) > 2) {
            $st = $conn->qstr('%' . $st . '%');

            $where .= " and  meta_name  = 'Order'  and  content like {$st} ";
        }
        $sn = trim($this->page->filter->searchnumber->getText());
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
        $docs = Document::find($this->getWhere(), "priority desc,document_id desc", $count, $start);

        return $docs;
    }

    public function getItem($id) {

    }

}

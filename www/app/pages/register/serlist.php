<?php

namespace App\Pages\Register;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Service;
use App\Entity\Item;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

/**
 * журнал  услуг
 */
class SerList extends \App\Pages\Base
{
    private $_doc = null;
    public $_serlist = [];
    public $_itemlist = [];

    /**
     *
     * @param mixed $docid Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('SerList')) {
            \App\Application::RedirectHome() ;
        }

        $this->add(new Panel('listpan'));
        $this->listpan->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');

        $this->listpan->filter->add(new TextInput('searchnumber'));
        $this->listpan->filter->add(new TextInput('searchtext'));
        $this->listpan->filter->add(new DropDownChoice('status', array(0 => "Відкриті", 1 => "Нові", 2 => "Виконуються", 3 => "Всі"), 0));

        $doclist = $this->listpan->add(new DataView('doclist', new SerListDataSource($this), $this, 'doclistOnRow'));

        $this->listpan->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(H::getPG());

        $this->add(new Panel("statuspan"))->setVisible(false);

        $this->statuspan->add(new Form('statusform'));

        $this->statuspan->statusform->add(new SubmitButton('bpos'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bwarranty'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bfin'))->onClick($this, 'statusOnSubmit');

        $this->statuspan->statusform->add(new SubmitButton('binproc'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bref'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('btask'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new \Zippy\Html\Link\RedirectLink('btopay'));

        $this->statuspan->add(new \App\Widgets\DocView('docview'));

        $this->listpan->doclist->Reload();
        $this->listpan->add(new ClickLink('csv', $this, 'oncsv'));

        $this->add(new Panel("editpan"))->setVisible(false);
        $this->editpan->add(new Label('etotal'));

        $this->editpan->add(new Form('sform'));
        $this->editpan->sform->add(new DropDownChoice('sser', \App\Entity\Service::getList(), 0))->onChange($this, 'onChangeSer');
        $this->editpan->sform->add(new TextInput('sdesc'));
        $this->editpan->sform->add(new TextInput('sqty'));
        $this->editpan->sform->add(new TextInput('sprice'));
        $this->editpan->sform->add(new SubmitButton('ssubmit'))->onClick($this, 'saveSer');

        $this->editpan->add(new Form('iform'));
        $this->editpan->iform->add(new AutocompleteTextInput('iitem'))->onText($this, 'OnAutoItem');
        $this->editpan->iform->iitem->onChange($this, 'OnChangeItem', true);
        $this->editpan->iform->add(new TextInput('iqty'));
        $this->editpan->iform->add(new TextInput('iprice'));
        $this->editpan->iform->add(new TextInput('isn'));
        $this->editpan->iform->add(new SubmitButton('isubmit'))->onClick($this, 'saveItem');


        $this->editpan->add(new ClickLink('closeedit', $this, 'onCloseEdit'));
        $this->editpan->add(new ClickLink('saveedit', $this, 'onSaveEdit'));
        $this->editpan->add(new DataView('slist', new ArrayDataSource($this, "_serlist"), $this, 'slistOnRow'));
        $this->editpan->add(new DataView('ilist', new ArrayDataSource($this, "_itemlist"), $this, 'ilistOnRow'));


    }

    public function filterOnSubmit($sender) {


        $this->statuspan->setVisible(false);

        $this->listpan->doclist->Reload();
    }

    public function doclistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $doc = $row->getDataItem();

        $row->add(new ClickLink('number', $this, 'showOnClick'))->setValue($doc->document_number);

        $row->add(new Label('date', H::fd($doc->document_date)));
        $row->add(new Label('onotes', $doc->notes));
        $row->add(new Label('amount', H::fa($doc->amount)));

        $row->add(new Label('customer', $doc->customer_name));
        $row->add(new Label('customerphone', $doc->headerdata['customerphone'] ?? ''));

        $row->add(new Label('state', Document::getStateName($doc->state)));

        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        if ($doc->state < Document::STATE_EXECUTED || ($doc->state == Document::STATE_INPROCESS && floatval($doc->payed) ==0)) {
            $row->edit->setVisible(true);
        } else {
            $row->edit->setVisible(false);
        }
        if ($doc->document_id == ($this->_doc->document_id ??0)) {
            $row->setAttribute('class', 'table-success');
        }

    }

    public function statusOnSubmit($sender) {
        if (\App\ACL::checkChangeStateDoc($this->_doc, true, true) == false) {
            return;
        }

        $state = $this->_doc->state;
        $list = $this->_doc->getChildren('POSCheck');
        $pos = count($list) > 0;


        $gi = count($this->_doc->getChildren('GoodsIssue')) > 0;
        $task = count($this->_doc->getChildren('Task')) > 0;

        if ($sender->id == "btask") {
            if ($task) {

                $this->setWarn('Вже існує документ Наряд');
            }
            App::Redirect("\\App\\Pages\\Doc\\Task", 0, $this->_doc->document_id);
            return;
        }
        if ($sender->id == "bpos") {
            if ($pos) {
                $this->setWarn('Вже існує документ Чек');
            }
            App::Redirect("\\App\\Pages\\Service\\ARMPos", 0, $this->_doc->document_id);
            return;

        }
        if ($sender->id == "bwarranty") {
 
            App::Redirect("\\App\\Pages\\Doc\\Warranty", 0, $this->_doc->document_id);
            return;            
        }
        if ($sender->id == "bref") {
            if ($gi || $task) {

                $this->setWarn('Були створені документи Наряд та/або Видаткова накладна');
            }
            $this->_doc->updateStatus(Document::STATE_REFUSED);
        }

        if ($sender->id == "binproc") {
            $this->_doc->updateStatus(Document::STATE_INPROCESS);
        }
        if ($sender->id == "bfin") {
            $this->_doc->updateStatus(Document::STATE_FINISHED);

            if($this->_doc->payamount > 0 && $this->_doc->payamount > $this->_doc->payed) {
                if($pos==false){
                  $this->_doc->updateStatus(Document::STATE_WP);
                }
            }



        }



        $this->listpan->doclist->Reload(false);

        $this->updateStatusButtons();
    }

    public function updateStatusButtons() {


        $state = $this->_doc->state;

        $this->statuspan->statusform->btopay->setVisible(false);

        //новый
        if ($state < Document::STATE_EXECUTED) {
            $this->statuspan->statusform->binproc->setVisible(true);
            $this->statuspan->statusform->bwarranty->setVisible(true);

            $this->statuspan->statusform->bpos->setVisible(false);
            $this->statuspan->statusform->bref->setVisible(false);
            $this->statuspan->statusform->btask->setVisible(false);
            $this->statuspan->statusform->bfin->setVisible(false);
        }


        // в работе
        if ($state == Document::STATE_INPROCESS) {

            $this->statuspan->statusform->binproc->setVisible(false);

            $this->statuspan->statusform->bwarranty->setVisible(true);
            $this->statuspan->statusform->bpos->setVisible(true);
            $this->statuspan->statusform->bref->setVisible(true);
            $this->statuspan->statusform->btask->setVisible(true);
            $this->statuspan->statusform->bfin->setVisible(true);
        }

        // выполнен
        if ($state == Document::STATE_FINISHED) {

            $this->statuspan->statusform->binproc->setVisible(false);

            $this->statuspan->statusform->bwarranty->setVisible(true);
            $this->statuspan->statusform->bpos->setVisible(false);
            $this->statuspan->statusform->bref->setVisible(false);
            $this->statuspan->statusform->btask->setVisible(false);
            $this->statuspan->statusform->bfin->setVisible(false);
        }
        // ждет оплату
        if ($state == Document::STATE_WP) {

            $this->statuspan->statusform->binproc->setVisible(false);

            $this->statuspan->statusform->bwarranty->setVisible(false);
      //      $this->statuspan->statusform->bpos->setVisible(false);
            $this->statuspan->statusform->bref->setVisible(false);
            $this->statuspan->statusform->btask->setVisible(false);
            $this->statuspan->statusform->bfin->setVisible(false);
        }

        //к  оплате
        if ($state == Document::STATE_WP) {

            if($this->_doc->payamount > 0 &&  $this->_doc->payamount >  $this->_doc->payed) {
                $this->statuspan->statusform->btopay->setVisible(true);
                $this->statuspan->statusform->btopay->setLink("App\\PAges\\Register\\PayBayList", array($this->_doc->document_id));
            }

        }
        //закрыт
        if ($state == Document::STATE_CLOSED) {
            $this->statuspan->statusform->binproc->setVisible(false);

            $this->statuspan->statusform->bwarranty->setVisible(false);
            $this->statuspan->statusform->bpos->setVisible(false);
            $this->statuspan->statusform->bref->setVisible(false);
            $this->statuspan->statusform->btask->setVisible(false);
            $this->statuspan->statusform->bfin->setVisible(false);
            $this->statuspan->statusform->setVisible(false);
        }
        
        if ($this->_doc->hasPayments()) {
            $this->statuspan->statusform->bpos->setVisible(false);
        }
        
    }

    //просмотр
    public function showOnClick($sender) {

        $this->_doc = $sender->owner->getDataItem();
        $this->_doc = $this->_doc->cast();
        if (false == \App\ACL::checkShowDoc($this->_doc, true)) {
            return;
        }

        $this->statuspan->setVisible(true);
        $this->statuspan->docview->setDoc($this->_doc);

        $this->listpan->doclist->Reload(false);
        $this->updateStatusButtons();
        $this->goAnkor('dankor');
    }

    public function editOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
        if (false == \App\ACL::checkEditDoc($doc, true)) {
            return;
        }
        if($doc->state == Document::STATE_INPROCESS) {
            $this->listpan->setVisible(false);
            $this->statuspan->setVisible(false);
            $this->editpan->setVisible(true);
            $this->_doc = $doc->cast();
            $this->_serlist =  $this->_doc->unpackDetails('detaildata') ;
            $this->_itemlist =  $this->_doc->unpackDetails('detail2data') ;

            $this->editpan->sform->sser->setValue(0) ;
            $this->editpan->sform->sdesc->setText('') ;
            $this->editpan->sform->sqty->setText('1') ;
            $this->editpan->sform->sprice->setText('') ;

            $this->editpan->iform->iitem->setKey(0) ;
            $this->editpan->iform->iitem->setText('') ;
            $this->editpan->iform->iqty->setText('1') ;
            $this->editpan->iform->iprice->setText('') ;

            $this->editpan->etotal->setText($doc->amount);
            $this->editpan->slist->Reload();
            $this->editpan->ilist->Reload();

            return;
        }

        App::Redirect("\\App\\Pages\\Doc\\ServiceAct", $doc->document_id);
    }

    public function slistOnRow($row) {
        $ser = $row->getDataItem();
        $row->add(new Label('rsservice_name', $ser->service_name));
        $row->add(new Label('rsdesc', $ser->desc));
        $row->add(new Label('rsquantity', H::fqty($ser->quantity)));
        $row->add(new Label('rsprice', H::fa($ser->price)));
        $row->add(new Label('rsamount', H::fa($ser->price * $ser->quantity)));
        $row->add(new Label('rsdisc', floatval($ser->disc) != 0 ? "-".H::fa1($ser->disc) : ''));
        $row->add(new ClickLink('rsdel'))->onClick($this, 'sdelOnClick');

    }

    public function ilistOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('riname', $item->itemname));
        $row->add(new Label('ricode', $item->item_code . ( strlen($item->snumber) >0 ? ' с/н: '. $item->snumber :'')  ));
        $row->add(new Label('riquantity', H::fqty($item->quantity)));
        $row->add(new Label('riprice', H::fa($item->price)));
        $row->add(new Label('riamount', H::fa($item->price * $item->quantity)));
        $row->add(new Label('ridisc', floatval($item->disc) != 0 ? "-".H::fa1($item->disc) : ''));
        $row->add(new ClickLink('ridel'))->onClick($this, 'idelOnClick');

    }

    public function sdelOnClick($sender) {
        $ser = $sender->owner->getDataItem();
        $rowid =  array_search($ser, $this->_serlist, true);
        $this->_serlist = array_diff_key($this->_serlist, array($rowid => $this->_serlist[$rowid]));
        $this->ecalc()  ;
    }

    public function idelOnClick($sender) {
        $item = $sender->owner->getDataItem();
        $rowid =  array_search($item, $this->_itemlist, true);
        $this->_itemlist = array_diff_key($this->_itemlist, array($rowid => $this->_itemlist[$rowid]));
        $this->ecalc() ;

    }

    private function ecalc() {
        $this->editpan->ilist->Reload();
        $this->editpan->slist->Reload();
        $a = 0;
        foreach($this->_serlist as $s) {
            $a += ($s->price * $s->quantity);
        }
        foreach($this->_itemlist as $i) {
            $a += ($i->price * $i->quantity);
        }
        $this->editpan->etotal->setText(H::fa($a)) ;


    }

    public function onSaveEdit($sender) {
        $this->_doc->packDetails('detaildata', $this->_serlist) ;
        $this->_doc->packDetails('detail2data', $this->_itemlist) ;

        $this->_doc->amount =  H::fa($this->editpan->etotal->getText());
        $this->_doc->payamount = floatval($this->_doc->amount)+ floatval($this->_doc->headerdata['bonus']) + floatval($this->_doc->headerdata['totaldisc']);


        $this->_doc->headerdata['timeentry'] = time();
        $this->_doc->save();
        $this->_doc->DoStore();
        $this->listpan->doclist->Reload();
        $this->listpan->setVisible(true);
        $this->editpan->setVisible(false);
    }

    public function onCloseEdit($sender) {
        $this->listpan->setVisible(true);
        $this->editpan->setVisible(false);

    }
    public function OnAutoItem($sender) {
        $store_id = $this->_doc->headerdata['store'];
        $text = trim($sender->getText());
        return Item::findArrayAC($text, $store_id);
    }

    public function onChangeSer($sender) {
        $id = $sender->getValue();
        $ser = Service::load($id) ;
        $price = $ser->getPrice($this->_doc->customer_id);
        $this->editpan->sform->sprice->setText($price) ;


    }
    public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $item = Item::load($id);
        $store_id = $this->_doc->headerdata['store'];

        $customer_id = $this->_doc->customer_id  ;
        $price = $item->getPriceEx(array(
           'store'=>$store_id,
           'customer'=>$customer_id
        ));

        $this->editpan->iform->iprice->setText($price) ;

    }

    public function saveSer($sender) {
        $id = intval($this->editpan->sform->sser->getValue());
        $desc  =  $this->editpan->sform->sdesc->getText();
        $qty  = floatval($this->editpan->sform->sqty->getText());
        $price  = floatval($this->editpan->sform->sprice->getText());
        if($id ==0 || $qty==0 || $price==0) {
            $this->setError('Невiрнi данi')  ;
            return;
        }
        $ser = Service::load($id) ;
        $ser->quantity = $qty;
        $ser->desc = $desc;
        $ser->pureprice = $ser->getPurePrice();
        $ser->price = $price;
        if($ser->pureprice > $ser->price) {
            $ser->disc = number_format((1 - ($ser->price/($ser->pureprice)))*100, 1, '.', '') ;
        }

        $this->_serlist[]=$ser;
        $this->editpan->sform->sser->setValue(0) ;
        $this->editpan->sform->sdesc->setText('') ;
        $this->editpan->sform->sqty->setText('1') ;
        $this->editpan->sform->sprice->setText('') ;

        $this->ecalc()  ;

    }

    public function saveItem($sender) {
        $id = intval($this->editpan->iform->iitem->getKey());

        $snumber  = $this->editpan->iform->isn->getText();
        $qty  = floatval($this->editpan->iform->iqty->getText());
        $price  = floatval($this->editpan->iform->iprice->getText());
        if($id ==0 || $qty==0 || $price==0) {
            $this->setError('Невiрнi данi')  ;
            return;
        }
 
        $common = System::getOptions("common");
        
        $item = Item::load($id) ;
        $item->quantity = $qty;
        $item->snumber = $snumber;

        $item->pureprice = $item->getPurePrice();
        $item->price = $price;
        if($item->pureprice > $item->price) {
            $item->disc = number_format((1 - ($item->price/($item->pureprice)))*100, 1, '.', '') ;
        }
         
        if($common['usesnumber'] > 0 && $item->useserial == 1 ) {
            
            if (strlen($item->snumber) == 0  ) {

                $this->setError("Потрібен серійний номер");
                return;
            }
            
            $store_id = $this->_doc->headerdata['store'];

            $slist = $item->getSerials($store_id);
            
            if (in_array($snumber, $slist) == false) {

                $this->setError('Невірний серійний номер  ');
                return;
            }  
      
        }        
         
        
        $this->_itemlist[]=$item;
        $this->editpan->iform->iitem->setKey(0) ;
        $this->editpan->iform->iitem->setText('') ;
        $this->editpan->iform->isn->setText('') ;

        $this->editpan->iform->iqty->setText('1') ;
        $this->editpan->iform->iprice->setText('') ;

        $this->ecalc()  ;

    }

    public function oncsv($sender) {
        $list = $this->listpan->doclist->getDataSource()->getItems(-1, -1, 'document_id');

        $header = array();
        $data = array();

        $i = 0;
        foreach ($list as $d) {
            $i++;
            $data['A' . $i] = H::fd($d->document_date);
            $data['B' . $i] = $d->document_number;
            $data['C' . $i] = $d->customer_name;
            $data['D' . $i] = $d->amount;
            $data['E' . $i] = $d->notes;
        }

        H::exportExcel($data, $header, 'serlist.xlsx');
    }

}

/**
 *  Источник  данных  для   списка  документов
 */
class SerListDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();

        $where = "   meta_name  in( 'ServiceAct'  ) ";

        $status = $this->page->listpan->filter->status->getValue();
        if ($status == 0) {
            $where .= " and  state <>   " . Document::STATE_CLOSED;
        }
        if ($status == 1) {
            $where .= " and  state =  " . Document::STATE_NEW;
        }
        if ($status == 2) {
            $where .= " and state = " . Document::STATE_INPROCESS;
        }


        $st = trim($this->page->listpan->filter->searchtext->getText());
        if (strlen($st) > 2) {
            $st = $conn->qstr('%' . $st . '%');

            $where .= " and  (  notes like {$st} or    content like {$st}  )";
        }
        $sn = trim($this->page->listpan->filter->searchnumber->getText());
        if (strlen($sn) > 1) { // игнорируем другие поля
            $sn = $conn->qstr('%' . $sn . '%');
            $where = " meta_name  in( 'ServiceAct'  )  and document_number like  {$sn} ";
        }

        return $where;
    }

    public function getItemCount() {
        return Document::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $docs = Document::find($this->getWhere(), "priority desc,  document_id desc", $count, $start);

        return $docs;
    }

    public function getItem($id) {

    }

}

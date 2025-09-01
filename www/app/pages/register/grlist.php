<?php

namespace App\Pages\Register;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Helper as H;
 
use App\Entity\Customer;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
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
 * журнал  закупок
 */
class GRList extends \App\Pages\Base
{
    private $_doc = null;

    /**
     *
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('GRList')) {
            App::RedirectHome() ;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');

        $this->filter->add(new TextInput('searchnumber'));
        $this->filter->add(new TextInput('searchtext'));
        $this->filter->add(new DropDownChoice('status', array(0 => 'Відкриті',   1 => 'Не сплачені', 2 => 'Всі'), 0));

        $this->filter->add(new DropDownChoice('fstore', \App\Entity\Store::getList(), 0));
        $this->filter->add(new AutocompleteTextInput('searchcust'))->onText($this, 'OnAutoCustomer');

        $doclist = $this->add(new DataView('doclist', new GoodsReceiptDataSource($this), $this, 'doclistOnRow'));

        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(H::getPG());

        $this->add(new Panel("statuspan"))->setVisible(false);

        $this->statuspan->add(new Form('statusform'));

        $this->statuspan->statusform->add(new SubmitButton('bttn'))->onClick($this, 'statusOnSubmit');
        $this->statuspan->statusform->add(new SubmitButton('bret'))->onClick($this, 'statusOnSubmit');


        $this->statuspan->add(new \App\Widgets\DocView('docview'));

        $this->filterOnSubmit(null);
        $this->add(new ClickLink('csv', $this, 'oncsv'));
    }

    public function filterOnSubmit($sender) {


        $this->statuspan->setVisible(false);

        $this->doclist->Reload();
    }

    public function doclistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $doc = $row->getDataItem();

        $row->add(new Label('number', $doc->document_number));

        $row->add(new Label('date', H::fd($doc->document_date)));
        $row->add(new Label('onotes', $doc->notes));
        $row->add(new Label('amount', H::fa(($doc->payamount > 0) ? $doc->payamount : ($doc->amount > 0 ? $doc->amount : ""))));

        $row->add(new Label('customer', $doc->customer_name));
        $row->add(new Label('ispay'))->setVisible(false) ;
        $row->add(new Label('istruck'))->setVisible(false) ;

        if($doc->state >=4) {
            if($doc->payamount > 0 &&  $doc->payamount > $doc->payed) {
                $row->ispay->setVisible(true);
            }
            if($doc->meta_name=='InvoiceCust') {
                $n = $doc->getChildren('GoodsReceipt');
                $row->istruck->setVisible(count($n)==0);

            }
            if($doc->meta_name=='GoodsReceipt') {
                if($doc->payamount == ($doc->headerdata['prepaid']??0) )  {
                   $row->ispay->setVisible(false);    
                }
            }            
            if($doc->state==9) {
                $row->ispay->setVisible(false);    
            }            
        }


        $row->add(new Label('state', Document::getStateName($doc->state)));

        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        if ($doc->state < Document::STATE_EXECUTED) {
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

        if ($sender->id == "bttn") {
            $d = $this->_doc->getChildren('GoodsReceipt');

            if (count($d) > 0) {
                $this->setWarn('Вже існує документ Прибуткова накладна');
            }
            App::Redirect("\\App\\Pages\\Doc\\GoodsReceipt", 0, $this->_doc->document_id);
            return;
        }
        if ($sender->id == "bret") {
            $d = $this->_doc->getChildren('RetCustIssue');

            if (count($d) > 0) {

                $this->setWarn('Вже існує документ Повернення');
            }
            App::Redirect("\\App\\Pages\\Doc\\RetCustIssue", 0, $this->_doc->document_id);
            return;
        }
             
        $this->doclist->Reload(false);

        $this->statuspan->setVisible(false);


        $this->updateStatusButtons();
    }

    public function updateStatusButtons() {

        $this->statuspan->statusform->bttn->setVisible($this->_doc->meta_name == 'InvoiceCust');
        $this->statuspan->statusform->bret->setVisible($this->_doc->meta_name == 'GoodsReceipt');


        //новый
        if ($this->_doc->state < Document::STATE_EXECUTED) {
            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->bret->setVisible(false);
        }
        if ($this->_doc->meta_name == 'RetCustIssue') {
            $this->statuspan->statusform->bttn->setVisible(false);
            $this->statuspan->statusform->bret->setVisible(false);
        }
    }

    //просмотр
    public function showOnClick($sender) {

        $this->_doc = $sender->owner->getDataItem();
        if (false == \App\ACL::checkShowDoc($this->_doc, true)) {
            return;
        }

        $this->statuspan->setVisible(true);
        $this->statuspan->docview->setDoc($this->_doc);

        $this->doclist->Reload(false);
        $this->updateStatusButtons();
        $this->goAnkor('dankor');
    }

    public function editOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
        if (false == \App\ACL::checkEditDoc($doc, true)) {
            return;
        }

        App::Redirect("\\App\\Pages\\Doc\\" . $doc->meta_name, $doc->document_id);
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
            $data['E' . $i] = $d->notes;
        }

        H::exportExcel($data, $header, 'baylist.xlsx');
    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 2, true);
    }
    
}

/**
 *  Источник  данных  для   списка  документов
 */
class GoodsReceiptDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();

        $common = System::getOptions("common");
        $actualdate = $common['actualdate'] ??  strtotime('2023-01-01') ;
        
        $conn = \ZDB\DB::getConnect();

        $actualdate =   $conn->DBDate($actualdate );
        
  
        $where = "   meta_name  in('GoodsReceipt','InvoiceCust',  'RetCustIssue','PayComitent' )   and document_date >= ".$actualdate;

        $status = $this->page->filter->status->getValue();

        if ($status == 0) {
            $where .= "  and    state >3 and  state  not in(14,5,9 )        ";
        }
      
        if ($status == 1) {
            $where .= " and state=". Document::STATE_WP;
        }
        if ($status == 2) {

        }

      
        $cust = $this->page->filter->searchcust->getKey();
        if ($cust > 0) {
            $where = $where . " and customer_id = " . $cust;
        }

        $store_id = $this->page->filter->fstore->getValue();
        if ($store_id > 0) {
            $where .= " and   content like '%<store>{$store_id}</store>%' ";
        }

        $st = trim($this->page->filter->searchtext->getText());
        if (strlen($st) > 2) {
            $st = $conn->qstr('%' . $st . '%');

            $where .= " and  (  notes like {$st} or    content like {$st}  )";
        }
        $sn = trim($this->page->filter->searchnumber->getText());
        if (strlen($sn) > 1) { // игнорируем другие поля
            $sn = $conn->qstr('%' . $sn . '%');
            $where = " meta_name  in('GoodsReceipt','InvoiceCust',  'RetCustIssue' )  and document_number like  {$sn} ";
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

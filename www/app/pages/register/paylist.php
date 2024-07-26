<?php

namespace App\Pages\Register;

use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Pay;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\BookmarkableLink;
use App\Application as App;

/**
 * журнал платежей
 */
class PayList extends \App\Pages\Base
{
    private $_doc = null;



    /**
     *
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('PayList')) {
            App::RedirectHome() ;
        }


        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('fmfund', \App\Entity\MoneyFund::getList(), 0));
        $this->filter->add(new DropDownChoice('fuser', \App\Entity\User::findArray('username', 'disabled<>1', 'username'), 0));
        $this->filter->add(new Date('from', time()));
        $this->filter->add(new Date('to', time()));

        $this->filter->add(new AutocompleteTextInput('fcustomer'))->onText($this, 'OnAutoCustomer');

        $doclist = $this->add(new DataView('doclist', new PayListDataSource($this), $this, 'doclistOnRow'));

        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(H::getPG());

        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);
        $this->add(new Form('fnotes'))->onSubmit($this, 'delOnClick');
        $this->fnotes->add(new TextInput('pl_id'));
        $this->fnotes->add(new TextInput('notes'));
        $this->add(new Label('totp'));
        $this->add(new Label('totm'));
        $this->add(new Label('tottot'));

        //   $this->doclist->Reload();
        $this->add(new ClickLink('csv', $this, 'oncsv'));

        $this->filterOnSubmit(null);
    }

    public function filterOnSubmit($sender) {

        $this->docview->setVisible(false);
        $this->doclist->Reload();

        $totp = 0;
        $totm = 0;
        foreach($this->doclist->getDataSource()->getItems() as $doc) {
            if(doubleval($doc->amount) >0) {
                $totp += $doc->amount;
            }
            if(doubleval($doc->amount) <0) {
                $totm += (0 - $doc->amount);
            }
        }



        $this->totp->setText(H::fa($totp)) ;
        $this->totm->setText(H::fa($totm)) ;
        $this->tottot->setText(H::fa($totp - $totm)) ;



    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText());
    }

    public function doclistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $doc = $row->getDataItem();

        $row->add(new Label('number', $doc->document_number));
        $row->add(new Label('docdate', H::fd(strtotime($doc->document_date))));

        $row->add(new Label('date', H::fd($doc->paydate)));
        $row->add(new Label('notes', $doc->notes));
        $row->add(new Label('amountp', H::fa($doc->amount > 0 ? $doc->amount : "")));
        $row->add(new Label('amountm', H::fa($doc->amount < 0 ? 0 - $doc->amount : "")));

        $row->add(new Label('mf_name', $doc->mf_name));
        $row->add(new Label('username', $doc->username));
        $row->add(new Label('customer_name', $doc->customer_name));


        $row->add(new ClickLink('show', $this, 'showOnClick'));
        $user = \App\System::getUser();
        $row->add(new BookmarkableLink('del'))->setVisible($user->rolename == 'admins');
        $row->del->setAttribute('onclick', "delpay({$doc->pl_id})");

        $row->add(new ClickLink('print'))->onClick($this, 'printOnClick', true);


    }

    //просмотр
    public function showOnClick($sender) {


        $this->_doc = Document::load($sender->owner->getDataItem()->document_id);

        if (false == \App\ACL::checkShowDoc($this->_doc, true)) {
            return;
        }

        $this->docview->setVisible(true);
        $this->docview->setDoc($this->_doc);
    }

    public function delOnClick($sender) {


        $id = $sender->pl_id->getText();

        $pl = Pay::load($id);

        $common = \App\System::getOptions('common') ;
        $da = $common['actualdate'] ?? 0 ;

        if($da>$pl->paydate) {
            $this->setError("Не можна відміняти оплату раніше  " .date('Y-m-d', $da));
            return;
        }

        $doc = Document::load($pl->document_id);

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();

        try {

            Pay::cancelPayment($id, $sender->notes->getText());


            $sql = "select coalesce(abs(sum(amount)),0) from paylist_view where document_id=" . $pl->document_id;
            $payed = $conn->GetOne($sql);

            $conn->Execute("update documents set payed={$payed} where   document_id =" . $pl->document_id);
       
            $doc = \App\Entity\Doc\Document::load($pl->document_id)->cast();
            $doc->DoBalans();
      
            $conn->CommitTrans();


        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();

            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $doc->meta_desc);
            return;
        }


        $this->doclist->Reload(true);

        $user = \App\System::getUser();


        \App\Entity\Notify::toSystemLog("Користувач {$user->username} видалив платіж з документа {$doc->document_number}. Підстава: " . $sender->notes->getText()) ;

        $sender->notes->setText('');
        $this->setSuccess('Платіж скасовано');
        $this->resetURL();
    }

    public function oncsv($sender) {
        $list = $this->doclist->getDataSource()->getItems(-1, -1);

        $header = array();
        $data = array();

        $header['A1'] = "Дата";
        $header['B1'] = "Счет";
        $header['C1'] = "Приход";
        $header['D1'] = "Расход";
        $header['E1'] = "Документ";
        $header['F1'] = "Создал";
        $header['G1'] = "Контрагент";
        $header['H1'] = "Примечание";

        $i = 1;
        foreach ($list as $doc) {
            $i++;
            $data['A' . $i] = H::fd($doc->paydate);
            $data['B' . $i] = $doc->mf_name;
            $data['C' . $i] = ($doc->amount > 0 ? H::fa($doc->amount) : "");
            $data['D' . $i] = ($doc->amount < 0 ? H::fa(0 - $doc->amount) : "");
            $data['E' . $i] = $doc->document_number;
            $data['F' . $i] = $doc->username;
            $data['G' . $i] = $doc->customer_name;
            $data['H' . $i] = $doc->notes;
        }

        H::exportExcel($data, $header, 'paylist.xlsx');
    }

    public function printOnClick($sender) {
        $pay = $sender->getOwner()->getDataItem();
        $doc = \App\Entity\Doc\Document::load($pay->document_id);

        $header = array();
        $header['document_number'] = $doc->document_number;
        $header['firm_name'] = $doc->firm_name;
        $header['customer_name'] = $doc->customer_name;
        $list = Pay::find("document_id=" . $pay->document_id, "pl_id");
        $all = 0;
        $header['plist'] = array();
        foreach ($list as $p) {
            $header['plist'][] = array('ppay' => H::fa(abs($p->amount)), 'pdate' => H::fd($p->paydate));
            $all += abs($p->amount);
        }
        $header['pall'] = H::fa($all);
        if(intval(\App\System::getUser()->prtype) == 0) {

            $report = new \App\Report('pays_bill.tpl');

            $html = $report->generate($header);

            $this->addAjaxResponse("  $('#paysprint').html('{$html}') ; $('#pform').modal()") ;
            return;
        }

        try {
            $report = new \App\Report('pays_bill_ps.tpl');

            $xml = $report->generate($header);
            $buf = \App\Printer::xml2comm($xml);
            $b = json_encode($buf) ;

            $this->addAjaxResponse("$('.seldel').prop('checked',null); sendPS('{$b}') ");
        } catch(\Exception $e) {
            $message = $e->getMessage()  ;
            $message = str_replace(";", "`", $message)  ;
            $this->addAjaxResponse(" toastr.error( '{$message}' )         ");

        }


    }

}

/**
 *  Источник  данных  для   списка  документов
 */
class PayListDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();

        //$where = "   d.customer_id in(select  customer_id from  customers  where  status=0)";
        $where = "p.paytype<>1001 and  date(paydate) >= " . $conn->DBDate($this->page->filter->from->getDate()) . " and  date(paydate) <= " . $conn->DBDate($this->page->filter->to->getDate());

        //        $where = " paydate>=  ". $conn->DBDate(strtotime("-400 day") );

        $author = $this->page->filter->fuser->getValue();

        $cust = $this->page->filter->fcustomer->getKey();
        $mf = $this->page->filter->fmfund->getValue();


        if ($cust > 0) {
            $where .= " and d.customer_id=" . $cust;
        }
        if ($mf > 0) {

            $where .= " and p.mf_id=" . $mf;
        }
        if ($author > 0) {
            $where .= " and p.user_id=" . $author;
        }

        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $where .= " and " . $c;
        }

        if ($user->rolename != 'admins') {
            if ($user->onlymy == 1) {

                $where .= " and d.user_id  = " . $user->user_id;
            }

            $where .= " and d.meta_id in({$user->aclview}) ";
        }
        return $where;
    }

    public function getItemCount() {
        $conn = \ZDB\DB::getConnect();
        $sql = "select coalesce(count(*),0) from documents_view  d join paylist_view p on d.document_id = p.document_id where " . $this->getWhere();
        return $conn->GetOne($sql);
    }

    public function getItems($start=-1, $count=-1, $sortfield = null, $asc = null) {

        $conn = \ZDB\DB::getConnect();
        $sql = "select  p.*,d.customer_name,d.meta_id,d.document_date  from documents_view  d join paylist_view p on d.document_id = p.document_id where " . $this->getWhere() . " order  by  pl_id desc   ";
        if ($count > 0) {
            $limit =" limit {$start},{$count}";
        


            $sql .= $limit;
        }

        $docs = \App\Entity\Pay::findBySql($sql);

        return $docs;
    }

    public function getItem($id) {

    }

}

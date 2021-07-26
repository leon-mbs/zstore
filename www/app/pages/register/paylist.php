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
            return;
        }


        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('fmfund', \App\Entity\MoneyFund::getList(), 0));
        $this->filter->add(new DropDownChoice('fuser', \App\Entity\User::findArray('username', '', 'username'), 0));

        $this->filter->add(new AutocompleteTextInput('fcustomer'))->onText($this, 'OnAutoCustomer');

        $doclist = $this->add(new DataView('doclist', new PayListDataSource($this), $this, 'doclistOnRow'));

        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(H::getPG());

        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);
        $this->add(new Form('fnotes'))->onSubmit($this, 'delOnClick');
        $this->fnotes->add(new TextInput('pl_id'));
        $this->fnotes->add(new TextInput('notes'));

        $this->doclist->Reload();
        $this->add(new ClickLink('csv', $this, 'oncsv'));


    }

    public function filterOnSubmit($sender) {


        $this->docview->setVisible(false);
        $this->doclist->Reload();
    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText());
    }

    public function doclistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $doc = $row->getDataItem();

        $row->add(new Label('number', $doc->document_number));

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

        $doc = Document::load($pl->document_id);
        Pay::cancelPayment($id, $sender->notes->getText());

        $conn = \ZDB\DB::getConnect();

        $sql = "select coalesce(abs(sum(amount)),0) from paylist where document_id=" . $pl->document_id;
        $payed = $conn->GetOne($sql);

        $conn->Execute("update documents set payed={$payed} where   document_id =" . $pl->document_id);

        $this->doclist->Reload(true);

        $user = \App\System::getUser();


        $n = new \App\Entity\Notify();
        $n->user_id = \App\Entity\Notify::SYSTEM;
        $n->dateshow = time();

        $n->message = H::l('deletedpay', $user->username, $doc->document_number, $sender->notes->getText());
        $n->save();

        $sender->notes->setText('');
        $this->setSuccess('payment_canceled');
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

        $report = new \App\Report('pays_bill.tpl');

        $html = $report->generate($header);
        $this->updateAjax(array(), "  $('#paysprint').html('{$html}') ; $('#pform').modal()");
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
        $where = "  1=1 ";

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
        $sql = "select coalesce(count(*),0) from documents_view  d join `paylist_view` p on d.`document_id` = p.`document_id` where " . $this->getWhere();
        return $conn->GetOne($sql);
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        $conn = \ZDB\DB::getConnect();
        $sql = "select  p.*,d.`customer_name`,d.`meta_id`  from documents_view  d join `paylist_view` p on d.`document_id` = p.`document_id` where " . $this->getWhere() . " order  by  pl_id desc   ";
        if ($count > 0) {
            $sql .= " limit {$start},{$count}";
        }

        $docs = \App\Entity\Pay::findBySql($sql);

        return $docs;
    }

    public function getItem($id) {

    }

}

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
 * журнал доходы  и расходы
 */
class IOState extends \App\Pages\Base
{

    private $_doc    = null;
    private $_ptlist = null;

    /**
     *
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('IOState')) {
            return;
        }

        $this->_ptlist = \App\Entity\IOState::getTypeList();

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('fuser', \App\Entity\User::findArray('username', 'disabled<>1', 'username'), 0));
        $this->filter->add(new DropDownChoice('ftype', $this->_ptlist, 0));

        $doclist = $this->add(new DataView('doclist', new IOStateListDataSource($this), $this, 'doclistOnRow'));

        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(H::getPG());

        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);

        $this->doclist->Reload();
        $this->add(new ClickLink('csv', $this, 'oncsv'))->setVisible(false);

        $this->_ptlist[0] = '';
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

        $row->add(new Label('username', $doc->username));

        $row->add(new Label('paytype', $this->_ptlist[$doc->paytype]));

        $row->add(new ClickLink('show', $this, 'showOnClick'));


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
    /*
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
    */
}

/**
 *  Источник  данных  для   списка  документов
 */
class IOStateListDataSource implements \Zippy\Interfaces\DataSource
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
        $type = $this->page->filter->ftype->getValue();

        if ($type > 0) {
            $where .= " and paytype=" . $type;
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

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        $conn = \ZDB\DB::getConnect();
        $sql = "select  p.*,d.customer_name,d.meta_id  from documents_view  d join paylist_view p on d.document_id = p.document_id where " . $this->getWhere() . " order  by  pl_id desc   ";
        if ($count > 0) {
            $limit =" limit {$start},{$count}";
            if($conn->dataProvider=="postgres") {
                $limit =" limit {$count} offset {$start}";
            }
                  
           
            $sql .= $limit;
        }

        $docs = \App\Entity\Pay::findBySql($sql);

        return $docs;
    }

    public function getItem($id) {

    }

}

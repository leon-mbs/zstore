<?php

namespace App\Pages\Register;

use App\Entity\Account;
use App\Entity\Doc\Document;
use App\Entity\AccEntry;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Pager;
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
 * журнал проводок
 */
class AccountEntryList extends \App\Pages\Base
{
    private $_doc    = null;
    private $_ptlist = null;

    /**
     *
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('AccountEntryList')) {
            App::RedirectHome() ;
        }

      
        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        
        $list = Account::getList(true);

            
        $this->filter->add(new DropDownChoice('facc', $list, 0));
        $this->filter->add(new DropDownChoice('ftype', [], 0));
        $this->filter->add(new Date('from',strtotime('-1 month')));
        $this->filter->add(new Date('to'));
  
        $entrylist = $this->add(new DataView('entrylist', new AccEntryListDataSource($this), $this, 'entrylistOnRow'));

        $this->add(new Pager('pag', $entrylist));
        $entrylist->setPageSize(H::getPG());

        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);

        $this->add(new ClickLink('csv', $this, 'oncsv')) ;

        $entrylist->Reload();
         
    }

    public function filterOnSubmit($sender) {
        $this->docview->setVisible(false);

        $this->entrylist->Reload();
    }

  

    public function entrylistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $entry = $row->getDataItem();

        $row->add(new Label('number', $entry->document_number));
        $row->add(new Label('date', H::fd($entry->createdon)));
        $row->add(new Label('amount', H::fa($entry->amount)));
        $row->add(new Label('notes', $entry->notes));
        $row->add(new Label('dt', $entry->accdt==0 ? '':$entry->accdt));
        $row->add(new Label('ct', $entry->accct==0 ? '':$entry->accct));
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
        $list = $this->entrylist->getDataSource()->getItems(-1, -1);

        $header = array();
        $data = array();

        $header['A1'] = "Дата";
        $header['B1'] = "Дебет";
        $header['C1'] = "Кредит";
        $header['D1'] = "Сума";
        $header['E1'] = "Документ";
        $header['F1'] = "";
      

        $i = 1;
        foreach ($list as $doc) {
            $i++;
            $data['A' . $i] = H::fd($doc->createdon);
            $data['B' . $i] = $doc->accdt;
            $data['C' . $i] = $doc->accct ;
            $data['D' . $i] = H::fa($doc->amount);
            $data['E' . $i] = $doc->document_number;
            $data['F' . $i] = $doc->notes;

        }

        H::exportExcel($data, $header, 'accentrylist.xlsx');
    }

}

/**
 *  Источник  данных  для   списка  документов
 */
class AccEntryListDataSource implements \Zippy\Interfaces\DataSource
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
        $from = $this->page->filter->from->getDate();
        $to = $this->page->filter->to->getDate();

        if ($from > 0) {
            $where .= " and  createdon >= " . $conn->DBDate($from);
        }
        if ($to > 0) {
            $where .= " and  createdon <= " . $conn->DBDate($to);
        }

        $acc = $this->page->filter->facc->getValue();
        $type = $this->page->filter->ftype->getValue();
   
        if(strlen($acc)>1) {
           if($type==0) {
                $where .= " and (accdt='{$acc}' or  accct='{$acc}' ) ";
           }
           if($type==1) {
                $where .= " and accdt='{$acc}'  ";
           }
           if($type==2) {
                $where .= " and accct='{$acc}'  ";
           }
        }
     

        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $where .= " and d." . $c;
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
        $sql = "select coalesce(count(*),0) from documents_view  d join acc_entry_view e on d.document_id = e.document_id where " . $this->getWhere();
        return $conn->GetOne($sql);
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        $conn = \ZDB\DB::getConnect();
        $sql = "select  e.*,d.user_id,d.meta_id,d.branch_id,d.notes  from documents_view  d join acc_entry_view e on d.document_id = e.document_id where " . $this->getWhere() . " order  by e.createdon    ";
        if ($count > 0) {
            $limit =" limit {$start},{$count}";
         

            $sql .= $limit;
        }

        $docs = \App\Entity\AccEntry::findBySql($sql);

        return $docs;
    }

    public function getItem($id) {

    }

}

<?php

namespace App\Pages\Register;

use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Panel;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use App\Entity\Doc\Document;
use App\Entity\Customer;
use \Zippy\Html\Form\AutocompleteTextInput;
use App\Filter;
use App\Helper as H;
use App\Application as App;
use App\System;

/**
 * журнал  докуметов
 */
class DocList extends \App\Pages\Base
{

    /**
     *
     * @param mixed $docid Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct($docid = 0) {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('DocList'))
            return;


        $filter = Filter::getFilter("doclist");
        if ($filter->to == null) {
            $filter->to = time() + (3 * 24 * 3600);
            $filter->from = time() - (7 * 24 * 3600);
            $filter->page = 1;
            $filter->doctype = 0;
            $filter->customer = 0;
            $filter->customer_name = '';

            $filter->searchnumber = '';
        }
        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new Date('from', $filter->from));
        $this->filter->add(new Date('to', $filter->to));
        $this->filter->add(new DropDownChoice('doctype', H::getDocTypes(), $filter->doctype));

        $this->filter->add(new ClickLink('erase', $this, "onErase"));
        $this->filter->add(new AutocompleteTextInput('searchcust'))->onText($this, 'OnAutoCustomer');
        $this->filter->searchcust->setKey($filter->customer);
        $this->filter->searchcust->setText($filter->customer_name);
        $this->filter->add(new TextInput('searchnumber', $filter->searchnumber));

        if (strlen($filter->docgroup) > 0)
            $this->filter->docgroup->setValue($filter->docgroup);

        $doclist = $this->add(new DataView('doclist', new DocDataSource(), $this, 'doclistOnRow'));
        $doclist->setSelectedClass('table-success');

        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(25);
        $filter->page = $this->doclist->setCurrentPage($filter->page);
        $doclist->Reload();
        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);
        if ($docid > 0) {
            $this->docview->setVisible(true);
            $this->docview->setDoc(Document::load($docid));
            //$this->doclist->setSelectedRow($docid);
            $doclist->Reload();
        }
        
        $this->add(new ClickLink('csv', $this,'oncsv'));        
        
    }

    public function onErase($sender) {
        $filter = Filter::getFilter("doclist");
        $filter->to = time();
        $filter->from = time() - (7 * 24 * 3600);
        $filter->page = 1;
        $filter->doctype = 0;
        $filter->customer = 0;
        $filter->customer_name = '';

        $filter->searchnumber = '';

        $this->filter->clean();
        $this->filter->to->setDate(time());
        ;
        $this->filter->from->setDate(time() - (7 * 24 * 3600));
        ;
        $this->filterOnSubmit($this->filter);
        ;
    }

    public function filterOnSubmit($sender) {

        $this->docview->setVisible(false);
        //запоминаем  форму   фильтра
        $filter = Filter::getFilter("doclist");
        $filter->from = $this->filter->from->getDate();
        $filter->to = $this->filter->to->getDate(true);
        $filter->doctype = $this->filter->doctype->getValue();
        $filter->customer = $this->filter->searchcust->getKey();
        $filter->customer_name = $this->filter->searchcust->getText();


        $filter->searchnumber = trim($this->filter->searchnumber->getText());

        $this->doclist->setCurrentPage(1);
        //$this->doclist->setPageSize($this->filter->rowscnt->getValue());

        $this->doclist->Reload();  
    }

    public function doclistOnRow($row) {
        $doc = $row->getDataItem();
        $doc = $doc->cast();
        $row->add(new Label('name', $doc->meta_desc));
        $row->add(new Label('number', $doc->document_number));
        $row->add(new Label('notes', $doc->notes));
        $row->add(new Label('cust', $doc->customer_name));
        $row->add(new Label('date', date('d-m-Y', $doc->document_date)));
        $row->add(new Label('amount', ($doc->amount > 0) ? $doc->amount : ""));

        $row->add(new Label('state', Document::getStateName($doc->state)));

        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('cancel'))->onClick($this, 'cancelOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');

        if ($doc->state == Document::STATE_CANCELED || $doc->state == Document::STATE_EDITED || $doc->state == Document::STATE_NEW|| $doc->state == Document::STATE_REFUSED) {
            $row->edit->setVisible(true);
            $row->delete->setVisible(true);
            $row->cancel->setVisible(false);
        } else {
            $row->edit->setVisible(false);
            $row->delete->setVisible(false);
            $row->cancel->setVisible(true);
        }

        //список документов   которые   могут  быть созданы  на  основании  текущего
        $basedon = $row->add(new Label('basedon'));
        $basedonlist = $doc->getRelationBased();
        if (count($basedonlist) == 0) {
            $basedon->setVisible(false);
        } else {
            $list = "";
            foreach ($basedonlist as $doctype => $docname) {
                $list .= "<a  class=\"dropdown-item\" href=\"/?p=App/Pages/Doc/" . $doctype . "&arg=/0/{$doc->document_id}\">{$docname}</a>";
            };
            $basedon = $row->add(new Label('basedlist'))->setText($list, true);
        }
    }

    //просмотр
    
    public function showOnClick($sender) {
        $item = $sender->owner->getDataItem();
        if (false == \App\ACL::checkShowDoc($item, true))
            return;
        $this->docview->setVisible(true);
        $this->docview->setDoc($item);
        $this->doclist->setSelectedRow($sender->getOwner());
        $this->doclist->Reload(false);
        $this->goAnkor('dankor');
    }

    //редактирование
    public function editOnClick($sender) {
        $item = $sender->owner->getDataItem();
        if (false == \App\ACL::checkEditDoc($item, true))
            return;
        $type = H::getMetaType($item->meta_id);
        $class = "\\App\\Pages\\Doc\\" . $type['meta_name'];
        //   $item = $class::load($item->document_id);
        //запоминаем страницу пагинатора
        $filter = Filter::getFilter("doclist");
        $filter->page = $this->doclist->getCurrentPage();

        App::Redirect($class, $item->document_id);
    }

    public function deleteOnClick($sender) {
        $this->docview->setVisible(false);

        $doc = $sender->owner->getDataItem();
        if (false == \App\ACL::checkEditDoc($doc, true))
            return;

        $doc = $doc->cast();
        if ($doc->canDeleted() == false) {

            return;
        }
        Document::delete($doc->document_id);
        $this->doclist->Reload(true);
        $this->resetURL();
    }

    public function cancelOnClick($sender) {
        $this->docview->setVisible(false);

        $doc = $sender->owner->getDataItem();
        if (false == \App\ACL::checkEditDoc($doc, true))
            return;

        if (false == $doc->canCanceled()) {
            return;
        }
        $doc->updateStatus(Document::STATE_CANCELED);
        $this->doclist->setSelectedRow($sender->getOwner());
        $this->doclist->Reload(false);
        $this->resetURL();
    }

    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "Customer_name like " . $text);
    }

    
    public function oncsv($sender) {
            $list = $this->doclist->getDataSource()->getItems(-1,-1,'document_id');
            $csv="";
 
            foreach($list as $d){
               $csv.=  date('Y.m.d',$d->document_date) .';';    
               $csv.=  $d->document_number .';';    
               $csv.=  $d->meta_desc .';';    
               $csv.=  $d->customer_name .';';    
               $csv.=  $d->amount  .';'; 
               $csv.=  $d->notes .';';     
               $csv.="\n";
            }
            $csv = mb_convert_encoding($csv, "windows-1251", "utf-8");

 
            header("Content-type: text/csv");
            header("Content-Disposition: attachment;Filename=doclist.csv");
            header("Content-Transfer-Encoding: binary");

            echo $csv;
            flush();
            die;
            
    }

}

/**
 *  Источник  данных  для   списка  документов
 */
class DocDataSource implements \Zippy\Interfaces\DataSource
{

    private function getWhere() {
        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();
        $filter = Filter::getFilter("doclist");
        $where = " date(document_date) >= " . $conn->DBDate($filter->from) . " and  date(document_date) <= " . $conn->DBDate($filter->to);

        if ($filter->doctype > 0) {
            $where .= " and meta_id  ={$filter->doctype} ";
        }
        if ($filter->customer > 0) {
            $where .= " and customer_id  ={$filter->customer} ";
        }



        if (strlen($filter->searchnumber) > 1) {
            // игнорируем другие поля
            $sn = $conn->qstr('%' . $sn . '%');
            $where = "    document_number like  {$sn} ";
        }
        if ($user->acltype == 2) {


            $where .= " and meta_id in({$user->aclview}) ";
        }


        return $where;
    }

    public function getItemCount() {
        return Document::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $docs = Document::find($this->getWhere(), "document_date desc,document_id desc", $count, $start);

        //$l = Traversable::from($docs);
        //$l = $l->where(function ($doc) {return $doc->document_id == 169; }) ;
        //$l = $l->select(function ($doc) { return $doc; })->asArray() ;
        return $docs;
    }

    public function getItem($id) {
        
    }

}

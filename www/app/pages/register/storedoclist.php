<?php

namespace App\Pages\Register;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\DataList\Paginator;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Binding\PropertyBinding as Prop;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Panel;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \App\Entity\Doc\Document;
use \App\Entity\Store;
use \App\Helper as H;
use \App\Application as App;
use \App\System;

/**
 * журнал   складских документов
 */
class StoreDocList extends \App\Pages\Base {

    private $_doc = null;

    /**
     *
     * @param mixed $docid Документ  должен  быть  показан  в  просмотре
     * @return DocList
     */
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('StoreDocList'))
            return;

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time() + (1 * 24 * 3600)));
        $this->filter->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));




        $doclist = $this->add(new DataView('doclist', new StorerDocDataSource($this), $this, 'doclistOnRow'));
        $doclist->setSelectedClass('table-success');

        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(H::getPG());


        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);



        $this->doclist->Reload();
        $this->add(new ClickLink('csv', $this, 'oncsv'));
    }

    public function filterOnSubmit($sender) {


        $this->docview->setVisible(false);
        $this->doclist->Reload();
    }

    public function doclistOnRow($row) {
        $doc = $row->getDataItem();

        $row->add(new Label('number', $doc->document_number));

        $row->add(new Label('date', date('d-m-Y', $doc->document_date)));
        $row->add(new Label('onotes', $doc->notes));
        $row->add(new Label('amount', H::fa($doc->amount)));

   

        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
    }

    //просмотр
    public function showOnClick($sender) {

        $this->_doc = $sender->owner->getDataItem();
        if (false == \App\ACL::checkShowDoc($this->_doc, true))
            return;
        $this->doclist->setSelectedRow($sender->getOwner());
        $this->doclist->Reload(false);
        $this->docview->setVisible(true);
        $this->docview->setDoc($this->_doc);
    }

    public function editOnClick($sender) {
        $doc = $sender->getOwner()->getDataItem();
        if (false == \App\ACL::checkEditDoc($doc, true))
            return;


        App::Redirect("\\App\\Pages\\Doc\\" . $doc->meta_name, $doc->document_id);
    }

    public function oncsv($sender) {
        $list = $this->doclist->getDataSource()->getItems(-1, -1, 'document_id');
        $csv = "";

        foreach ($list as $d) {
            $csv .= date('Y.m.d', $d->document_date) . ';';
            $csv .= $d->document_number . ';';
         
            $csv .= $d->amount . ';';
            $csv .= str_replace(';', '', $d->notes) . ';';
            $csv .= "\n";
        }
        $csv = mb_convert_encoding($csv, "windows-1251", "utf-8");


        header("Content-type: text/csv");
        header("Content-Disposition: attachment;Filename=baylist.csv");
        header("Content-Transfer-Encoding: binary");

        echo $csv;
        flush();
        die;
    }

}

/**
 *  Источник  данных  для   списка  документов
 */
class StorerDocDataSource implements \Zippy\Interfaces\DataSource {

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $user = System::getUser();

        $conn = \ZDB\DB::getConnect();

        $where = " date(document_date) >= " . $conn->DBDate($this->page->filter->from->getDate()) . " and  date(document_date) <= " . $conn->DBDate($this->page->filter->to->getDate());

        $where .= " and meta_name  in ( 'TransItem','OutcomeItem','IncomeItem','Inventory','MoveItem')  ";




        $store = $this->page->filter->store->getValue();
        if ($store > 0) {
            $where .= " and (content like '%<store>{$store}</store>%' or content like '%<storefrom>{$store}</storefrom>%' or content like '%<storeto>{$store}</storeto>%'  ) ";
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

<?php

namespace App\Pages\Register;

use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use App\Entity\Doc\Document;
use App\Filter;
use App\Helper as H;
use App\Application as App;

/**
 * Реестр налоговых накладных
 */
class TaxInvoiceList extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
    
        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new Date('from', time() - (15 * 24 * 3600)));
        $this->filter->add(new Date('to', 0));
        $this->filter->add(new CheckBox('notchecked'))->setChecked(  true);
           
        $doclist = $this->add(new DataView('doclist', new TaxListDataSource($this), $this, 'doclistOnRow'));
        $this->add(new Paginator('pag', $doclist));
        $doclist->setPageSize(H::getPG());
    
        $doclist->setSelectedClass('table-success');
        $doclist->Reload();
        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);
   
    }

    public function filterOnSubmit($sender) {
        $this->docview->setVisible(false);
     
        $this->doclist->Reload();
    }

    public function doclistOnRow($row) {
        $item = $row->getDataItem();
        $item = $item->cast();
        $row->add(new Label('name', $item->meta_desc));
        $row->add(new Label('number', $item->document_number));
        $row->add(new Label('date', date('d-m-Y', $item->document_date)));
        $row->add(new Label('amount', ($item->amount > 0) ? H::famt($item->amount) : ""));

        $row->add(new Label('ernn',date('d-m-Y', $item->document_date))) ;
        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
   
        if ($item->state == Document::STATE_CLOSED) {
            $row->edit->setVisible(false);
            $row->cancel->setVisible(true);
        } else {
            $row->edit->setVisible(true);
            $row->cancel->setVisible(false);
        }


       
    }

    //просмотр
    public function showOnClick($sender) {
        $item = $sender->owner->getDataItem();
        $this->docview->setVisible(true);
        $this->docview->setDoc($item);
    }

 

}

/**
 *  Источник  данных  для   списка  документов
 */
class TaxListDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }
    private function getWhere() {

        $conn = \ZDB\DB::getConnect();

        $isch = $this->page->filter->notchecked->isChecked()   ;
        if($isch) {
            $where = "1=1 " ;
        }   else {
            $where = " document_date >= " . $conn->DBDate($this->page->filter->from->getDate()) ;
            $to = $this->page->filter->to->getDate();
            if($to) {
               $where .=  " and  document_date <= " . $conn->DBDate($to);
            }
        }
        
        $where .= " and  (meta_name = 'TaxInvoice' or meta_name = 'TaxInvoiceIncome'  or meta_name = 'TaxInvoice2' )";


      
        return $where;
    }

    public function getItemCount() {
        return Document::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        return Document::find($this->getWhere(), "document_id desc ", $count, $start);
    }

    public function getItem($id) {
        
    }

}

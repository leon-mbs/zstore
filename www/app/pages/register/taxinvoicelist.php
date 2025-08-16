<?php

namespace App\Pages\Register;

use Zippy\Html\DataList\DataView;
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
        $filter = Filter::getFilter("taxinvoicelist");
        if ($filter->to == null) {
            $filter->to = time();
            $filter->from = time() - (7 * 24 * 3600);
        }
        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new Date('from', $filter->from));
        $this->filter->add(new Date('to', $filter->to));
        $this->filter->add(new CheckBox('notchecked'))->setChecked($filter->notchecked == true);
        if (strlen($filter->docgroup) > 0)
            $this->filter->docgroup->setValue($filter->docgroup);
        $doclist = $this->add(new DataView('doclist', new TaxListDataSource(), $this, 'doclistOnRow'));
        $doclist->setSelectedClass('table-success');
        $doclist->Reload();
        $this->add(new \App\Widgets\DocView('docview'))->setVisible(false);
    }

    public function filterOnSubmit($sender) {
        $this->docview->setVisible(false);
        //запоминаем  форму   фильтра
        $filter = Filter::getFilter("taxinvoicelist");
        $filter->from = $this->filter->from->getDate();
        $filter->to = $this->filter->to->getDate(true);
        $filter->notchecked = $this->filter->notchecked->isChecked();

        $this->doclist->Reload();
    }

    public function doclistOnRow($row) {
        $item = $row->getDataItem();
        $item = $item->cast();
        $row->add(new Label('name', $item->meta_desc));
        $row->add(new Label('number', $item->document_number));
        $row->add(new Label('date', date('d-m-Y', $item->document_date)));
        $row->add(new Label('amount', ($item->amount > 0) ? H::famt($item->amount) : ""));

        $row->add(new Label('ernn'))->setVisible($item->headerdata['ernn'] == true);
        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('cancel'))->onClick($this, 'cancelOnClick');

        if ($item->state == Document::STATE_CLOSED) {
            $row->edit->setVisible(false);
            $row->cancel->setVisible(true);
        } else {
            $row->edit->setVisible(true);
            $row->cancel->setVisible(false);
        }


        //закрытый период
        if ($item->updated < strtotime("2013-01-01")) {
            $row->edit->setVisible(false);
            $row->cancel->setVisible(false);
        }
    }

    //просмотр
    public function showOnClick($sender) {
        $item = $sender->owner->getDataItem();
        $this->docview->setVisible(true);
        $this->docview->setDoc($item);
        $this->doclist->setSelectedRow($sender->getOwner());
        $this->doclist->Reload();
    }

    //редактирование
    public function editOnClick($sender) {
        $item = $sender->owner->getDataItem();
        $type = H::getMetaType($item->type_id);
        $class = "\\App\\Pages\\Doc\\" . $type['meta_name'];
        //   $item = $class::load($item->document_id);
        App::Redirect($class, $item->document_id);
    }

    public function cancelOnClick($sender) {
        $this->docview->setVisible(false);

        $item = $sender->owner->getDataItem();
        $item->updateStatus(Document::STATE_CANCELED);
        $this->doclist->Reload();
    }

}

/**
 *  Источник  данных  для   списка  документов
 */
class TaxListDataSource implements \Zippy\Interfaces\DataSource
{

    private function getWhere() {

        $conn = \ZDB\DB::getConnect();
        $filter = Filter::getFilter("taxinvoicelist");
        $where = " document_date >= " . $conn->DBDate($filter->from) . " and  document_date <= " . $conn->DBDate($filter->to);
        $where .= " and  (meta_name = 'TaxInvoice' or meta_name = 'TaxInvoiceIncome' )";


        if ($filter->notchecked == true) {
            // $where .= " and intattr2  <> 1 ";  //не  проверен  в  ЕРНН
        }
        return $where;
    }

    public function getItemCount() {
        return Document::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        return Document::find($this->getWhere(), "document_id  ", $count, $start);
    }

    public function getItem($id) {
        
    }

}

<?php

namespace App\Pages\Register;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \App\Entity\Item;
use \App\Entity\Stock;
use \App\Entity\Category;
use \App\Entity\Store;
use \App\Helper as H;

class StockList extends \App\Pages\Base {

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('StockList'))
            return;

        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->filter->add(new TextInput('searchkey'));
        $this->filter->add(new DropDownChoice('searchcat', Category::findArray("cat_name", "", "cat_name"), 0));
        $this->filter->add(new DropDownChoice('searchstore', Store::getList(), H::getDefStore()));
        $this->filter->add(new DropDownChoice('searchtype', array('1' => 'Ожидаемые', '2' => 'Зарезервированные', '3' => 'Неликвиды')));


        $this->add(new Panel('itemtable'))->setVisible(true);
        $this->itemtable->add(new DataView('itemlist', new ItemDataSource($this), $this, 'itemlistOnRow'));

        $this->itemtable->itemlist->setPageSize(25);
        $this->itemtable->add(new \Zippy\Html\DataList\Paginator('pag', $this->itemtable->itemlist));



        $this->itemtable->itemlist->Reload();
        $this->add(new ClickLink('csv', $this, 'oncsv'));
    }

    public function itemlistOnRow($row) {
        $stock = $row->getDataItem();
        $row->add(new Label('storename', $stock->storename));
        $row->add(new Label('itemname', $stock->itemname));
        $row->add(new Label('code', $stock->item_code));
        $row->add(new Label('msr', $stock->msr));
        $row->add(new Label('partion', $stock->partion));
        $stock->qty = $stock->qty - $stock->wqty + $stock->rqty;
        $q = "<span>" . H::fqty($stock->qty) . "</span>";
        $w = "";
        if ($stock->wqty > 0) {
            $w .= "<span class='text-success'>+" . H::fqty($stock->wqty) . "</span>";
        }
        if ($stock->rqty > 0) {
            $w .= "&nbsp;<span class='text-danger'>-" . H::fqty($stock->rqty) . "</span>";
        }
        if (strlen($w) > 0) {
            $q .= "&nbsp;(" . $w . ")";
        }

        $row->add(new Label('qty', $q, true));
        $row->add(new Label('amount', round($stock->qty * $stock->partion)));

        $item = Item::load($stock->item_id);
        $row->add(new Label('cat_name', $item->cat_name));

        $plist = array();
        if ($item->price1 > 0)
            $plist[] = $item->getPrice('price1', $stock->partion);
        if ($item->price2 > 0)
            $plist[] = $item->getPrice('price2', $stock->partion);
        if ($item->price3 > 0)
            $plist[] = $item->getPrice('price3', $stock->partion);
        if ($item->price4 > 0)
            $plist[] = $item->getPrice('price4', $stock->partion);
        if ($item->price5 > 0)
            $plist[] = $item->getPrice('price5', $stock->partion);

        $row->add(new Label('price', implode(',', $plist)));
    }

    public function OnFilter($sender) {
        $this->itemtable->itemlist->Reload();
    }

    public function oncsv($sender) {
        $list = $this->itemtable->itemlist->getDataSource()->getItems(-1, -1, 'document_id');
        $csv = "";

        foreach ($list as $st) {
            $csv .= $st->storename . ';';
            $csv .= $st->itemname . ';';
            $csv .= $st->item_code . ';';
            $csv .= $st->msr . ';';
            $csv .= $st->partion . ';';
            $csv .= H::fqty($st->qty) . ';';
            $csv .= round($st->qty * $st->partion) . ';';
            $csv .= "\n";
        }
        $csv = mb_convert_encoding($csv, "windows-1251", "utf-8");


        header("Content-type: text/csv");
        header("Content-Disposition: attachment;Filename=stockslist.csv");
        header("Content-Transfer-Encoding: binary");

        echo $csv;
        flush();
        die;
    }

}

class ItemDataSource implements \Zippy\Interfaces\DataSource {

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $conn = $conn = \ZDB\DB::getConnect();

        $form = $this->page->filter;
        $where = " (qty <> 0 or rqty <> 0 or wqty <> 0) ";

        $text = trim($form->searchkey->getText());
        $store = $form->searchstore->getValue();
        if ($store > 0) {
            $where = $where . " and store_id=" . $store;
        }
        $cat = $form->searchcat->getValue();


        if ($cat > 0) {
            $where = $where . " and cat_id=" . $cat;
        }
        if (strlen($text) > 0) {
            $text = Stock::qstr('%' . $text . '%');
            $where = $where . " and (itemname like {$text} or item_code like {$text} )  ";
        }

        $type = $form->searchtype->getValue();
        if ($type == 1) {
            $where = $where . " and wqty>0 ";
        }
        if ($type == 2) {
            $where = $where . " and rqty>0 ";
        }
        if ($type == 3) {
            $in = "(select distinct sc.item_id  
               from  entrylist_view  sc
               where sc.document_date >" . $conn->DBDate(strtotime('- 30 day')) . " and document_date <=  " . $conn->DBDate(time()) . "
               and sc.quantity < 0 )";
            $where = $where . " and item_id not in ($in) ";
        }

        return $where;
    }

    public function getItemCount() {
        return Stock::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        return Stock::find($this->getWhere(), "itemname asc", $count, $start);
    }

    public function getItem($id) {
        return Stock::load($id);
    }

}

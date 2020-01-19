<?php

namespace App\Pages\Service;

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

class ItemList extends \App\Pages\Base {

    public $_item;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowSer('ItemList'))
            return;

        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->filter->add(new TextInput('searchkey'));
        $this->filter->add(new DropDownChoice('searchcat', Category::findArray("cat_name", "", "cat_name"), 0));
        $this->filter->add(new DropDownChoice('searchstore', Store::getList(), 0));


        $this->add(new Panel('itempanel'));


        $this->itempanel->add(new DataView('itemlist', new ItemDataSource($this), $this, 'itemlistOnRow'));

        $this->itempanel->itemlist->setPageSize(H::getPG());
        $this->itempanel->add(new \Zippy\Html\DataList\Paginator('pag', $this->itempanel->itemlist));


        $this->itempanel->add(new ClickLink('csv', $this, 'oncsv'));
        $this->itempanel->add(new Label('totamount'));

        $this->add(new Panel('detailpanel'))->setVisible(false);
        $this->detailpanel->add(new ClickLink('back'))->onClick($this, 'backOnClick');
        $this->detailpanel->add(new Label('itemdetname'));

        $this->detailpanel->add(new DataView('stocklist', new DetailDataSource($this), $this, 'detailistOnRow'));

        $this->detailpanel->add(new Form('moveform'))->onSubmit($this, 'OnMove');
        $this->detailpanel->moveform->add(new DropDownChoice('frompart'));
        $this->detailpanel->moveform->add(new DropDownChoice('topart'));
        $this->detailpanel->moveform->add(new TextInput('mqty', '0'));


        $this->OnFilter(null);
    }

    public function itemlistOnRow($row) {
        $item = $row->getDataItem();
        $store = $this->filter->searchstore->getValue();
        $row->add(new Label('itemname', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));

        $qty = $item->getQuantity($store);
        $row->add(new Label('iqty', H::fqty($qty)));
        $row->add(new Label('minqty', H::fqty($item->minqty)));
        $row->add(new Label('iamount', H::fa(abs($item->getAmount($store)))));


        $row->add(new Label('cat_name', $item->cat_name));

        $plist = array();
        if ($item->price1 > 0)
            $plist[] = $item->getPrice('price1', $store);
        if ($item->price2 > 0)
            $plist[] = $item->getPrice('price2', $store);
        if ($item->price3 > 0)
            $plist[] = $item->getPrice('price3', $store);
        if ($item->price4 > 0)
            $plist[] = $item->getPrice('price4', $store);
        if ($item->price5 > 0)
            $plist[] = $item->getPrice('price5', $store);

        $row->add(new Label('iprice', implode(',', $plist)));


        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        if ($qty < 0) {
            $row->setAttribute('class', 'text-danger');
        }

        $row->add(new \Zippy\Html\Link\BookmarkableLink('imagelistitem'))->setValue("/loadimage.php?id={$item->image_id}");
        $row->imagelistitem->setAttribute('href', "/loadimage.php?id={$item->image_id}");
        if ($item->image_id == 0) {
            $row->imagelistitem->setVisible(false);
        }
    }

    public function OnFilter($sender) {
        $this->itempanel->itemlist->Reload();

        $am = $this->getTotalAmount();
        $this->itempanel->totamount->setText((H::fa($am)));
    }

    public function getTotalAmount() {

        $conn = \ZDB\DB::getConnect();
        $sql = "select  coalesce(sum(qty*partion),0) from store_stock_view where item_id in (select item_id from items where disabled<>1 ) ";
        $cat = $this->filter->searchcat->getValue();
        $store = $this->filter->searchstore->getValue();
        if ($store > 0) {
            $sql = $sql . " and  store_id={$store}  ";
        }

        if ($cat > 0) {
            $sql = $sql . " and cat_id=" . $cat;
        }

        $text = trim($this->filter->searchkey->getText());
        if (strlen($text) > 0) {

            $text = Stock::qstr('%' . $text . '%');
            $sql = $sql . "  and (itemname like {$text} or item_code like {$text}    )  ";
        }
        return $conn->GetOne($sql);
    }

    public function detailistOnRow($row) {
        $stock = $row->getDataItem();
        $row->add(new Label('storename', $stock->storename));
        $row->add(new Label('snumber', $stock->snumber));
        $row->add(new Label('sdate', ''));

        $row->add(new Label('sedate', ''));



        if (strlen($stock->snumber) > 0) {
            $row->sdate->setText(date('Y-m-d', $stock->sdate));
            if ($this->_item->term > 0) {
                $term = strtotime("+{$this->_item->term} month", $stock->sdate);
                $row->sedate->setText(date('Y-m-d', $term));
            }
        }
        $row->add(new Label('partion', $stock->partion));

        $row->add(new Label('qty', H::fqty($stock->qty)));
        $row->add(new Label('amount', H::fa($stock->qty * $stock->partion)));

        $item = Item::load($stock->item_id);

        if ($stock->qty < 0) {
            $row->setAttribute('class', 'text-danger');
        }

        $plist = array();
        if ($item->price1 > 0)
            $plist[] = $item->getPrice('price1', 0, $stock->partion);
        if ($item->price2 > 0)
            $plist[] = $item->getPrice('price2', 0, $stock->partion);
        if ($item->price3 > 0)
            $plist[] = $item->getPrice('price3', 0, $stock->partion);
        if ($item->price4 > 0)
            $plist[] = $item->getPrice('price4', 0, $stock->partion);
        if ($item->price5 > 0)
            $plist[] = $item->getPrice('price5', 0, $stock->partion);

        $row->add(new Label('price', implode(',', $plist)));
    }

    public function backOnClick($sender) {

        $this->itempanel->setVisible(true);
        $this->detailpanel->setVisible(false);
    }

    public function showOnClick($sender) {
        $this->_item = $sender->getOwner()->getDataItem();
        $this->itempanel->setVisible(false);
        $this->detailpanel->setVisible(true);
        $this->detailpanel->itemdetname->setText($this->_item->itemname);
        $this->detailpanel->stocklist->Reload();

        $rows = $this->detailpanel->stocklist->getDataRows();
        $st = array();
        foreach ($rows as $row) {
            $stock = $row->getDataItem();
            $name = $stock->itemname;
            if (strlen($stock->snumber) > 0)
                $name = $name . " ({$stock->snumber})";
            $name = $name . ', ' . H::fa($stock->partion);
            $st[$stock->stock_id] = $name;
        }
        $this->detailpanel->moveform->frompart->setOptionList($st);
        $this->detailpanel->moveform->topart->setOptionList($st);
    }

    public function OnMove($sender) {
        $st1 = $sender->frompart->getValue();
        $st2 = $sender->topart->getValue();
        $qty = $sender->mqty->getText();
        if ($st1 == 0 || $st2 == 0) {
            $this->setError('Не выбрана  партия');

            return;
        }
        if ($st1 == $st2) {
            $this->setError('Одинаковые партии');

            return;
        }
        if (($qty > 0) == false) {
            $this->setError('Неверное количество');

            return;
        }
        $st1 = Stock::load($st1);
        $st2 = Stock::load($st2);
        if ($qty > $st1->qty) {
            $this->setError('Превышено количество');

            return;
        }
        $doc = \App\Entity\Doc\Document::create('MoveItem');
        $doc->document_number = $doc->nextNumber();
        if (strlen($doc->document_number) == 0)
            $doc->document_number = "ПТ-000001";
        $doc->document_date = time();

        $item = Item::load($st1->item_id);
        $item->snumber = $st1->snumber;
        $item->quantity = $qty;
        $item->st1 = $st1->stock_id;
        $item->st2 = $st2->stock_id;


        $doc->detaildata[] = $item->getData();

        $store = Store::load($st1->store_id);
        $doc->headerdata['storefrom'] = $store->store_id;
        $doc->headerdata['storefromname'] = $store->storename;
        $doc->headerdata['storeto'] = $store->store_id;
        $doc->headerdata['storetoname'] = $store->storename;
        $doc->notes = "Перемещение партий";
        $doc->save();
        $doc->updateStatus(\App\Entity\Doc\Document::STATE_NEW);
        $doc->updateStatus(\App\Entity\Doc\Document::STATE_EXECUTED);

        $this->setInfo('Товар перемещен');
        $sender->clean();
        $this->detailpanel->stocklist->Reload();
    }

    public function oncsv($sender) {
        $store = $this->filter->searchstore->getValue();
        $list = $this->itempanel->itemlist->getDataSource()->getItems(-1, -1, 'itemname');
        $csv = "";

        foreach ($list as $item) {

            $csv .= $item->itemname . ',';
            $csv .= $st->item_code . ',';

            $csv .= $item->msr . ',';
            $csv .= $item->cat_name . ',';
            $qty = $item->getQuantity($store);

            $csv .= H::fqty($qty) . ',';
            $plist = array();
            if ($item->price1 > 0)
                $plist[] = $item->getPrice('price1', $store);
            if ($item->price2 > 0)
                $plist[] = $item->getPrice('price2', $store);
            if ($item->price3 > 0)
                $plist[] = $item->getPrice('price3', $store);
            if ($item->price4 > 0)
                $plist[] = $item->getPrice('price4', $store);
            if ($item->price5 > 0)
                $plist[] = $item->getPrice('price5', $store);



            $csv .= implode(' ', $plist) . ',';

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
        $where = "   disabled <> 1 ";

        $cat = $form->searchcat->getValue();
        $store = $form->searchstore->getValue();


        if ($cat > 0) {
            $where = $where . " and cat_id=" . $cat;
        }
        if ($store > 0) {
            $where = $where . " and item_id in (select item_id from store_stock where qty <> 0 and store_id={$store}) ";
        } else {
            $where = $where . " and item_id in (select item_id from store_stock where qty <> 0) ";
        }
        $text = trim($form->searchkey->getText());
        if (strlen($text) > 0) {

            $text = Stock::qstr('%' . $text . '%');
            $where = "   (itemname like {$text} or item_code like {$text}  or bar_code like {$text}  )  ";
        }



        return $where;
    }

    public function getItemCount() {
        return Item::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {


        return Item::find($this->getWhere(), "itemname asc", $count, $start);
    }

    public function getItem($id) {
        return Stock::load($id);
    }

}

class DetailDataSource implements \Zippy\Interfaces\DataSource {

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {


        $form = $this->page->filter;
        $where = "item_id = {$this->page->_item->item_id} and   qty <> 0   ";
        $store = $form->searchstore->getValue();
        if ($store > 0) {
            $where = $where . " and   store_id={$store}  ";
        }


        return $where;
    }

    public function getItemCount() {
        return Stock::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        return Stock::find($this->getWhere(), "", $count, $start);
    }

    public function getItem($id) {
        return Stock::load($id);
    }

}

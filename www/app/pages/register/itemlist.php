<?php

namespace App\Pages\Register;

use App\Entity\Category;
use App\Entity\Item;
use App\Entity\Stock;
use App\Entity\Store;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

class ItemList extends \App\Pages\Base
{

    public  $_item;
    private $_total;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('ItemList')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->filter->add(new TextInput('searchkey'));
        $this->filter->add(new DropDownChoice('searchcat', Category::getList(), 0));
        $storelist = Store::getList() ;
        
        if(\App\System::getUser()->showotherstores) {
            $storelist = Store::getListAll() ;
            
        }         
        $this->filter->add(new DropDownChoice('searchstore', $storelist, 0));

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

        $this->OnFilter(null);

        $options = \App\System::getOptions('common');

        $this->_tvars['hp1'] = strlen($options['price1']) > 0 ? $options['price1'] : false;
        $this->_tvars['hp2'] = strlen($options['price2']) > 0 ? $options['price2'] : false;
        $this->_tvars['hp3'] = strlen($options['price3']) > 0 ? $options['price3'] : false;
        $this->_tvars['hp4'] = strlen($options['price4']) > 0 ? $options['price4'] : false;
        $this->_tvars['hp5'] = strlen($options['price5']) > 0 ? $options['price5'] : false;


    }

    public function itemlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();
        $store = $this->filter->searchstore->getValue();

        $row->add(new Label('itemname', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('brand', $item->manufacturer));
        $row->add(new Label('msr', $item->msr));

        $qty = $item->getQuantity($store);
        $row->add(new Label('iqty', H::fqty($qty)));
        $row->add(new Label('minqty', H::fqty($item->minqty)));
        $am = $item->getAmount($store);
        $row->add(new Label('iamount', H::fa(abs($am))));

        $row->add(new Label('cat_name', $item->cat_name));

        $plist = array();

        $row->add(new Label('iprice1', H::fa($item->getPrice('price1', $store))));
        $row->add(new Label('iprice2', H::fa($item->getPrice('price2', $store))));
        $row->add(new Label('iprice3', H::fa($item->getPrice('price3', $store))));
        $row->add(new Label('iprice4', H::fa($item->getPrice('price4', $store))));
        $row->add(new Label('iprice5', H::fa($item->getPrice('price5', $store))));

        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        if ($qty < 0) {
            $row->setAttribute('class', 'text-danger');
        }
        if ($qty == 0) {
            $row->setAttribute('class', 'text-warning');
        }

        $row->add(new \Zippy\Html\Link\BookmarkableLink('imagelistitem'))->setValue("/loadimage.php?id={$item->image_id}");
        $row->imagelistitem->setAttribute('href', "/loadimage.php?id={$item->image_id}");
        if ($item->image_id == 0) {
            $row->imagelistitem->setVisible(false);
        }


    }

    public function OnFilter($sender) {
        $this->_total = 0;
        $this->itempanel->itemlist->Reload();

        $am = $this->getTotalAmount();
        $this->itempanel->totamount->setText((H::fa($am)));
    }

    public function getTotalAmount() {

        $cstr = \App\Acl::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "    store_id in ({$cstr})  and   ";
        }

        $conn = \ZDB\DB::getConnect();
        $sql = "select  coalesce(sum(qty*partion),0) from store_stock_view where {$cstr} qty <>0 and item_id in (select item_id from items where disabled<>1 ) ";


        $cat = $this->filter->searchcat->getValue();
        $store = $this->filter->searchstore->getValue();
        if ($store > 0) {
            $sql = $sql . " and  store_id={$store}  ";
        }


        $text = trim($this->filter->searchkey->getText());
        if (strlen($text) > 0) {

            $text = Stock::qstr('%' . $text . '%');
            $sql = $sql . "  and (itemname like {$text} or item_code like {$text}    )  ";
            $cat = 0;
        }
        if ($cat > 0) {
            $sql = $sql . " and cat_id=" . $cat;
        }
        return $conn->GetOne($sql);
    }

    public function detailistOnRow($row) {
        $stock = $row->getDataItem();
        $row->add(new Label('storename', $stock->storename));
        $row->add(new Label('snumber', $stock->snumber));
        $row->add(new Label('sdate', ''));

        if (strlen($stock->snumber) > 0 && strlen($stock->sdate) > 0) {
            $row->sdate->setText(H::fd($stock->sdate));
        }
        $row->add(new Label('partion', H::fa($stock->partion)));


        $row->add(new Label('qty', H::fqty($stock->qty)));
        $row->add(new Label('amount', H::fa($stock->qty * $stock->partion)));
        $row->add(new Label('rate', ''));
        $item = Item::load($stock->item_id);
        if ($this->_tvars["useval"] && $item->rate > 0) {
            $row->rate->setText($item->rate . H::getValName($item->val));
        }
        if ($stock->qty < 0) {
            $row->setAttribute('class', 'text-danger');
        }

        $plist = array();
        if ($item->price1 > 0) {
            $plist[] = $item->getPrice('price1', 0, $stock->partion);
        }
        if ($item->price2 > 0) {
            $plist[] = $item->getPrice('price2', 0, $stock->partion);
        }
        if ($item->price3 > 0) {
            $plist[] = $item->getPrice('price3', 0, $stock->partion);
        }
        if ($item->price4 > 0) {
            $plist[] = $item->getPrice('price4', 0, $stock->partion);
        }
        if ($item->price5 > 0) {
            $plist[] = $item->getPrice('price5', 0, $stock->partion);
        }

        $row->add(new Label('price', implode(',', $plist)));
        $row->add(new \Zippy\Html\Link\RedirectLink("createmove", "\\App\\Pages\\Doc\\MovePart", array(0, $stock->stock_id)))->setVisible($stock->qty < 0);

        if (\App\System::getUser()->noshowpartion  ) {
          //  $row->partion->setText('');
          //   $row->amount->setText('');
        }
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
            if (strlen($stock->snumber) > 0) {
                $name = $name . " ({$stock->snumber})";
            }
            $name = $name . ', ' . H::fa($stock->partion);
            $st[$stock->stock_id] = $name;
        }
    }

    public function oncsv($sender) {
        $store = $this->filter->searchstore->getValue();
        $list = $this->itempanel->itemlist->getDataSource()->getItems(-1, -1, 'itemname');

        $common = System::getOptions('common') ;
        
        
        $header = array();
        $data = array();

        $header['A1'] = "Наименуваня";
        $header['B1'] = "Артикул";
        $header['C1'] = "Штрих-код";
        $header['D1'] = "Од.";
        $header['E1'] = "Категорiя";
        $header['F1'] = "Кiл.";
        
        if(strlen($common['price1'])) $header['G1'] = $common['price1'];
        if(strlen($common['price2'])) $header['H1'] = $common['price2'];
        if(strlen($common['price3'])) $header['I1'] = $common['price3'];
        if(strlen($common['price4'])) $header['J1'] = $common['price4'];
        if(strlen($common['price5'])) $header['K1'] = $common['price5'];
        
        
        $i = 1;
        foreach ($list as $item) {
            $i++;
            $data['A' . $i] = $item->itemname;
            $data['B' . $i] = $item->item_code;
            $data['C' . $i] = $item->bar_code;
            $data['D' . $i] = $item->msr;
            $data['E' . $i] = $item->cat_name;
            $qty = $item->getQuantity($store);
            $data['F' . $i] = H::fqty($qty);
            
             
            if ($item->price1 > 0) {
                $data['G' . $i] = $item->getPrice('price1', $store);
            }
            if ($item->price2 > 0) {
                $data['H' . $i] = $item->getPrice('price2', $store);
            }
            if ($item->price3 > 0) {
                $data['I' . $i] = $item->getPrice('price3', $store);
            }
            if ($item->price4 > 0) {
                $data['J' . $i] = $item->getPrice('price4', $store);
            }               
            if ($item->price5 > 0) {
                $data['K' . $i] = $item->getPrice('price5', $store);
            }
        
        
        }
        

        H::exportExcel($data, $header, 'itemlist.xlsx');
    }

}

class ItemDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
        $conn = $conn = \ZDB\DB::getConnect();

        $form = $this->page->filter;
        $where = "   disabled <> 1 and  ( select sum(st1.qty) from store_stock st1 where st1.item_id= item_id ) <>0 ";
        $cstr = \App\Acl::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "    store_id in ({$cstr})  and   ";
        }
        if(\App\System::getUser()->showotherstores) {
            $cstr =""; ;
            
        }   
        $cat = $form->searchcat->getValue();
        $store = $form->searchstore->getValue();

        if ($cat > 0) {
            $where = $where . " and cat_id=" . $cat;
        }
        if ($store > 0) {
            $where = $where . " and item_id in (select item_id from store_stock where {$cstr}  qty <> 0 and store_id={$store}) ";
        } else {
            $where = $where . " and item_id in (select item_id from store_stock where  {$cstr}  qty <> 0) ";
        }
        $text = trim($form->searchkey->getText());
        if (strlen($text) > 0) {

            $text = Stock::qstr('%' . $text . '%');

          //  $where = "   disabled <> 1 and  ( select sum(st1.qty) from store_stock st1 where st1.item_id= item_id ) <>0 ";

            $where .= " and   (itemname like {$text} or item_code like {$text}  or bar_code like {$text}  )  ";
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

class DetailDataSource implements \Zippy\Interfaces\DataSource
{

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

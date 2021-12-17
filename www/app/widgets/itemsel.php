<?php

namespace App\Widgets;

use App\Entity\Category;
use App\Entity\Item;
use App\Helper as H;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\Column;
use Zippy\Html\DataList\DataTable;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;

/**
 * Виджет для подбора  товаров
 */
class ItemSel extends \Zippy\Html\PageFragment
{

    private $_page;
    private $_event;
    private $_pricetype;
    private $_store = 0;
    public  $_list  = array();

    /**
     *
     *
     * @param mixed $id
     * @param mixed $page
     * @param mixed $event
     * @param mixed $pricetype
     */
    public function __construct($id, $page, $event) {
        parent::__construct($id);
        $this->_page = $page;
        $this->_event = $event;

        $this->add(new Form('wisfilter'))->onSubmit($this, 'ReloadData');

        $this->wisfilter->add(new TextInput('wissearchkey'));
        $this->wisfilter->add(new DropDownChoice('wissearchcat', Category::getList(false,false), 0));
        $this->wisfilter->add(new TextInput('wissearchmanufacturer'));

        $ds = new ArrayDataSource($this, '_list');

        $table = $this->add(new DataTable('witemselt', $ds, true, true));
        $table->setPageSize(H::getPG());
        $table->AddColumn(new Column('itemname', H::l('name'), true, true, true));
        $table->AddColumn(new Column('item_code', H::l('code'), true, true, false));
        $table->AddColumn(new Column('bar_code', H::l('barcode'), true, true, false));
        $table->AddColumn(new Column('manufacturer', H::l('brand'), true, true, false));

        $table->setCellClickEvent($this, 'OnSelect');
    }

    /**
     * тип  цены для  столбца  Цена
     *
     * @param mixed $pricetype
     * @param mixed $store
     */
    public function setPriceType($pricetype, $store = 0) {
        $this->_pricetype = $pricetype;
        $this->_store = $store;
        if (strlen($this->_pricetype) > 0) {
            $this->witemselt->AddColumn(new Column('price', 'Цена', true, true, false, "text-right", "text-right"));
        }
    }

    /**
     * Обновление данных
     *
     */
    public function Reload() {
        $this->wisfilter->clean();
        $this->ReloadData($this->wisfilter);
    }

    public function OnSelect($sender, $data) {
        $item = $data['dataitem'];
        $this->_page->{$this->_event}($item->item_id, $item->itemname);
    }

    public function ReloadData($sender) {

        $where = "disabled <> 1";
        $text = trim($this->wisfilter->wissearchkey->getText());
        $man = trim($this->wisfilter->wissearchmanufacturer->getText());
        $cat = $this->wisfilter->wissearchcat->getValue();

        if ($cat > 0) {
            $where = $where . " and cat_id=" . $cat;
        }

        if (strlen($text) > 0) {

            $text = Item::qstr('%' . $text . '%');
            $where = $where . " and (itemname like {$text} or item_code like {$text} )  ";
        }
        if (strlen($man) > 0) {

            $man = Item::qstr($man);
            $where = $where . " and  manufacturer like {$man}      ";
        }


        $list = Item::find($where);

        $this->_list = array();
        foreach ($list as $item) {

            if (strlen($this->_pricetype) > 0) {
                $item->price = $item->getPrice($this->_pricetype, $this->_store);
            }

            $this->_list[] = $item;
        }

        $this->witemselt->Reload();
    }

}

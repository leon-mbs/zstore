<?php

namespace App\Modules\Rozetka;

use App\Application as App;
use App\Entity\Item;
use App\Helper as H;
use App\System;
use Zippy\Binding\PropertyBinding as Prop;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\SubmitLink;

/**
 * Товари: звірка артикулів з Rozetka та оновлення цін і залишків
 */
class Items extends \App\Pages\Base
{
    public $_items = [];

    public function __construct() {
        parent::__construct();

        if (!Helper::allowed()) {
            System::setErrorMsg('Немає права доступу до сторінки');
            App::RedirectError();
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('searchcat', \App\Entity\Category::getList(), 0));
        $this->filter->add(new DropDownChoice('show', [0 => 'Усі', 1 => 'Лише не знайдені на Rozetka', 2 => 'Лише знайдені'], 0));

        $this->add(new Form('updform'))->onSubmit($this, 'updOnSubmit');
        $this->updform->add(new DataView('itemlist', new ArrayDataSource(new Prop($this, '_items')), $this, 'itemOnRow'));
        $this->updform->itemlist->setPageSize(H::getPG());
        $this->updform->add(new Paginator('pag', $this->updform->itemlist));
        $this->updform->add(new SubmitLink('updsel'))->onClick($this, 'updSelOnClick');
        $this->updform->add(new SubmitLink('updall'))->onClick($this, 'updAllOnClick');

        $this->_tvars['feedurl'] = Helper::feedUrl();
        $this->_tvars['hasfeed'] = strlen(Helper::feedUrl()) > 0;
    }

    /**
     * Артикул -> товар Rozetka (id, price, stock_quantity)
     */
    private function rozByArticle() {
        $map = [];
        foreach (Api::allGoods() as $g) {
            $a = trim($g['article'] ?? '');
            if (strlen($a) == 0) {
                continue;
            }
            $map[$a] = [
                'id'    => intval($g['rz_item_id'] ?? $g['id'] ?? 0),
                'price' => floatval($g['price'] ?? 0),
                'qty'   => intval($g['stock_quantity'] ?? 0),
            ];
        }
        return $map;
    }

    public function filterOnSubmit($sender) {
        $this->_items = [];
        try {
            $roz = $this->rozByArticle();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            $this->updform->itemlist->Reload();
            return;
        }

        $pricetype = Helper::opt('pricetype', 'price1');
        $store = intval(Helper::opt('store', 0));
        $cat = intval($this->filter->searchcat->getValue());
        $show = intval($this->filter->show->getValue());

        $where = "disabled <> 1 and item_code <> ''";
        if ($cat > 0) {
            $where .= ' and cat_id=' . $cat;
        }
        foreach (Item::findYield($where, 'itemname') as $item) {
            if (intval($item->noshop ?? 0) == 1) {
                continue;
            }
            $r = $roz[trim($item->item_code)] ?? null;
            if ($show == 1 && $r != null) {
                continue;
            }
            if ($show == 2 && $r == null) {
                continue;
            }
            $item->qty = floatval($item->getQuantity($store));
            $item->zprice = floatval($item->getPrice($pricetype, $store));
            $item->rozid = $r['id'] ?? 0;
            $item->rozprice = $r['price'] ?? null;
            $item->rozqty = $r['qty'] ?? null;
            $this->_items[] = $item;
        }
        $this->updform->itemlist->Reload();
        $this->setInfo('На Rozetka товарів з артикулом: ' . count($roz) . '. У списку: ' . count($this->_items));
    }

    public function itemOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new CheckBox('ch', new Prop($item, 'ch')));
        $row->add(new Label('name', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('qty', H::fqty($item->qty)));
        $row->add(new Label('price', H::fa($item->zprice)));
        $found = intval($item->rozid) > 0;
        $row->add(new Label('rozqty', $found ? H::fqty($item->rozqty) : '—'));
        $row->add(new Label('rozprice', $found ? H::fa($item->rozprice) : '—'));
        $row->add(new Label('found', $found ? 'є' : 'немає'));
        $row->found->setAttribute('class', $found ? 'text-success' : 'text-danger');
    }

    /**
     * Надіслати ціни і залишки для переданих товарів (лише знайдених на Rozetka)
     */
    private function push(array $items) {
        $list = [];
        foreach ($items as $item) {
            if (intval($item->rozid) == 0) {
                continue;
            }
            $qty = intval(floor($item->qty));
            $list[] = [
                'item_rz_id'     => intval($item->rozid),
                'price'          => round($item->zprice, 2),
                'stock_quantity' => $qty < 0 ? 0 : $qty,
            ];
        }
        if (count($list) == 0) {
            $this->setError('Немає товарів, знайдених на Rozetka');
            return;
        }
        try {
            $n = Api::massUpdate($list);
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return;
        }
        $this->setSuccess("Надіслано на Rozetka: {$n}. Ціни, що змінились більш як у 1,5 раза, Rozetka затверджує вручну.");
    }

    public function updSelOnClick($sender) {
        $sel = array_filter($this->_items, fn($i) => $i->ch == true);
        if (count($sel) == 0) {
            $this->setError('Не обрано товар');
            return;
        }
        $this->push($sel);
    }

    public function updAllOnClick($sender) {
        if (count($this->_items) == 0) {
            $this->setError('Спершу завантажте список');
            return;
        }
        $this->push($this->_items);
    }

    public function updOnSubmit($sender) {
    }
}

<?php

namespace App\Widgets;

use App\DataItem;
use App\Helper;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Label;

/**
 * Виджет для минимального количества на  складе
 */
class WMinQty extends \Zippy\Html\PageFragment
{

    private $data = array();

    public function __construct($id) {
        parent::__construct($id);
        $this->add(new \Zippy\Html\Link\ClickLink('csvminqty', $this, 'oncsv'));
        $visible = (strpos(System::getUser()->widgets, 'wminqty') !== false || System::getUser()->userlogin == 'admin');

        $cstr = \App\Acl::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = " where  store_id in ({$cstr})  ";
        }

        $conn = $conn = \ZDB\DB::getConnect();
        $this->data = array();


        $sql = "select t.qty, i.`minqty`,i.`itemname`,i.`item_code`,s.`storename`  from (select  item_id,store_id,coalesce(sum( `qty`),0) as qty   from  store_stock
            {$cstr} group by item_id,store_id   ) t
            join items  i  on t.item_id = i.item_id
            join stores s  on t.store_id = s.store_id
            where i.disabled  <> 1 and  t.qty < i.`minqty` and i.`minqty`>0 ";

        if ($visible) {
            $rs = $conn->Execute($sql);

            foreach ($rs as $row) {

                $this->data[] = new DataItem($row);
            }
        }

        $mqlist = $this->add(new DataView('mqlist', new ArrayDataSource($this->data), $this, 'mqlistOnRow'));
        $mqlist->setPageSize(10);
        $this->add(new \Zippy\Html\DataList\Paginator("mqpag", $mqlist));
        $mqlist->Reload();


        if (count($this->data) == 0 || $visible == false) {
            $this->setVisible(false);
        };
    }

    public function mqlistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('storename', $item->storename));
        $row->add(new Label('itemname', $item->itemname));
        $row->add(new Label('item_code', $item->item_code));
        $row->add(new Label('qty', Helper::fqty($item->qty)));
        $row->add(new Label('minqty', Helper::fqty($item->minqty)));
    }

    public function oncsv($sender) {

        $csv = "";

        foreach ($this->data as $d) {

            $csv .= $d->storename . ';';
            $csv .= $d->itemname . ';';
            $csv .= $d->item_code . ';';
            $csv .= $d->qty . ';';
            $csv .= $d->minqty;

            $csv .= "\n";
        }
        $csv = mb_convert_encoding($csv, "windows-1251", "utf-8");


        header("Content-type: text/csv");
        header("Content-Disposition: attachment;Filename=minqty.csv");
        header("Content-Transfer-Encoding: binary");

        echo $csv;
        flush();
        die;
    }

}

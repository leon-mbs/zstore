<?php

namespace App\Widgets;

use \Zippy\Binding\PropertyBinding as Bind;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Label;
use \App\Helper;
use \App\System;
use \App\Entity\Doc\Document;
use \Carbon\Carbon;
use \App\Entity\Item;
use \App\DataItem;

/**
 * Виджет для минимального количества на  складе
 */
class WMinQty extends \Zippy\Html\PageFragment {

    private $data = array();

    public function __construct($id) {
        parent::__construct($id);
        $this->add(new \Zippy\Html\Link\ClickLink('csvminqty', $this, 'oncsv'));
        $visible = (strpos(System::getUser()->widgets, 'wminqty') !== false || System::getUser()->userlogin == 'admin');

        $cstr = \App\Acl::getStoreBranchConstraint();
        if (strlen($cstr) > 0)
            $cstr = "  s.store_id in ({$cstr}) and ";

        $conn = $conn = \ZDB\DB::getConnect();
        $this->data = array();


        $sql = "select * from (select item_id, itemname,
                 (select  coalesce(sum(s.qty) ,0) from `store_stock_view` s where {$cstr} s.item_id=i.item_id and s.qty <> 0    ) as iqty,
                 i.`minqty`
                 from items i where minqty>0 )t where   iqty <  minqty   
                 ";

        if ($visible) {
            $rs = $conn->Execute($sql);

            foreach ($rs as $row) {

                $this->data[$row['item_id']] = new DataItem($row);
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

        $row->add(new Label('itemname', $item->itemname));
        $row->add(new Label('qty', Helper::fqty($item->iqty)));
        $row->add(new Label('minqty', Helper::fqty($item->minqty)));
    }

    public function oncsv($sender) {

        $csv = "";

        foreach ($this->data as $d) {

            $csv .= $d->itemname . ';';
            $csv .= $d->iqty . ';';
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

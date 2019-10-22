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

    public function __construct($id) {
        parent::__construct($id);

        $visible = (strpos(System::getUser()->widgets, 'wminqty') !== false || System::getUser()->userlogin == 'admin');

        $conn = $conn = \ZDB\DB::getConnect();
        $data = array();


        $sql = "select * from (select item_id, itemname,
                 (select  coalesce(sum(s.qty) ,0) from `store_stock_view` s where  s.item_id=i.item_id and s.qty >0    ) as iqty,
                 i.`minqty`
                 from items i where minqty>0 )t where   iqty <  minqty   
                 ";

        if ($visible) {
            $rs = $conn->Execute($sql);

            foreach ($rs as $row) {

                $data[$row['item_id']] = new DataItem($row);
            }
        }

        $mqlist = $this->add(new DataView('mqlist', new ArrayDataSource($data), $this, 'mqlistOnRow'));
        $mqlist->setPageSize(10);
        $this->add(new \Zippy\Html\DataList\Paginator("mqpag", $mqlist));
        $mqlist->Reload();


        if (count($data) == 0 || $visible == false) {
            $this->setVisible(false);
        };
    }

    public function mqlistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('itemname', $item->itemname));
        $row->add(new Label('qty', Helper::fqty($item->qty)));
        $row->add(new Label('minqty', Helper::fqty($item->minqty)));
    }

}

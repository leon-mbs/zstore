<?php

namespace App\Widgets;

use App\DataItem;
use App\Helper;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Label;

/**
 * Виджет для  просмотра ожидаемых товаров
 */
class WWaited extends \Zippy\Html\PageFragment
{

    public function __construct($id) {
        parent::__construct($id);

        $visible = (strpos(System::getUser()->widgets, 'wwaited') !== false || System::getUser()->userlogin == 'admin');

        $conn = $conn = \ZDB\DB::getConnect();
        $data = array();

        $cstr = \App\Acl::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "   sv.store_id in ({$cstr}) and  ";
        }

        $sql = "select sum( ev.quantity) as qty, sv.`item_id`, sv.`store_id`,  sv.`itemname`, sv.`storename` from
        `store_stock_view` sv  join entrylist_view ev on ev.stock_id = sv.stock_id
         where {$cstr}  ev.quantity < 0  and   ev.document_date > cast(now() as date)
         group by sv.`item_id`, sv.`store_id`,  sv.`itemname`, sv.`storename`
         order  by  sv.itemname";


        if ($visible) {
            $rs = $conn->Execute($sql);

            foreach ($rs as $row) {

                $data[$row['item_id'] . '_' . $row['store_id']] = new DataItem($row);
            }
        }

        $waitlist = $this->add(new DataView('waitlist', new ArrayDataSource($data), $this, 'waitlistOnRow'));
        $waitlist->setPageSize(10);
        $this->add(new \Zippy\Html\DataList\Paginator("waitpag", $waitlist));
        $waitlist->Reload();


        if (count($data) == 0 || $visible == false) {
            $this->setVisible(false);
        };
    }

    public function waitlistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('waititem', $item->storename));
        $row->add(new Label('waitstore', $item->itemname));
        $row->add(new Label('waitqty', Helper::fqty($item->qty)));
    }

}

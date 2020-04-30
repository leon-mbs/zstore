<?php

namespace App\Widgets;

use App\DataItem;
use App\Helper;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Label;

/**
 * Виджет для  просмотра зарезервированных товаров
 */
class WReserved extends \Zippy\Html\PageFragment
{

    public function __construct($id) {
        parent::__construct($id);

        $visible = (strpos(System::getUser()->widgets, 'wreserved') !== false || System::getUser()->userlogin == 'admin');
        $cstr = \App\Acl::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "  sv.store_id in ({$cstr}) and  ";
        }

        $conn = $conn = \ZDB\DB::getConnect();
        $data = array();


        $sql = "select sum( ev.quantity) as qty, sv.`item_id`, sv.`store_id`,  sv.`itemname`, sv.`storename` from
        `store_stock_view` sv  join entrylist_view ev on ev.stock_id = sv.stock_id
         where  {$cstr} ev.quantity > 0  and   ev.document_date > cast(now() as date)
         group by sv.`item_id`, sv.`store_id`,  sv.`itemname`, sv.`storename`
         order  by  sv.itemname  ";

        if ($visible) {
            $rs = $conn->Execute($sql);

            foreach ($rs as $row) {

                $data[$row['item_id'] . '_' . $row['store_id']] = new DataItem($row);
            }
        }

        $reslist = $this->add(new DataView('reslist', new ArrayDataSource($data), $this, 'reslistOnRow'));
        $reslist->setPageSize(10);
        $this->add(new \Zippy\Html\DataList\Paginator("respag", $reslist));
        $reslist->Reload();


        if (count($data) == 0 || $visible == false) {
            $this->setVisible(false);
        };
    }

    public function reslistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('resitem', $item->storename));
        $row->add(new Label('resstore', $item->itemname));
        $row->add(new Label('resqty', Helper::fqty($item->qty)));
    }

}

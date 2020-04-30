<?php

namespace App\Widgets;

use App\Entity\Stock;
use App\Helper;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Label;

/**
 * Виджет для просроченных товаров
 */
class WSdate extends \Zippy\Html\PageFragment
{

    public function __construct($id) {
        parent::__construct($id);

        $visible = (strpos(System::getUser()->widgets, 'wsdate') !== false || System::getUser()->userlogin == 'admin');
        $cstr = \App\Acl::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "   store_id in ({$cstr}) and  ";
        }

        $conn = $conn = \ZDB\DB::getConnect();
        $data = array();

        if ($visible) {

            $stock = Stock::find(" {$cstr} qty > 0 and sdate is not null  and sdate <  ADDDATE( now(), INTERVAL 7 day)  ");


            foreach ($stock as $st) {

                $data[$st->stock_id] = $st;


            }
        }

        $sdlist = $this->add(new DataView('sdlist', new ArrayDataSource($data), $this, 'sdlistOnRow'));
        $sdlist->setPageSize(10);
        $this->add(new \Zippy\Html\DataList\Paginator("sdpag", $sdlist));
        $sdlist->Reload();


        if (count($data) == 0 || $visible == false) {
            $this->setVisible(false);
        };
    }

    public function sdlistOnRow($row) {
        $stock = $row->getDataItem();

        $row->add(new Label('storename', $stock->storename));
        $row->add(new Label('itemname', $stock->itemname));
        $row->add(new Label('snumber', $stock->snumber));
        $row->add(new Label('edate', date('Y-m-d', $stock->sdate)));
        $row->add(new Label('qty', Helper::fqty($stock->qty)));
        $row->edate->setAttribute('class', 'badge badge-danger');
        if ($stock->sdate > time()) {
            $row->edate->setAttribute('class', 'badge badge-warning');
        }
    }

}

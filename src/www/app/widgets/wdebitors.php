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

/**
 * Виджет для  должников
 */
class WDebitors extends \Zippy\Html\PageFragment
{

    public function __construct($id) {
        parent::__construct($id);

        $visible = (strpos(System::getUser()->widgets, 'wdebitors') !== false || System::getUser()->userlogin == 'admin');


        $data = array();


        $conn = $conn = \ZDB\DB::getConnect();
        $sql = "select * from (
            select meta_desc,document_number, customer_name,   (   datatag-amount)  as am
            from `documents_view` where meta_name in ('GoodsIssue','Goodsreceipt','Task') and amount > datatag
              
            ) t  order by am   ";

        if ($visible) {

            $rs = $conn->Execute($sql);
            foreach ($rs as $row) {
                $data[] = new \App\DataItem($row);
            }
        }


        $list = $this->add(new DataView('ddoclist', new ArrayDataSource($data), $this, 'OnRow'));
        $list->Reload();

        if (count($data) == 0 || $visible == false) {
            $this->setVisible(false);
        };
    }

    public function OnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('cust', $item->customer_name));
        $row->add(new Label('amount', 0 - ($item->am)));
        $row->add(new Label('type', $item->meta_desc));
        $row->add(new Label('number', $item->document_number));
    }

}

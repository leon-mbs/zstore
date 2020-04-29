<?php

namespace App\Widgets;

use App\Helper;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Label;

/**
 * Виджет для  должников
 */
class WDebitors extends \Zippy\Html\PageFragment
{

    public function __construct($id) {
        parent::__construct($id);

        $visible = (strpos(System::getUser()->widgets, 'wdebitors') !== false || System::getUser()->userlogin == 'admin');

        $cstr = \App\Acl::getBranchListConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "  branch_id in({$cstr}) and ";
        }

        $data = array();


        $conn = $conn = \ZDB\DB::getConnect();
        $sql = "select * from (
            select meta_desc,document_number, customer_name,  abs( amount - payamount)  as am 
            from `documents_view` where {$cstr} amount > 0 and amount <> payamount  and state not in (1,2,3,17)  and meta_name in('GoodsReceipt','GoodsIssue','Task','ServiceAct') 
              
            ) t  order by am desc  ";

        if ($visible) {

            $rs = $conn->Execute($sql);
            foreach ($rs as $row) {
                $data[] = new \App\DataItem($row);
            }
        }


        $list = $this->add(new DataView('ddoclist', new ArrayDataSource($data), $this, 'OnRow'));
        $list->Reload();
        $list->setPageSize(10);
        $this->add(new \Zippy\Html\DataList\Paginator("wdpag", $list));
        $list->Reload();
        if (count($data) == 0 || $visible == false) {
            $this->setVisible(false);
        };
    }

    public function OnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('cust', $item->customer_name));
        $row->add(new Label('amount', Helper::fa($item->am)));
        $row->add(new Label('type', $item->meta_desc));
        $row->add(new Label('number', $item->document_number));
    }

}

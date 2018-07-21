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
 * Виджет для  просмотра
 */
class WNoliq extends \Zippy\Html\PageFragment
{

    public function __construct($id) {
        parent::__construct($id);

        $visible = (strpos(System::getUser()->widgets, 'wnoliq') !== false || System::getUser()->userlogin == 'admin');

        $conn = $conn = \ZDB\DB::getConnect();
        $data = array();


        $sql = "select distinct sv.qty, sv.`item_id`,sv.`store_id`, sv.`itemname`,sv.`storename` from `store_stock_view`   sv 
         where  sv.qty >0  and   
                 sv.stock_id not  in(select sc.stock_id  
               from  entrylist_view  sc
               where sc.document_date >" . $conn->DBDate(strtotime('- 30 day')) . " and document_date <  " . $conn->DBDate(time()) . "
               and sc.quantity < 0 )  
                 
                
                 ";

        if ($visible) {
            $rs = $conn->Execute($sql);

            foreach ($rs as $row) {

                $data[$row['item_id'] . '_' . $row['store_id']] = new DataItem($row);
            }
        }

        $noliqlist = $this->add(new DataView('noliqlist', new ArrayDataSource($data), $this, 'noliqlistOnRow'));
        $noliqlist->setPageSize(20);
        $this->add(new \Zippy\Html\DataList\Paginator("noliqpag", $noliqlist));
        $noliqlist->Reload();


        if (count($data) == 0 || $visible == false) {
            $this->setVisible(false);
        };
    }

    public function noliqlistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('noliqitem', $item->storename));
        $row->add(new Label('noliqstore', $item->itemname));
        $row->add(new Label('now', $item->qty));
    }

}

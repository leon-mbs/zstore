<?php

namespace App\Widgets;

use App\DataItem;
use App\Helper;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Label;

/**
 * Виджет для  просмотра неликвидных товаров
 */
class WNoliq extends \Zippy\Html\PageFragment
{

    private $data = array();

    public function __construct($id) {
        parent::__construct($id);
        $this->add(new \Zippy\Html\Link\ClickLink('csvnoliq', $this, 'oncsv'));
        $visible = (strpos(System::getUser()->widgets, 'wnoliq') !== false || System::getUser()->userlogin == 'admin');
        $cstr = \App\Acl::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = " and st.store_id in ({$cstr})    ";
        }

        $conn = $conn = \ZDB\DB::getConnect();
        $this->data = array();


        $sql = "select coalesce(sum(st.qty),0) as qty, st.itemname,st.item_code,st.storename from  store_stock_view  st where st.itemdisabled <> 1   
               {$cstr} and   st.stock_id not  in(select   stock_id    
               from  entrylist_view  
               where    document_date >" . $conn->DBDate(strtotime('- 30 day')) . "  and  quantity < 0 )  
               group by  st.itemname,st.item_code,st.storename  
                 ";

        if ($visible) {
            $rs = $conn->Execute($sql);

            foreach ($rs as $row) {
                $this->data[] = new DataItem($row);
            }
        }

        $noliqlist = $this->add(new DataView('noliqlist', new ArrayDataSource($this->data), $this, 'noliqlistOnRow'));
        $noliqlist->setPageSize(10);
        $this->add(new \Zippy\Html\DataList\Paginator("noliqpag", $noliqlist));
        $noliqlist->Reload();


        if (count($this->data) == 0 || $visible == false) {
            $this->setVisible(false);
        };
    }

    public function noliqlistOnRow($row) {
        $item = $row->getDataItem();


        $row->add(new Label('storename', $item->storename));
        $row->add(new Label('itemname', $item->itemname));
        $row->add(new Label('item_code', $item->item_code));
        $row->add(new Label('qty', Helper::fqty($item->qty)));
    }

    public function oncsv($sender) {

        $csv = "";

        foreach ($this->data as $d) {


            $csv .= $d->storename . ';';
            $csv .= $d->itemname . ';';
            $csv .= $d->item_code . ';';
            $csv .= $d->qty;


            $csv .= "\n";
        }
        $csv = mb_convert_encoding($csv, "windows-1251", "utf-8");


        header("Content-type: text/csv");
        header("Content-Disposition: attachment;Filename=noliq.csv");
        header("Content-Transfer-Encoding: binary");

        echo $csv;
        flush();
        die;
    }

}

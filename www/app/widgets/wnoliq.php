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
 * Виджет для  просмотра неликвидных товаров
 */
class WNoliq extends \Zippy\Html\PageFragment {
      private $data = array();
    public function __construct($id) {
        parent::__construct($id);
         $this->add(new \Zippy\Html\Link\ClickLink('csvnoliq', $this, 'oncsv'));
        $visible = (strpos(System::getUser()->widgets, 'wnoliq') !== false || System::getUser()->userlogin == 'admin');

        $conn = $conn = \ZDB\DB::getConnect();
        $this->data = array();


        $sql = "select distinct sv.qty, sv.`item_id`,sv.`store_id`, sv.`itemname`,sv.`storename` from `store_stock_view`   sv 
         where  sv.qty >0  
               and sv.stock_id in (select sc2.stock_id  
               from  entrylist  sc2 where sc2.quantity < 0 ) 
               and  sv.stock_id not  in(select sc.stock_id  
               from  entrylist_view  sc
               where sc.document_date >" . $conn->DBDate(strtotime('- 30 day')) . "  
               and sc.quantity < 0 )  
                 
                
                 ";

        if ($visible) {
            $rs = $conn->Execute($sql);

            foreach ($rs as $row) {

                $this->data[$row['item_id'] . '_' . $row['store_id']] = new DataItem($row);
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

        $row->add(new Label('noliqitem', $item->storename));
        $row->add(new Label('noliqstore', $item->itemname));
        $row->add(new Label('qty', Helper::fqty($item->qty)));
    }

   public function oncsv($sender) {
    
        $csv = "";

        foreach ($this->data as $d) {
          
            $csv .= $d->storename . ',';
            $csv .= $d->itemname . ',';
            $csv .= $d->qty  ;
      
         
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

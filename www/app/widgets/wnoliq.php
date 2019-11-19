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


        $sql = "select i.itemname,i.item_id from `items`  i where  i.disabled  <> 1 
               and   i.item_id not  in(select coalesce(sc.item_id,0)   
               from  entrylist_view  sc
               where sc.item_id >0  and sc.document_date >" . $conn->DBDate(strtotime('- 30 day')) . "  
               and sc.quantity < 0 )  
               and  i.item_id    in (select coalesce(sc2.item_id,0) from entrylist_view sc2  ); 
                
                 ";

        if ($visible) {
            $rs = $conn->Execute($sql);

            foreach ($rs as $row) {
                $item = Item::load($row['item_id']);
                $item->qty = $item->getQuantity();
                $this->data[$row['item_id'] ] = $item;
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

     
        $row->add(new Label('itemname', $item->itemname));
        $row->add(new Label('qty', Helper::fqty($item->qty)));
    }

   public function oncsv($sender) {
    
        $csv = "";

        foreach ($this->data as $d) {
          
       
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

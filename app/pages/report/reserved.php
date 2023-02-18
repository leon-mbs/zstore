<?php

namespace App\Pages\Report;

use App\Entity\Item;
use App\Entity\Doc\Document;
use App\Helper as H;
use Zippy\Html\Label;
use Zippy\Html\Panel;

/**
 *  Зарезервированные товары
 */
class Reserved extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('Reserved')) {
            return;
        }
       
        $this->add(new Panel('detail')) ;
 
        $this->detail->add(new Label('preview'));

    
        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
    
    
    
    }
   
    

    private function generateReport() {



         $detail = array();


         $conn = \ZDB\DB::getConnect() ;
        
         $sql = "SELECT i.itemname,ev.document_id,sum(ev.quantity) as qty  FROM entrylist_view ev 
                 JOIN items i ON ev.item_id = i.item_id
                 WHERE  tag = -64 

                 GROUP  BY  i.itemname,ev.document_id
                 ORDER  BY  i.itemname";
        
         $res = $conn->Execute($sql);
        
         foreach($res as $r){
            $row = array();
            $row['itemname']  = $r['itemname'] ;
            $doc = Document::load($r['document_id']);
            $row['store']  = $doc->headerdata['storename'];
            $row['document_number']  = $doc->document_number;
            $row['customer']  = $doc->customer_name;
            $row['qty']  = H::fqty(0-$r['qty']);
            $detail[]=$row;
            
             
         }
        
        
         $header = array(
          '_detail' =>   $detail ,
          'date'=>H::fd(time())
          
        );
      
        
      
        $report = new \App\Report('report/reserved.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

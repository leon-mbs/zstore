<?php

namespace App\Pages\Report;

use App\Entity\Item;
use App\Helper as H;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 * Товары  на  комиссии
 */
class ItemComission extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowReport('ItemComission')) {
            return;
        }
        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');

        $br = "";
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $br = " and  branch_id in ({$brids}) ";
        }        
        
        $clist = \App\Entity\Customer::findArray("customer_name","status=0 and customer_id in (select customer_id from documents_view where  meta_name='GoodsReceipt' and state=5 and  content like '%<comission>1</comission>%'  {$br} )","customer_name") ;
 
        $this->filter->add(new DropDownChoice('customer', $clist, 0));
       
        $this->add(new Panel('detail'))->setVisible(false);

        $this->detail->add(new Label('preview'));

   
    }

    public function OnSubmit($sender) {


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";


        $this->detail->setVisible(true);
    }

    private function generateReport() {
      
        $cust =  $this->filter->customer->getValue() ;
        
        $br = "";
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $br = " and  branch_id in ({$brids}) ";
        }        
      
      
        $where="1=1";
        if($cust >0){
            $where= "    s.customer_id={$cust}   "; 
        }
   
        $conn = $conn = \ZDB\DB::getConnect();
        $this->data = array();
            
        $stard= $conn->GetOne(" select coalesce(min(document_date),'') from documents_view where  meta_name='GoodsReceipt' and state=5 and  content like '%<comission>1</comission>%'  {$br}  ") ;
        if($stard != '') {
            $stard=" and document_date >= '{$stard}'";
        }

        $sql = "
            select s.*   from store_stock_view s
               join  entrylist_view e  on e.stock_id = s.stock_id 
              where  {$where} and e.document_id in (select document_id from documents_view where  meta_name='GoodsReceipt'   and state=5 and  content like '%<comission>1</comission>%'  {$br} ) 
              
             
            order by s.itemname 
        ";
        $stocksid=[];
        
        $detail = array();
        $res = $conn->Execute($sql);
        foreach ($res as $item) {
            $d= \App\Entity\Doc\Document::load($item["document_id"]);
            $det=[];
            $det['itemname'] =  $item['itemname'];
            $det['item_code'] = $item['item_code'];
            $det['price'] = H::fa($item['partion']);
            $det['docs'] = $d->docs;
            
            $sql="select  GROUP_CONCAT(DISTINCT d.document_number SEPARATOR ',') AS docs from entrylist e join documents_view d on e.document_id=d.document_id 
             where  e.quantity > 0 and e.stock_id={$item["stock_id"]} and    meta_name='GoodsReceipt'  {$stard} and state=5 
             group by d.document_number  "    ;
            $det['docs']  =  $conn->GetOne($sql) ;
            $sql="select coalesce(sum(quantity),0 ) from entrylist where quantity >0 and document_id   in (select document_id from documents_view where  meta_name='GoodsReceipt'  {$stard} and state=5) and stock_id=".$item["stock_id"];
            $det['buyqty']  = H::fqty($conn->GetOne($sql) );
            $sql="select coalesce(sum(0-quantity),0 ) from entrylist where quantity < 0 and document_id in (select document_id from documents_view where  meta_name='RetCustIssue'  {$stard}) and stock_id=".$item["stock_id"];
            $det['retqty'] =  H::fqty($conn->GetOne($sql) );
            $sql="select coalesce(sum(0-quantity),0 ) from entrylist where quantity < 0 and document_id not in (select document_id from documents_view where  meta_name='RetCustIssue'  {$stard}) and stock_id=".$item["stock_id"];
            $det['sellqty'] = H::fqty($conn->GetOne($sql) );

            $stocksid[]= $item['stock_id'] ;
            
            $detail[] = $det;
              
        }

        $stocksid[]= 0;
        $ids = implode(',',$stocksid) ;
        if($stard != '') {
            $stard=str_replace( 'document_date','d.document_date', $stard);
        }
      
        $sql="select  d.document_date,coalesce(sum(e.quantity * e.partion),0) as sm, GROUP_CONCAT(DISTINCT d.document_number SEPARATOR ',') AS docs from entrylist_view e join documents_view d on e.document_id=d.document_id 
             where  e.quantity < 0 and e.stock_id in ({$ids})  {$stard}    and e.tag=". \App\Entity\Entry::TAG_SELL ."
             group by d.document_date  
             order by d.document_date  "    ;
       
        $detail2=[];
        
        foreach($conn->Execute($sql) as $row) {
            
        $sql2="select  i.itemname,coalesce(sum(e.quantity  ),0) as sm from entrylist_view e
         join documents_view d on e.document_id=d.document_id 
         join items i on e.item_id=i.item_id 
             where  e.quantity < 0 and e.stock_id in ({$ids}) and d.document_date='{$row['document_date']}'     and e.tag=". \App\Entity\Entry::TAG_SELL ."
             group by i.itemname  
             order by i.itemname  "    ;
             $items=[];
             foreach($conn->Execute($sql2) as $row2) {
                $items[]= array('itemname'=>$row2['itemname'],'sm'=>H::fa( 0-$row2['sm']) );
             }
            
           $detail2[]= array('docs'=>$row['docs'] ,'items'=>$items,  'dt'=> H::fd(strtotime( $row['document_date'])),'sm'=> H::fa( 0-$row['sm'])); 
        }
       
        $header = array(
            "_detail" => $detail,
            "_detail2" => $detail2,
            "dt" => H::fd(time()) ,
            "iscust" => $cust > 0 ,
            "cust" => $cust > 0  ? $this->filter->customer->getValueName()  : ""
            

        );
        $report = new \App\Report('report/itemcomission.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

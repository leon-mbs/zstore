<?php

namespace App\Pages\Report;

 
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\Entity\Doc\Document;
use App\Helper as H;
 
class Toc extends \App\Pages\Base
{
    private $_cci = array();


    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowReport('Toc')) {
            return;
        }

        $this->add(new Form('filter'));
        $this->filter->add(new DropDownChoice('period', [], 1));
        $this->filter->add(new SubmitButton('start' ))->onClick($this, 'OnSubmit');
         
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
        
        $conn = \ZDB\DB::getConnect();
 

        $period = (int)$this->filter->period->getValue();
         
        $end=strtotime('-7 day') ;
        $start=strtotime("-{$period} month",$end) ;
      
        $from = $conn->DBDate($start); 
        $to = $conn->DBDate($end); 
        
        $dd = "document_date >={$from} and document_date <={$to} ";
        
           //актуальность складов
        $detail1=[] ;
        $detail2=[] ;
        $detail3=[] ;
        $detail4=[] ;
        $detail5=[] ;
        
        $items=[];
        $itemsd=[];
        
         
        foreach(Document::findYield("document_date >={$from}  and meta_name='Order' and state >4 ") as $order){
             foreach ($order->unpackDetails('detaildata') as $item) {
                 if(!isset($items[$item->item_id])) {
                    $items[$item->item_id] = ['name'=> $item->itemname,'code'=>$item->item_code??'','amount'=>0];
                 }
                 
                 $sql="select coalesce(sum(quantity),0)  as totqty  from entrylist_view where item_id={$item->item_id} and document_date <=  ". $conn->DBDate($order->document_date) ;
                 $onstore= doubleval($conn->GetOne($sql) );
                 if($onstore < $item->quantity && $item->quantity >0)  {
                     $items[$item->item_id]['amount'] += ($item->quantity - $onstore) * $item->price ; 
                 }
                 
                 
             }
                 //товары с задержкой после  заказа
             $logs = $order->getLogList([9,11,14,20]);
             if(count($logs)>0) {
                 $last= array_shift($logs);
              //   $dt = strtotime("+1 day",  $order->document_date);
                $datetime1 = new \DateTime( date('Y-m-d',$order->document_date)  );
                $datetime2 = new \DateTime( date('Y-m-d',$last->createdon));
                $interval = $datetime1->diff($datetime2);
                $days = $interval->days;
                                               
                if($days > 1) { // обрабатывался  больше двух дней
                     foreach ($order->unpackDetails('detaildata') as $item) {
                         if(!isset($itemsd[$item->item_id])) {
                            $itemsd[$item->item_id] = ['name'=> $item->itemname,'code'=>$item->item_code??'','days'=>[]];
                         }
                         
                         $itemsd[$item->item_id]['days'][]=$days ; 
                         
                     }
                 }
                 
             }
        }
        foreach($items as $item){
           $item['amount'] = H::fa($item['amount']); 
           $detail1[]=$item; 
        }
        
        foreach($itemsd as $item){
           $avg = array_sum($item['days'])/count($item['days']);  
            
           $item['days'] = number_format($avg, 1, '.', '') ; 
           $detail2[]=$item; 
        }
        unset($itemsd);
        unset($items);
        $itemsd=[];
       //задержка с заявки до получения     
    
        foreach(Document::findYield($dd." and meta_name='OrderCust' and state >4 ") as $order){
             $logs = $order->getLogList([9]);
             if(count($logs)>0) {
                $last= array_shift($logs);
           
                $datetime1 = new \DateTime( date('Y-m-d',$order->document_date)  );
                $datetime2 = new \DateTime( date('Y-m-d',$last->createdon));
                $interval = $datetime1->diff($datetime2);
                $days = $interval->days;
                                               
                if($days > 1) { // обрабатывался  больше двух дней
                     foreach ($order->unpackDetails('detaildata') as $item) {
                         if(!isset($itemsd[$item->item_id])) {
                            $itemsd[$item->item_id] = ['name'=> $item->itemname,'code'=>$item->item_code??'','days'=>[]];
                         }
                         
                         $itemsd[$item->item_id]['days'][]=$days ; 
                         
                     }
                 }
                 
             }
        } 
        foreach($itemsd as $item){
           $avg = array_sum($item['days'])/count($item['days']);  
            
           $item['days'] = number_format($avg, 1, '.', '') ; 
           $detail3[]=$item; 
        }      
       
         
         //задержки по  поставщиком  после  оплат        
         $items=[];  
        
         foreach(Document::findYield($dd." and meta_name='InvoiceCust' and state >4 and  payed >= payamount ") as $invoice){
            $p=\App\Entity\Pay::getFirst("document_id=".$invoice->document_id) ;
           
            $datetime1 = new \DateTime( date('Y-m-d',$p->paydate)  );
            
            $gi= Document::getFirst("state > 4 and parent_id=".$invoice->document_id)  ;
            if($gi==null)  continue;
            
            $datetime2 = new \DateTime( date('Y-m-d',$gi->document_date));
            $interval = $datetime1->diff($datetime2);
            $days = $interval->days;
                   
            
            if($days > 1)  
            { 
               if(!isset($items[$invoice->customer_id])) {
                    $items[$invoice->customer_id] = ['name'=> $invoice->customer_name, 'days'=>[]];
               }              
               $items[$invoice->customer_id]['days'][]=$days ; 
               
            }    
         }   
     
        foreach($items as $item){
           $avg = array_sum($item['days'])/count($item['days']);  
            
           $item['days'] = number_format($avg, 1, '.', '') ; 
           
           $detail4[]=$item; 
        }
     
        // неликвиды

        $sql = "select  st.item_id,st.itemname, st.item_code, coalesce(sum(st.qty*st.partion)) as am from  store_stock_view  st 
               where st.itemdisabled <> 1  and  st.qty >0 
                and   st.stock_id not  in(select   stock_id    
               from  entrylist_view  
               where    document_date >={$from}   and  quantity < 0  AND stock_id  IS  NOT  null) 
               and   st.stock_id    in(select   stock_id    
               from  entrylist_view  
               where    document_date < {$from}  and  quantity > 0  AND stock_id  IS  NOT  null) 
                
               group by st.item_id,st.item_code,st.itemname 
               order by st.itemname
                 ";

        
        $res = $conn->Execute($sql);
        foreach ($res as $item) {
            
            if(doubleval($item['am']) <=0){
                continue;
            }
            $item['amount']  = H::fa($item['am']) ;
              
            $detai5[] = $item;
      

        }     
     
     
        //todo убрать =  
        $header = array(
           "_detail1" => $detail1,
           "isdetail1" => count($detail1) >= 0,
           "_detail2" => $detail2,
           "isdetail2" => count($detail2) >= 0,
          "_detail3" => $detail3,
           "isdetail3" => count($detail3) >= 0, 
          "_detail4" => $detail4,
           "isdetail4" => count($detail4) >= 0, 
           "_detail5" => $detai5,
           "isdetail5" => count($detail5) >= 0 


                        
        );
        $report = new \App\Report('report/toc.tpl');

        $html = $report->generate($header);

        return $html;
    }

 

}

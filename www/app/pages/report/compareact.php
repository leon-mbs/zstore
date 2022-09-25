<?php

namespace App\Pages\Report;

use App\Entity\Item;
use App\Helper as H;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 *  Акт сверки
 */
class CompareAct extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('CompareAct')) {
            return;
        }


        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new DropDownChoice('type', array( ), 0))->onChange($this, "OnType");
        $this->filter->add(new DropDownChoice('cust', array() , 0));

        
        $this->add(new Panel('detail'))->setVisible(false);
 
        $this->detail->add(new Label('preview'));
    }
   
    
    public function OnType($sender) {
         $type = $this->filter->type->getValue();
         $this->filter->cust->setValue(0);
         
         $list = array();
         $this->filter->cust->setValue(0);

         if($type==1) {
             $list = \App\Entity\Customer::findArray("customer_name","status=0 and  customer_id  in (select coalesce(customer_id,0) as  id from documents_view  where  meta_name  in('Invoice','GoodsIssue','Order','POSCheck'))","customer_name");
         }
         if($type==2) {
             $list = \App\Entity\Customer::findArray("customer_name","status=0 and  customer_id  in (select coalesce(customer_id,0) as  id from documents_view  where  meta_name  in('InvoiceCust','GoodsReceipt','IncomeService'))","customer_name");
         }


         $this->filter->cust->setOptionList($list);
        
         
         $this->detail->setVisible(false);         
         
    }    
    
    public function OnSubmit($sender) {


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

 
        $this->detail->setVisible(true);
    }

    private function generateReport() {

        $type = $this->filter->type->getValue();

        $cust_id = $this->filter->cust->getValue();


         $detail = array();
        
        
if($type==1){

        $list = \App\Entity\Doc\Document::find(" customer_id= {$cust_id} and    state NOT IN (0, 1, 2, 3, 15, 8, 17) " , "  document_id asc",-1,-1,"*, COALESCE( ((CASE WHEN (meta_name IN ('GoodsIssue', 'TTN', 'PosCheck', 'OrderFood', 'ServiceAct')) THEN payamount WHEN ((meta_name = 'OutcomeMoney') AND      (content LIKE '%<detail>1</detail>%')) THEN payed WHEN (meta_name = 'ReturnIssue') THEN payed ELSE 0 END)), 0) AS b_passive,  COALESCE( ((CASE WHEN (meta_name IN ('GoodsIssue', 'Order', 'PosCheck', 'OrderFood', 'Invoice', 'ServiceAct')) THEN payed WHEN ((meta_name = 'IncomeMoney') AND      (content LIKE '%<detail>1</detail>%')) THEN payed WHEN (meta_name = 'ReturnIssue') THEN payamount ELSE 0 END)), 0) AS b_active");
  
        $bal=0;

        foreach ($list as $id=>$d) {
            if($d->b_active != $d->b_passive ){
                
                 $r  = array();

                 $r['meta_desc'] = $d->meta_desc;
                 $r['document_number'] = $d->document_number;
                 $r['document_date'] = H::fd($d->document_date);
                 $r['active'] = H::fa($d->b_active);
                 $r['passive'] = H::fa($d->b_passive);

                 $diff = $d->b_passive - $d->b_active;
        
                 $bal +=  $diff;        
                 $r['bal'] = H::fa( $bal);

                 $detail[] = $r; 
                 if($bal==0){
                    // $detail = array();  
                 }                                
            }
             
        }
    
}        
        
if($type==2){

        $list = \App\Entity\Doc\Document::find("   customer_id= {$cust_id} and    state NOT IN (0, 1, 2, 3, 15, 8, 17) " , "document_id asc ",-1,-1,"*,  COALESCE( ((CASE WHEN (meta_name IN ('InvoiceCust', 'GoodsReceipt', 'IncomeService', 'OutcomeMoney')) THEN payed WHEN ((meta_name = 'OutcomeMoney') AND      (content LIKE '%<detail>2</detail>%')) THEN payed WHEN (meta_name = 'RetCustIssue') THEN payamount ELSE 0 END)), 0) AS s_passive,  COALESCE( ((CASE WHEN (meta_name IN ('GoodsReceipt','IncomeService') ) THEN payamount WHEN ((meta_name = 'IncomeMoney') AND      (content LIKE '%<detail>2</detail>%')) THEN payed WHEN (meta_name = 'RetCustIssue') THEN payed ELSE 0 END)), 0) AS s_active ");

        $bal=0;

        foreach ($list as $id=>$d) {
            if($d->s_active != $d->s_passive ){
                
                 $r  = array();

                 $r['meta_desc'] = $d->meta_desc;
                 $r['document_number'] = $d->document_number;
                 $r['document_date'] = H::fd($d->document_date);
                 $r['active'] = H::fa($d->s_active);
                 $r['passive'] = H::fa($d->s_passive);

                 $diff = $d->s_passive - $d->s_active;
        
                 $bal +=  $diff;        
                 $r['bal'] = H::fa( $bal);

                 $detail[] = $r; 
                 if($bal==0){
                    // $detail = array();  
                 }                                
            }
             
        }
    
}        
        
        
        
         $header = array(
          'date' => H::fd(time()) ,
          'cust' => $this->filter->cust->getValueName() ,
          'firm' => 'Firma' ,
          '_detail' =>   $detail
          
        );
      
        
        
        $conn = \ZDB\DB::getConnect();

        $report = new \App\Report('report/compareact.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

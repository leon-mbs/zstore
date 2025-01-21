<?php

namespace App\Pages\Report;

use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Customer;
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

        $this->filter->add(new \Zippy\Html\Form\AutocompleteTextInput('cust'))->onText($this, 'OnAutoCustomer');
        $this->filter->add(new Date('from', strtotime("-1 month", time())));
        $this->filter->add(new Date('to', time()));


        $this->add(new Panel('detail'))->setVisible(false);

        $this->detail->add(new Label('preview'));
    }

    public function OnAutoCustomer($sender) {
        
        $conn = \ZDB\DB::getConnect();
        $search=  trim($sender->getText());
        if(strlen($search)==0) {
            return [];
        }
        
        $search = $conn->qstr('%'. $search.'%');
        
        $where = "status=0 and customer_name like {$search}  AND detail not like '%<isholding>1</isholding>%'    ";

        return Customer::findArray("customer_name", $where, "customer_name");
 
    }
 
    public function OnSubmit($sender) {


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";


        $this->detail->setVisible(true);
    }

    private function generateReport() {
        $conn = \ZDB\DB::getConnect();

        $from = $conn->DBDate($this->filter->from->getDate());
        $to = $conn->DBDate($this->filter->to->getDate());
       
        $cust_id = $this->filter->cust->getKey();

        if($cust_id ==0) {
            $this->setError('Не вибраний  контрагент') ;
            return;
        }
 
        $detail = array();
        
        $sql="select coalesce(sum(amount),0) from custacc_view where optype in (2,3) and  customer_id= {$cust_id} and createdon < {$from} "; 
        $bal = $conn->GetOne($sql) ;
        $r  = array();

        $r['meta_desc'] = '';
        $r['document_number'] = '';
        $r['document_date'] = '';
        $r['active'] = '';
        $r['passive'] = '';

        $r['bal'] = H::fa($bal);

        $detail[] = $r;
        
        $br="";
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
           $br=" and dv.branch_id in ({$brids}) ";
        }
             
        
         
        $sql =  "select 
             SUM(CASE WHEN cv.amount > 0  THEN cv.amount ELSE 0 END) AS active,
             SUM(CASE WHEN cv.amount < 0  THEN 0 - cv.amount ELSE 0 END) AS passive,
            cv.document_id,cv.document_number,cv.createdon,dv.meta_desc

             FROM custacc_view cv
             JOIN documents_view dv 
             ON cv.document_id = dv.document_id 
             WHERE  cv.customer_id={$cust_id} {$br}
            AND optype IN (2,3)  and createdon >={$from} and createdon <={$to}  
            GROUP BY cv.document_id,cv.document_number,cv.createdon
            ORDER  BY  cv.document_id ";
     
        foreach ( $conn->Execute($sql) as $d) {
           
               
            
                $r  = array();

                $r['meta_desc'] = $d['meta_desc'];
                $r['document_number'] = $d['document_number'];
                $r['document_date'] = H::fd( strtotime($d['createdon'] ));
                $r['active'] = H::fa($d['active']);
                $r['passive'] = H::fa($d['passive']);

                $diff = $d['active'] - $d['passive'];

                $bal +=  $diff;
                $r['bal'] = H::fa($bal);
                $r['pays'] = $this->getPayments($d['document_id']) ;
                $r['notes'] = $d->notes ?? '';

                $detail[] = $r;
                
        }

 
        $header = array(
           'date' => H::fd(time()) ,
           'cust' => $this->filter->cust->getText() ,
           '_detail' =>   $detail

        );
 
        $conn = \ZDB\DB::getConnect();

        $report = new \App\Report('report/compareact.tpl');

        $html = $report->generate($header);

        return $html;
    }

    private function getPayments( $did){
        $doc = Document::load($did) ;
        $pays = [];
        foreach(\App\Entity\Pay::find("document_id={$doc->document_id} and paytype < 1000","pl_id asc") as $pay){
            $pays[]= H::fd($pay->paydate)." ".H::fa($pay->amount) ." ".$pay->mf_name ;
        }
        if(count($pays) >0) {
           return implode('; ',$pays);                      
        }
        if($doc->parent_id >0) {   //предоплата
            $d=  Document::load($doc->parent_id);
            
            $ret=$d->document_number;
            if(strlen($d->customer_name) >0) {
               $ret = $ret .' '.$d->customer_name;    
            }
            $p = [];
            foreach(\App\Entity\Pay::find("document_id={$d->document_id} and paytype < 1000","pl_id asc") as $pay){
                 $p[]= H::fd($pay->paydate)." ".H::fa($pay->amount) ." ".$pay->mf_name ;
            }
            if(count($p) >0) {
               $ret = $ret .'. ('.implode('; ',$p).')';    

            }
            
            return $ret;
        }     
        $ch = $doc->getChildren() ;
        if(count($ch)>0) { //постоплата
           foreach($ch as $d) {
               
                $ret=$d->document_number;
                if(strlen($d->customer_name) >0) {
                   $ret = $ret .' '.$d->customer_name;    
                }
                $p = [];
                foreach(\App\Entity\Pay::find("document_id={$d->document_id} and paytype < 1000","pl_id asc") as $pay){
                     $p[]= H::fd($pay->paydate)." ".H::fa($pay->amount) ." ".$pay->mf_name ;
                }
                if(count($p) >0) {
                   $ret = $ret .'. ('.implode('; ',$p).')';    

                }               
               
                 
              $pays[]= $ret;
           }
           return implode('; ',$pays);      
        }
    }
            
}

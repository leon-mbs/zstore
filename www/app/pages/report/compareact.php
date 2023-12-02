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

        $this->filter->add(new \Zippy\Html\Form\AutocompleteTextInput('cust'))->onText($this, 'OnAutoCustomer');;
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
        
        $where = "status=0 and customer_name like {$search} ";

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
 
        $c = Customer::load($cust_id) ;
        if($c->isholding==1) {
           $list =  $c->getChillden();
        }  else {
           $list[] = $cust_id;    
        }
         
        if(count($list) ==0) {
            $this->setError('Не вибраний  контрагент') ;
            return;
        }
        $clist = implode(',',$list) ;
        $detail = array();
        
        $where_start =  " document_date <{$from}  and    customer_id  in({$clist})  and    state NOT IN (0, 1, 2, 3, 15, 8, 17) ";
        $bal=0;
        foreach (\App\Entity\Doc\Document::findYield($where_start, "document_date asc,document_id asc ", -1, -1) as $d) {
           
            $ch = Customer::balans($d,true);
         
            if($ch===true) {
                continue;
            }
            
            $diff = $ch['passive'] - $ch['active'];

            $bal +=  $diff;
           
                
        }        
        
        $r  = array();

        $r['meta_desc'] = '';
        $r['document_number'] = '';
        $r['document_date'] = '';
        $r['active'] = '';
        $r['passive'] = '';

        $r['bal'] = H::fa($bal);

        $detail[] = $r;
         
        $where =  " document_date >={$from} and document_date <={$to} and   customer_id  in({$clist})  and    state NOT IN (0, 1, 2, 3, 15, 8, 17) ";
     
        foreach (\App\Entity\Doc\Document::findYield($where, "document_date asc ", -1, -1) as $d) {
           
                $ch = Customer::balans($d);
            
                if($ch===true) {
                    continue;
                }
                if($ch['active']==$ch['passive']) {
                  //  continue;
           
                }
            
            
                $r  = array();

                $r['meta_desc'] = $d->meta_desc;
                $r['document_number'] = $d->document_number;
                $r['document_date'] = H::fd($d->document_date);
                $r['active'] = H::fa($ch['active']);
                $r['passive'] = H::fa($ch['passive']);

                $diff = $ch['active'] - $ch['passive']  ;

                $bal +=  $diff;
                $r['bal'] = H::fa($bal);
                $r['pays'] = $this->getPayments($d) ;
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

    private function getPayments(Document $doc){
        $pays = [];
        foreach(\App\Entity\Pay::find("document_id={$doc->document_id} and paytype < 1000","pl_id asc") as $pay){
            $pays[]= H::fd($pay->paydate)." ".H::fa($pay->amount) ." ".$pay->mf_name ;
        }
        if(count($pays) >0) {
           return implode('; ',$pays);                      
        }
        if($doc->parent_id >0) {   //предоплата
            $d=  Document::load($doc->parent_id);
            return $d->document_number;
        }     
        $ch = $doc->getChildren() ;
        if(count($ch)>0) { //постоплата
           foreach($ch as $d) {
              $pays[]=   $d->document_number;
           }
           return implode('; ',$pays);      
        }
    }
            
}

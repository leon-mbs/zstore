<?php

namespace App\Pages\Report;

use App\Helper as H;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;
use App\Entity\Pay;
use App\Entity\Item;

/**
 * Управоенческий  баланс
 */
class Balance extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('Balance')) {
            return;
        }
        $dt = new \App\DateTime();
      
        $dt = $dt->startOfMonth()->getTimestamp();
  
    
        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('dt', $dt));
  
      

        $this->add(new Panel('detail'))->setVisible(false);

        $this->detail->add(new Label('preview'));

        $this->OnSubmit($this->filter) ;
    }

    public function OnSubmit($sender) {


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

        $this->detail->setVisible(true);

    }

    private function generateReport() {
        $dt = $this->filter->dt->getDate();
         
        $conn= \ZDB\DB::getConnect() ;
        $dbdt = $conn->DBDate($dt);
        
        $brdoc = "";
        $brf = "";
        $brst = "";
        $bemp = "";
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $brst = "   store_id in( select store_id from  stores where  branch_id in ({$brids})  )  and ";

            $brf = " and branch_id in ({$brids}) ";
            $bemp = " and branch_id in ({$brids}) ";
            $brdoc = " and  document_id in(select  document_id from  documents where branch_id in ({$brids}) )";
        }

        $stview ="SELECT  SUM( (SELECT COALESCE(SUM(quantity), 0)  FROM entrylist_view
          WHERE entrylist_view.stock_id = store_stock_view.stock_id and date(document_date) < {$dbdt} ) * partion)  from store_stock_view ";

        $sql = $stview. " where {$brst} item_type=   ".Item::TYPE_MAT;
        $amat = doubleval($conn->GetOne($sql)) ;
        $sql = $stview. " where {$brst}  (item_type=   ".Item::TYPE_PROD ."  or  item_type=   ".Item::TYPE_HALFPROD .")";
        $aprod = doubleval($conn->GetOne($sql)) ;
        $sql = $stview. " where {$brst}  item_type=   ".Item::TYPE_MBP;
        $ambp = doubleval($conn->GetOne($sql)) ;
        $sql = $stview. " where {$brst}  item_type=   ".Item::TYPE_TOVAR;
        $aitem = doubleval($conn->GetOne($sql)) ;
        $sql = $stview. " where {$brst}  coalesce(item_type,0)=0  ";
        $aother = doubleval($conn->GetOne($sql)) ;
 
        $sql = "select coalesce(sum(amount),0)  from paylist_view where date(paydate) < {$dbdt} and paytype <=1000 and mf_id  in (select mf_id  from mfund where detail not like '%<beznal>1</beznal>%' {$brf})";
        $anal = doubleval($conn->GetOne($sql)) ;

        $sql = "select coalesce(sum(amount),0)  from paylist_view where date(paydate) < {$dbdt} and paytype <=1000 and mf_id  in (select mf_id  from mfund where detail like '%<beznal>1</beznal>%' {$brf})";
        $abnal = doubleval($conn->GetOne($sql)) ;

        $aemp=0;
        $pemp=0;
        $sql = "select coalesce(sum(amount),0) as am  from empacc_view where date(createdon) <  {$dbdt}  {$bemp} group by emp_id ";

        foreach($conn->GetCol($sql) as $r ) {
           if($r >0) {
             $pemp += $r;      
           } 
           if($r < 0) {
             $aemp += abs($r);      
           } 
            
        }
        
        $cust_acc_view = \App\Entity\CustAcc::get_acc_view($dt)  ;
          
        //к оплате
        $sql = "SELECT COALESCE( SUM(   a.s_active - a.s_passive    ) ,0) AS d   FROM ({$cust_acc_view}) a where  a.s_active > a.s_passive   ";
        $sum = doubleval($conn->GetOne($sql));
        $sql = "SELECT COALESCE( SUM(   a.b_active - a.b_passive    ) ,0) AS d   FROM ({$cust_acc_view}) a where  a.b_active > a.b_passive   ";
        $sum += doubleval($conn->GetOne($sql));
        $credit = H::fa($sum);
        
        //ожидается  оплата
        $sql = "SELECT COALESCE( SUM( a.s_passive -  a.s_active      ) ,0) AS d   FROM ({$cust_acc_view}) a where  a.s_active < a.s_passive   ";
        $sum = doubleval($conn->GetOne($sql));
        $sql = "SELECT COALESCE( SUM(  a.b_passive -  a.b_active      ) ,0) AS d   FROM ({$cust_acc_view}) a where  a.b_active < a.b_passive   ";
        $sum += doubleval($conn->GetOne($sql));
        $debet = H::fa($sum);


        $aeq=0;
        foreach(\App\Entity\Equipment::find("") as $eq){
            $aeq += doubleval($eq->getBalance($dt));
        }
        
 
        $amat = H::fa($amat);
        $aprod = H::fa($aprod);
        $ambp = H::fa($ambp);
        $aitem = H::fa($aitem);
        $aother = H::fa($aother);
        $anal = H::fa($anal);
        $abnal = H::fa($abnal);
        $aemp = H::fa($aemp);
        $pemp = H::fa($pemp);
 
        $aeq = H::fa($aeq);
        
        $atotal = $amat + $aprod + $ambp + $aitem +$aother + $anal + $abnal + $aemp+  doubleval($debet);
        $ptotal = H::fa( doubleval($pemp) +   doubleval($credit) );
        $bal = $atotal - $ptotal ;


        $header = array(
            'datefrom' => \App\Helper::fd($dt) ,
            'amat'      => $amat != 0 ? $amat : false,
            'aprod'     => $aprod !=0 ? $aprod : false,
            'ambp'      => $ambp !=0 ? $ambp : false,
            'aitem'     => $aitem !=0 ? $aitem : false,
            'aother'    => $aother !=0 ? $aother : false,
            'anal'      => $anal !=0 ? $anal : false,
            'abnal'     => $abnal !=0 ? $abnal : false,
            'aemp'      => $aemp !=0 ? $aemp : false,
            'pemp'      => $pemp !=0 ? $pemp : false,
            'debet'      => $debet >0 ? $debet : false,
            'credit'      => $credit >0 ? $credit : false,
            'aeq'      => $aeq !=0 ? $aeq : false,

            'atotal'    => H::fa($atotal) ,
            'ptotal'    => H::fa($ptotal) ,
            'bal'       => H::fa($bal)

        );

 
        $report = new \App\Report('report/balance.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

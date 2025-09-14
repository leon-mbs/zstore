<?php

namespace App\Pages\Report;

use App\Entity\Item;
use App\Helper as H;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\Date;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 * закрытие  дня
 */
class EndDay extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowReport('EndDay')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');

        $this->filter->add(new Date('from', time() ));
   
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

        $user = \App\System::getUser()  ;
        $from = $this->filter->from->getDate();
        $conn =  \ZDB\DB::getConnect();
        $date = $conn->DBDate($from) ;
     
      
        $usr = '';
        $brf = '';
     
        if($user->rolename!='admins') {
           $usr = " and user_id=" . $user->user_id ;  
        }
       
      
        $brids = \App\ACL::getBranchIDsConstraint();
    
        if (strlen($brids) > 0) {
            $brf = " and  mf_id in (select mf_id from mfund where   branch_id in ({$brids})  )";
        }  
        $detail=[];
        $detail2=[];

        $sql = "SELECT
              COALESCE(SUM( case when amount > 0 then amount else 0 end   ), 0) as dt ,
              COALESCE(SUM( case when amount < 0 then 0-amount else 0 end   ), 0) as ct,
               
              mf_name,
              username
            FROM paylist_view
            WHERE paytype <= 1000  and paydate={$date} {$brf} {$usr}  
            GROUP BY username,
                     mf_name
            ORDER BY username, mf_name ";

        
        foreach($conn->Execute($sql) as $row){
            $detail[]=array(
              'username'=>$row['username'],
              'mf_name'=>$row['mf_name'],
              'ct'=> H::fa( $row['ct']),
              'dt'=> H::fa( $row['dt']),
            );
        }
        
      $begins=[];
 
 
       $sql = "SELECT
              COALESCE(SUM( amount  ), 0) sm ,
           
               
              mf_name 
              
            FROM paylist_view
            WHERE paytype <= 1000  and paydate<{$date} {$brf}
            GROUP BY  
                     mf_name
              ";

        
        
        
        
        foreach($conn->Execute($sql) as $row){
            $begins[$row['mf_name']] =  $row['sm']   ;
        } 
 
 
      $sql = "SELECT
              COALESCE(SUM( case when amount > 0 then amount else 0 end   ), 0) as dt ,
              COALESCE(SUM( case when amount < 0 then 0-amount else 0 end   ), 0) as ct,
               
              mf_name 
              
            FROM paylist_view
            WHERE paytype <= 1000  and paydate={$date} {$brf}
            GROUP BY  
                     mf_name
            ORDER BY   mf_name ";

        
        
        
        
        foreach($conn->Execute($sql) as $row){
            $b = $begins[$row['mf_name']];
            $detail2[]=array(
           
              'mf_name'=>$row['mf_name'],
              'b'=> H::fa( $b),
              'ct'=> H::fa( $row['ct']),
              'dt'=> H::fa( $row['dt']),
              'e'=> H::fa( $b+$row['dt']-$row['ct'] ) 
            );
        } 
        
        $header = array(
            "showmf" => $user->rolename=='admins',
            "_detail" => $detail,
            "_detail2" => $detail2,
            'date'    =>   H::fd($from)
        );
        $report = new \App\Report('report/endday.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

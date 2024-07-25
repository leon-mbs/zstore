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

/**
 * Доходы и расходы
 */
class PayBalance extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('PayBalance')) {
            return;
        }

        $dt = new \App\DateTime();
        $dt->subMonth(1);

        $from = $dt->startOfMonth()->getTimestamp();
        $to = $dt->endOfMonth()->getTimestamp();

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');

        $this->filter->add(new Date('from', $from));
        $this->filter->add(new Date('to', $to));
        $this->filter->add(new CheckBox('showdet'));

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


        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();
        $det = $this->filter->showdet->isChecked();



        $detail = array();
        $detail2 = array();

        // $cstr = \App\Acl::getMFBranchConstraint();
        // if (strlen($cstr) > 0) {
        //     $cstr = "  mf_id in ({$cstr}) and ";
        //  }


        $brpay = "";
        $brst = "";
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $brst = " and   store_id in( select store_id from  stores where  branch_id in ({$brids})  ) ";

            $brpay = " and  document_id in(select  document_id from  documents where branch_id in ({$brids}) )";
        }


        $pl = \App\Entity\IOState::getTypeList();

        $conn = \ZDB\DB::getConnect();

        $sql = " 
         SELECT   iotype,coalesce(sum(amount),0) as am   FROM iostate_view 
             WHERE    
              iotype <30   {$brpay}
              AND document_date  >= " . $conn->DBDate($from) . "
              AND  document_date  <= " . $conn->DBDate($to) . "
              GROUP BY  iotype order  by  iotype  
                         
        ";

        $rs = $conn->Execute($sql);
        $tin = 0;
        foreach ($rs as $row) {
            $detailitem = array();

            $detailitem["in"]   = H::fa($row['am']);
            $detailitem["type"] = $pl[$row['iotype'] ] ;
            $detailitem["docdet"] = false ;
            if($det) {

                $sqldet = " 
                 SELECT  meta_desc,coalesce(sum(i.amount),0) as detam  
                     FROM iostate i join documents_view d on i.document_id=d.document_id
                     WHERE    
                      iotype = {$row['iotype']}
                      AND d.document_date  >= " . $conn->DBDate($from) . "
                      AND  d.document_date  <= " . $conn->DBDate($to) . "
                      GROUP BY  meta_desc order  by  meta_desc  
                                 
                ";
                $rsdet = $conn->Execute($sqldet);
                $detailitem["docdet"] =array();
                foreach ($rsdet as $rowdet) {
                    $detailitem["docdet"][]= array('docdesc'=>  $rowdet['meta_desc'] ,'indet'=>H::fa($rowdet['detam']) );

                }


            }
            $detail[]= $detailitem;
            $tin += $row['am'];
        }

        $sql = " 
         SELECT   iotype,coalesce(sum(amount),0) as am   FROM iostate_view 
             WHERE   
              iotype >= 50 and  iotype < 80    {$brpay}
              AND document_date  >= " . $conn->DBDate($from) . "
              AND  document_date  <= " . $conn->DBDate($to) . "
              GROUP BY  iotype order  by  iotype  
                         
        ";

        $rs = $conn->Execute($sql);
        $tout = 0;
        foreach ($rs as $row) {
            $detailitem = array();

            $detailitem["out"]   = H::fa(0-$row['am']);
            $detailitem["type"] = $pl[$row['iotype'] ] ;
            $detailitem["docdet"] = false ;
            if($det) {

                $sqldet = " 
                     SELECT  meta_desc,coalesce(sum(i.amount),0) as detam  
                         FROM iostate i join documents_view d on i.document_id=d.document_id
                         WHERE    
                          iotype = {$row['iotype']}
                          AND d.document_date  >= " . $conn->DBDate($from) . "
                          AND  d.document_date  <= " . $conn->DBDate($to) . "
                          GROUP BY  meta_desc order  by  meta_desc  
                                     
                    ";
                $rsdet = $conn->Execute($sqldet);
                $detailitem["docdet"] =array();
                foreach ($rsdet as $rowdet) {
                    $detailitem["docdet"][]= array('docdesc'=>  $rowdet['meta_desc'] ,'indet'=>H::fa(0-$rowdet['detam']) );

                }


            }

            $detail2[]= $detailitem;
            $tout +=  (0-$row['am']);
        }

        $total = $tin - $tout;

        $detail3=[];
        $sql = " 
         SELECT   iotype,coalesce(sum(amount),0) as am   FROM iostate_view 
             WHERE   
              iotype in (30,31,90,81)     {$brpay}
              AND document_date  >= " . $conn->DBDate($from) . "
              AND  document_date  <= " . $conn->DBDate($to) . "
              GROUP BY  iotype    
                         
        ";

        $rs = $conn->Execute($sql);        
        
        foreach ($rs as $row) {
            $detailitem = array();
            $detailitem["out"]   = H::fa(0-$row['am']);
            $detailitem["type"] = $pl[$row['iotype'] ] ;
            
            
            $detail3[]= $detailitem;
        }        
        
        $header = array(
            'datefrom' => \App\Helper::fd($from),
            'dateto'   => \App\Helper::fd($to),
            "_detail"  => $detail,
            "_detail2" => $detail2,
            "_detail3" => $detail3,
            "is3" => count($detail3) >0,
            'tin'      => H::fa($tin),
            'tout'     => H::fa($tout),
            'total'    => H::fa($total)
        );

 
        $report = new \App\Report('report/paybalance.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

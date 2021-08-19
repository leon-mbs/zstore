<?php

namespace App\Pages\Report;

use App\Helper as H;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;
use \App\Entity\Pay;

/**
 * Платежный  баланс
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

        $this->add(new Panel('detail'))->setVisible(false);
        $this->detail->add(new \Zippy\Html\Link\BookmarkableLink('print', ""));

        $this->detail->add(new RedirectLink('word', "mfreport"));
        $this->detail->add(new RedirectLink('excel', "mfreport"));
        $this->detail->add(new RedirectLink('pdf', "mfreport"));
        $this->detail->add(new Label('preview'));
        \App\Session::getSession()->issubmit = false;
    }

    public function OnSubmit($sender) {


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

        $reportpage = "App/Pages/ShowReport";
        $reportname = "mfreport";

        $this->detail->word->pagename = $reportpage;
        $this->detail->word->params = array('doc', $reportname);
        $this->detail->excel->pagename = $reportpage;
        $this->detail->excel->params = array('xls', $reportname);
        $this->detail->pdf->pagename = $reportpage;
        $this->detail->pdf->params = array('pdf', $reportname);

        $this->detail->setVisible(true);

        \App\Session::getSession()->printform = "";
        \App\Session::getSession()->issubmit = true;
    }

    private function generateReport() {


        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();

        $tin = 0;
        $tout = 0;
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
              iotype <50   {$brpay}
              AND document_date  >= " . $conn->DBDate($from) . "
              AND  document_date  <= " . $conn->DBDate($to) . "
              GROUP BY  iotype order  by  iotype  
                         
        ";

        $rs = $conn->Execute($sql);

        foreach ($rs as $row) {
            $detail[] = array(
                "in"   => H::fa($row['am']),
                "type" => $pl[$row['iotype']]
            );
            $tin += $row['am'];
        }

        $sql = " 
         SELECT   iotype,coalesce(sum(amount),0) as am   FROM iostate_view 
             WHERE   
              iotype >= 50    {$brpay}
              AND document_date  >= " . $conn->DBDate($from) . "
              AND  document_date  <= " . $conn->DBDate($to) . "
              GROUP BY  iotype order  by  iotype  
                         
        ";

        $rs = $conn->Execute($sql);

        foreach ($rs as $row) {
            $detail2[] = array(
                "out"  => 0 - H::fa($row['am']),
                "type" => $pl[$row['iotype']]
            );
            $tout += 0 - $row['am'];
        }

        $total = $tin - $tout;

        $header = array(
            'datefrom' => \App\Helper::fd($from),
            'dateto'   => \App\Helper::fd($to),
            "_detail"  => $detail,
            "_detail2" => $detail2,
            'tin'      => H::fa($tin),
            'tout'     => H::fa($tout),
            'total'    => H::fa($total)
        );

        $sql = " 
         SELECT   coalesce(sum(abs(amount)),0)  as am   FROM iostate_view 
             WHERE   
              iotype  = " . \App\Entity\IOState::TYPE_BASE_OUTCOME . "   {$brpay}
              AND document_date  >= " . $conn->DBDate($from) . "
              AND  document_date  <= " . $conn->DBDate($to) . "
             
                         
        ";

        $OPOUT = $conn->GetOne($sql); // переменные расходы

        $sql = " 
         SELECT   coalesce(  sum(abs(amount)),0)  as am   FROM iostate_view 
             WHERE   
              iotype  = " . \App\Entity\IOState::TYPE_BASE_INCOME . "   {$brpay}
              AND document_date  >= " . $conn->DBDate($from) . "
              AND  document_date  <= " . $conn->DBDate($to) . "
             
                         
        ";

        $OPIN = $conn->GetOne($sql); // операционный доход

        $header['tu'] = H::fa($OPIN - $OPOUT);    //проход
        $header['tvc'] = H::fa($OPOUT);   //переменные затраты
        $header['OP'] = H::fa($tout - $OPOUT);  //операционные расходы
        $header['PR'] = H::fa($header['tu'] - $header['OP']);  // прибыль

        $inv = 0;

        foreach (\App\Entity\Equipment::find('disabled<>1') as $oc) {
            if ($oc->balance > 0) {
                $inv += $oc->balance;
            }
        }
        $sql = " 
         SELECT   coalesce(  sum(partion),0)     FROM store_stock 
             WHERE qty <> 0    {$brst}
              
                         
        ";

        $amount = $conn->GetOne($sql); //ТМЦ  на складах       

        if ($amount > 0) {
            $inv += $amount;
        }

        $header['isinv'] = false;
        if ($inv > 0) {
            $header['isinv'] = true;
            $header['inv'] = H::fa($inv);
            $header['ROI'] = round((($header['tu'] - $header['OP']) / $inv) * 100);
        }

        $header['isinv'] = $header['PR'] > 0;

        $report = new \App\Report('report/paybalance.tpl');

        $html = $report->generate($header);

        return $html;
    }

}
 
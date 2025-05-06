<?php

namespace App\Pages\Report;

use App\Entity\Doc\Document;
use App\Entity\ProdStage;
use App\Entity\Employee;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 * Оплата  по  производству
 */
class EmpTask extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowReport('EmpTask')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));

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

        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();
        $brids = \App\ACL::getBranchIDsConstraint();
 
       $be="";
       if (strlen($brids) > 0) {
          $be = " and document_id in(select document_id from documents where branch_id in ({$brids}) )   ";
       }  
       $sql = "select coalesce( abs ( sum(amount)),0) as am,emp_id from  empacc_view  where  optype = 104 {$be} AND DATE(createdon) >= {$from}   AND DATE(createdon) <= " .$to . "  group by  emp_id   ";
       $etasklist=[];
       
       foreach($conn->Execute($sql) as $r){
          $etasklist[$r['emp_id']]  =  $r['am'];
       }
 
 
 
        $elist = Employee::find("", "emp_name");
        foreach ($elist as $emp_id => $emp) {
            $emp->cnt = 0;
            $emp->hours = 0;
            $emp->amount = $etasklist[$emp_id] ??0;
        }

        $detail = array();
        $conn = \ZDB\DB::getConnect();

        $where = "   meta_name='Task'   
              AND   document_date  >= " . $conn->DBDate($from) . "
              AND   document_date  <= " . $conn->DBDate($to) . " and state= " . Document::STATE_CLOSED;


        foreach (Document::findYield($where) as $doc) {


            $emplist = $doc->unpackDetails('emplist');
            if (count($emplist) == 0) {
                continue;
            }
           
            $hours = 0;
            foreach ($doc->unpackDetails('detaildata') as $service) {
            
                $hours += (doubleval($ser->hours??0) * doubleval($service->quantity??0));
            }
            if (($doc->headerdata['hours'] ??0 ) > 0) {
                $hours = $doc->headerdata['hours'];
            }
   

            foreach ($emplist as $emp) {


              //  $elist[$emp->employee_id]->amount += round($total * $emp->ktu);
                $elist[$emp->employee_id]->hours += $hours;
                $elist[$emp->employee_id]->cnt += 1;
            }
        }

        foreach ($elist as $emp_id => $emp) {
            if ($emp->cnt > 0) {
                $detail[] = array(
                    "name"   => $emp->emp_name,
                    "cnt"    => $emp->cnt>0 ? $emp->cnt : '',
                    "hours"  => $emp->hours>0 ? $emp->hours : '',
                    "amount" => \App\Helper::fa($emp->amount)
                );
            }
        }
 
        $header = array('datefrom' => \App\Helper::fd($from),
                        "_detail"  => $detail,
                     
                        'dateto'   => \App\Helper::fd($to)
        );
        $report = new \App\Report('report/emptask.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

<?php

namespace App\Pages\Report;

use App\Entity\Doc\Document;
use App\Entity\ProdStage;
use App\Entity\Employee;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\DropDownChoice;
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
        $this->filter->add(new DropDownChoice('emp', \App\Entity\Employee::findArray("emp_name", "disabled<>1", "emp_name"))) ;
  
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
        $emp = intval($this->filter->emp->getValue() );
        $empname = $this->filter->emp->getValueName();
        
        $brids = \App\ACL::getBranchIDsConstraint();
 
 
        $fromd = $conn->DBDate($from) ;
        $tod = $conn->DBDate($to) ;
 
       $be="";
       if (strlen($brids) > 0) {
          $be = " and document_id in(select document_id from documents where branch_id in ({$brids}) )   ";
       }  
       $detail=[];

 
           if($emp ==0) {
            $sql = "select coalesce(   sum(amount) ,0) as am,emp_id,emp_name from  empacc_view  where  optype = 104 {$be} AND DATE(createdon) >= {$fromd}   AND DATE(createdon) <= {$tod}  group by emp_id,emp_name  order  by  emp_name   ";
       
            foreach ($conn->Execute($sql) as $r) {
                if ($r['am'] > 0) {
                    $detail[] = array(
                        "name"   => $r['emp_name'],
                        "amount" => \App\Helper::fa($r['am'])
                    );
                }
            }
        }
        else {
            $sql = "select amount as am,createdon,document_number  from  empacc_view  where emp_id={$emp} and  amount > 0 and optype = 104 {$be} AND DATE(createdon) >= {$fromd}   AND DATE(createdon) <= {$tod}    order  by  createdon   ";
            foreach ($conn->Execute($sql) as $r) {
                if ($r['am'] > 0) {
                    $detail[] = array(
                        "document_date"   => $r['createdon'],
                        "document_number"   => $r['document_number'],
                        "amount" => \App\Helper::fa($r['am'])
                    );
                }
            }          
        }
        
        
        $header = array('datefrom' => \App\Helper::fd($from),
                        "_detail"  => $detail,
                        "isemp"  => $emp > 0,
                        "emp_name"  => $empname  ,
                     
                        'dateto'   => \App\Helper::fd($to)
        );
        $report = new \App\Report('report/emptask.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

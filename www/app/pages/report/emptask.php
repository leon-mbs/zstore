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

        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();

        $elist = Employee::find("", "emp_name");
        foreach ($elist as $emp_id => $emp) {
            $emp->cnt = 0;
            $emp->hours = 0;
            $emp->amount = 0;
        }

        $detail = array();
        $conn = \ZDB\DB::getConnect();

        $where = "   meta_name='Task'   
              AND DATE( document_date) >= " . $conn->DBDate($from) . "
              AND DATE( document_date) <= " . $conn->DBDate($to) . "
                
        and state= " . Document::STATE_CLOSED;

        $docs = Document::find($where);

        foreach ($docs as $doc) {


            $emplist = $doc->unpackDetails('emplist');
            if (count($emplist) == 0) {
                continue;
            }
            $total = 0;
            $hours = 0;
            foreach ($doc->unpackDetails('detaildata') as $service) {
                $ser = \App\Entity\Service::load($service->service_id);

                $total += (doubleval($ser->cost) * doubleval($service->quantity) ) ;
                $hours += (doubleval($ser->hours) * doubleval($service->quantity) );
            }
            if ($doc->headerdata['hours'] > 0) {
                $hours = $doc->headerdata['hours'];
            }


            foreach ($emplist as $emp) {


                $elist[$emp->employee_id]->amount += round($total * $emp->ktu);
                $elist[$emp->employee_id]->hours += $hours;
                $elist[$emp->employee_id]->cnt += 1;
            }
        };

        foreach ($elist as $emp_id => $emp) {
            if ($emp->cnt > 0) {
                $detail[] = array(
                    "name"   => $emp->emp_name,
                    "cnt"    => $emp->cnt,
                    "hours"  => $emp->hours,
                    "amount" => round($emp->amount)
                );
            }
        }

        
       $elist = Employee::find("", "emp_name");
        foreach ($elist as $emp_id => $emp) {
            $emp->cnt = 0;
            $emp->hours = 0;
            $emp->amount = 0;
        }

        $detail2 = array();
        $where = "      
                DATE( enddate) >= " . $conn->DBDate($from) . "
              AND DATE( enddate) <= " . $conn->DBDate($to) . "
                
        and state= " . ProdStage::STATE_FINISHED;

        $stages = ProdStage::find($where);
       
        foreach ($stages as $stage) {
            
            if (count($stage->emplist) == 0) {
                continue;
            }
            if($stage->salary >0){
           
                foreach ($stage->emplist as $emp) {


                    $elist[$emp->employee_id]->amount += round($stage->salary * $emp->ktu);
                    $elist[$emp->employee_id]->hours += $stage->hours;
                    $elist[$emp->employee_id]->cnt += 1;
                }         
                
            }
            
            
        }
        
        foreach ($elist as $emp_id => $emp) {
            if ($emp->cnt > 0) {
                $detail2[] = array(
                    "name"   => $emp->emp_name,
                    "cnt"    => $emp->cnt,
                    "hours"  => $emp->hours,
                    "amount" => round($emp->amount)
                );
            }
        }        
        
        
        $header = array('datefrom' => \App\Helper::fd($from),
                        "_detail"  => $detail,
                        "_detail2"  => $detail2,
                        'dateto'   => \App\Helper::fd($to)
        );
        $report = new \App\Report('report/emptask.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

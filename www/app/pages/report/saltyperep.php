<?php

namespace App\Pages\Report;

use App\Entity\Employee;
use App\Entity\SalType;
use App\Helper as H;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 *  Отчет по начислениям  и удержаним
 */
class SalTypeRep extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('SalTypeRep')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');

        $this->filter->add(new DropDownChoice('yfrom', \App\Util::getYears(), round(date('Y'))));
        $this->filter->add(new DropDownChoice('mfrom', \App\Util::getMonth(), round(date('m'))));
        $this->filter->add(new DropDownChoice('yto', \App\Util::getYears(), round(date('Y'))));
        $this->filter->add(new DropDownChoice('mto', \App\Util::getMonth(), round(date('m'))));

        $this->filter->add(new DropDownChoice('emp', Employee::findArray('emp_name', 'disabled<>1', 'emp_name')));

        $this->add(new Panel('detail'))->setVisible(false);
        $this->detail->add(new \Zippy\Html\Link\BookmarkableLink('print', ""));

        $this->detail->add(new RedirectLink('excel', "sltreport"));
        $this->detail->add(new RedirectLink('pdf', "sltreport"));
        $this->detail->add(new Label('preview'));
    }

    public function OnSubmit($sender) {


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);

        // \ZippyERP\System\Session::getSession()->storereport = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
        $reportpage = "App/Pages/ShowReport";
        $reportname = "sltreport";

        $this->detail->excel->pagename = $reportpage;
        $this->detail->excel->params = array('xls', $reportname);
        $this->detail->pdf->pagename = $reportpage;
        $this->detail->pdf->params = array('pdf', $reportname);

        $this->detail->setVisible(true);

        $this->detail->preview->setText($html, true);
    }

    private function generateReport() {

        $emp_id = $this->filter->emp->getValue();
        $emp_name = $this->filter->emp->getValueName();
        $yfrom = $this->filter->yfrom->getValue();
        $mfrom = $this->filter->mfrom->getValue();
        $mfromname = $this->filter->mfrom->getValueName();
        $yto = $this->filter->yto->getValue();
        $mto = $this->filter->mto->getValue();
        $mtoname = $this->filter->mto->getValueName();

  
   
        $from = strtotime($yfrom . '-' . $mfrom . '-01');
        $dt = new \App\DateTime($from);
        $from = $dt->startOfMonth()->getTimestamp();
 
    
         $to = strtotime($yto . '-' . $mto . '-01');
         $dt = new \App\DateTime($to);
        
  
         $to = $dt->endOfMonth()->getTimestamp();
    
        $conn = \Zdb\DB::getConnect() ;
        
        $doclist = \App\Entity\Doc\Document::find("meta_name = 'CalcSalary' and state >= 5 and document_date >= ". $conn->DBDate($from) . " and document_date <= " . $conn->DBDate($to) );

        $stlist   =   SalType::find("disabled<>1","salcode");
        
        $stam = array();
        foreach($stlist as $st){
            $stam[$st->salcode]  = 0;   
        }
    
        foreach ($doclist as $doc) {

         
    
            foreach ($doc->unpackDetails('detaildata') as $emp) {
                if($emp_id >0 && $emp_id != $emp->employee_id) continue;
            
                foreach($stlist as $st){
                    $code = '_c'.$st->salcode;
                    $am = doubleval($emp->{$code} );
                    
                    $stam[$st->salcode]  += $am;   
                    
                }  
                        
                 
            }
        }
        $detail = array();
   
        foreach($stlist as $st){
                     
            $detail[] = array('code'=>$st->salcode,
               'name'=>$st->salname,'am'=>H::fa($stam[$st->salcode] )
               );
        }


        $header = array(
            "_detail"  => array_values($detail),
            'yfrom'    => $yfrom,
            'mfrom'    => $mfromname,
            'yto'      => $yto,
            'mto'      => $mtoname,
            'isemp'    => $emp_id > 0,
            
            "emp_name" => $emp_name
        );

        $report = new \App\Report('report/saltyperep.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

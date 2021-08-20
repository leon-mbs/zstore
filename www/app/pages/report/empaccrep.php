<?php

namespace App\Pages\Report;

use App\Entity\Employee;
use App\Helper as H;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 *  Отчет движение  по  лицевым  счетам
 */
class EmpAccRep extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('EmpAccRep')) {
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

        $this->detail->add(new RedirectLink('excel', "eareport"));
        $this->detail->add(new RedirectLink('pdf', "eareport"));
        $this->detail->add(new Label('preview'));
    }

    public function OnSubmit($sender) {


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);

        // \ZippyERP\System\Session::getSession()->storereport = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
        $reportpage = "App/Pages/ShowReport";
        $reportname = "eareport";

        $this->detail->excel->pagename = $reportpage;
        $this->detail->excel->params = array('xls', $reportname);
        $this->detail->pdf->pagename = $reportpage;
        $this->detail->pdf->params = array('pdf', $reportname);

        $this->detail->setVisible(true);

        $this->detail->preview->setText($html, true);
    }

    private function generateReport() {
        $dt = new \App\DateTime( );
        $from = $dt->addMonth(-1)->startOfMonth()->getTimestamp();
        $from= date(\DateTime::ISO8601,$from);
        
        
        $to= date(\DateTime::ISO8601,time());
   
        $emp_id = $this->filter->emp->getValue();
        $emp_name = $this->filter->emp->getValueName();
        if(strlen($emp_name)==0) {
            $this->setError('noempselected') ;
            return;
        }
        $yfrom = $this->filter->yfrom->getValue();
        $mfrom = $this->filter->mfrom->getValue() ;
        $mfromname = $this->filter->mfrom->getValueName();
        $yto = $this->filter->yto->getValue();
        $mto = $this->filter->mto->getValue() ;
        $mtoname = $this->filter->mto->getValueName();

    
         $dt = new \App\DateTime(strtotime($yfrom . '-' . $mfrom . '-01')) ;        
         $from = $dt->startOfMonth()->getTimestamp();
           
         $dt = new \App\DateTime(strtotime($yto . '-' . $mto . '-01')) ;        
         $to = $dt->endOfMonth()->getTimestamp();
         
      
         $conn = \Zdb\DB::getConnect() ;

         $sql = "select coalesce(sum(amount),0) from empacc_view where emp_id = {$emp_id} and document_date < ". $conn->DBDate($from)    ;   

         $b = $conn->GetOne($sql);
         
         $sql = "select coalesce(sum(  case  when amount >0 then amount else  0 end ),0)  as inp, coalesce(sum(  case  when amount <0 then 0-amount else  0 end ),0)  as outp,document_date,document_id,document_number from empacc_view where emp_id= {$emp_id} and document_date >= ". $conn->DBDate($from) . " and document_date <= " . $conn->DBDate($to) . " group by  document_date,document_id,document_number  order  by document_date, document_id "   ;   
         
         $rc= $conn->Execute($sql);
       
         $detail = array();
         
         foreach ($rc as $row) {

           $detail[] = array(
             'dt'=> $row['document_date'] ,
             'doc'=>$row['document_number'],
             'begin'=> H::fa($b),
            'in'=>H::fa($row['inp']),
            'out'=>H::fa($row['outp']),
            'end'=>H::fa($b + $row['inp'] - $row['outp'] )
              );
         
          
            $b  = $b + $row['inp'] - $row['outp']  ;
         }
        
     


        $header = array(
            "_detail"  => array_values($detail),
            'yfrom'    => $yfrom,
            'mfrom'    => $mfromname,
            'yto'      => $yto,
            'mto'      => $mtoname,
        
            "emp_name" => $emp_name
        );

        $report = new \App\Report('report/empaccrep.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

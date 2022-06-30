<?php

namespace App\Pages;

use App\Entity\Employee;
use App\Helper as H;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 *  Лицевой счет
 */
class EmpAcc extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
     

        $this->add(new Form('filterz'))->onSubmit($this, 'OnSubmitZ');

        $this->filterz->add(new DropDownChoice('yfrom', \App\Util::getYears(), round(date('Y'))));
        $this->filterz->add(new DropDownChoice('mfrom', \App\Util::getMonth(), round(date('m'))));
        $this->filterz->add(new DropDownChoice('yto', \App\Util::getYears(), round(date('Y'))));
        $this->filterz->add(new DropDownChoice('mto', \App\Util::getMonth(), round(date('m'))));

 
    }

    public function OnSubmitZ($sender) {

        $dt = new \App\DateTime();
        $from = $dt->addMonth(-1)->startOfMonth()->getTimestamp();
        $from = date(\DateTime::ISO8601, $from);


        $to = date(\DateTime::ISO8601, time());

        $emp_id = \App\System::getUser()->employee_id ;

        $yfrom = $this->filterz->yfrom->getValue();
        $mfrom = $this->filterz->mfrom->getValue();
        $mfromname = $this->filterz->mfrom->getValueName();
        $yto = $this->filterz->yto->getValue();
        $mto = $this->filterz->mto->getValue();
        $mtoname = $this->->mto->getValueName();


        $dt = new \App\DateTime(strtotime($yfrom . '-' . $mfrom . '-01'));
        $from = $dt->startOfMonth()->getTimestamp();

        $dt = new \App\DateTime(strtotime($yto . '-' . $mto . '-01'));
        $to = $dt->endOfMonth()->getTimestamp();


        $conn = \Zdb\DB::getConnect();

        $sql = "select coalesce(sum(amount),0) from empacc_view where emp_id = {$emp_id} and document_date < " . $conn->DBDate($from);

        $b = $conn->GetOne($sql);

        $sql = "select coalesce(sum(  case  when amount >0 then amount else  0 end ),0)  as inp, coalesce(sum(  case  when amount <0 then 0-amount else  0 end ),0)  as outp,document_date,document_id,document_number from empacc_view where emp_id= {$emp_id} and document_date >= " . $conn->DBDate($from) . " and document_date <= " . $conn->DBDate($to) . " group by  document_date,document_id,document_number  order  by document_date, document_id ";

        $rc = $conn->Execute($sql);

        $detail = array();

        foreach ($rc as $row) {

            $detail[] = array(
                'dt'    => $row['document_date'],
                'doc'   => $row['document_number'],
                'begin' => H::fa($b),
                'in'    => H::fa($row['inp']),
                'out'   => H::fa($row['outp']),
                'end'   => H::fa($b + $row['inp'] - $row['outp'])
            );


            $b = $b + $row['inp'] - $row['outp'];
        }


        $header = array(
            "_detail" => array_values($detail),
            'yfrom'   => $yfrom,
            'mfrom'   => $mfromname,
            'yto'     => $yto,
            'mto'     => $mtoname,

            "emp_name" => $emp_name
        );

        $report = new \App\Report('report/empaccrep.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

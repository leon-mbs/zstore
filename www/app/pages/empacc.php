<?php

namespace App\Pages;

use App\Entity\Employee;
use App\Helper as H;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;
use Zippy\Html\Form\Date;
use App\Entity\SalType;
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

        $this->add(new Form('filters'))->onSubmit($this, 'OnSubmitS');

        $d = new \App\DateTime() ;
        $d = $d->startOfMonth()->subMonth(1) ;
          
        $this->filters->add(new Date('from', $d->getTimestamp()));
        $this->filters->add(new Date('to', time()));
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
        $mtoname = $this->filterz->mto->getValueName();

        $dt = new \App\DateTime(strtotime($yfrom . '-' . $mfrom . '-01'));
        $from = $dt->startOfMonth()->getTimestamp();

        $dt = new \App\DateTime(strtotime($yto . '-' . $mto . '-01'));
        $to = $dt->endOfMonth()->getTimestamp();


        $conn = \Zdb\DB::getConnect();

         $doclist = \App\Entity\Doc\Document::find("meta_name = 'OutSalary' and state >= 5 ");

        $detail = array();

        $from = strtotime($yfrom . '-' . $mfrom . '-01');
        $to = strtotime($yto . '-' . $mto . '-01 23:59:59');

        foreach ($doclist as $doc) {

            $date = strtotime($doc->headerdata['year'] . '-' . $doc->headerdata['month'] . '-01');

            $d1 = \App\Helper::fdt($from);
            $d2 = \App\Helper::fdt($to);
            $d3 = \App\Helper::fdt($date);

            if ($date < $from || $date > $to) {
                continue;
            }
        $total = 0;
            foreach ($doc->unpackDetails('detaildata') as $emp) {

                if ($emp->employee_id == $emp_id && $emp->amount > 0) {
                    $d = $doc->headerdata['year'] . $doc->headerdata['month'];
                    
                    if (!is_array($detail[$d])) {
                         $detail[$d]    = array("d"=>$doc->headerdata['monthname'] . ' ' . $doc->headerdata['year'],'v'=>0)  ;                   
                    }
                
                    $detail[$d]['v'] = H::fa($detail[$d]['v'] + $emp->amount);

                    
                    $total += $emp->amount;
                }  
            }
        }

     

        //типы начислний
       $doclist = \App\Entity\Doc\Document::find("meta_name = 'CalcSalary' and state >= 5 and document_date >= " . $conn->DBDate($from) . " and document_date <= " . $conn->DBDate($to));

        $stlist = SalType::find("disabled<>1", "salcode");

        $stam = array();
        foreach ($stlist as $st) {
            $stam[$st->salcode] = 0;
        }

        foreach ($doclist as $doc) {


            foreach ($doc->unpackDetails('detaildata') as $emp) {
                if (    $emp_id != $emp->employee_id) {
                    continue;
                }

                foreach ($stlist as $st) {
                    $code = '_c' . $st->salcode;
                    $am = doubleval($emp->{$code});

                    $stam[$st->salcode] += $am;

                }


            }
        }
        $detail2 = array();

        foreach ($stlist as $st) {

            $detail2[] = array('code' => $st->salcode,
                              'name' => $st->salname, 'am' => H::fa($stam[$st->salcode])
            );
        }
        
        

       $this->_tvars['memsal']  = array_values($detail);
       $this->_tvars['mempst']  =  $detail2;
       $this->_tvars['memtotal']  = H::fa( $total);
 

      
    }
 
    public function OnSubmitS($sender) {

      
        $emp_id = \App\System::getUser()->employee_id ;
        $from =  $this->filters->from->getDate();
        $to =  $this->filters->to->getDate();
        
        $conn = \Zdb\DB::getConnect();

        $sql = "select coalesce(sum(amount),0) from empacc where optype < 100 and  emp_id = {$emp_id} and createdon < " . $conn->DBDate($from);

        $b = $conn->GetOne($sql);


        $sql =    $sql = "select * from empacc_view where optype < 100 and  emp_id = {$emp_id} and createdon <= " . $conn->DBDate($to) . " and createdon >= " . $conn->DBDate($from) ." order  by  ea_id ";
        $rc = $conn->Execute($sql);

        $detail = array();

        foreach ($rc as $row) {
            $in =   doubleval($row['amount']) > 0 ? $row['amount']  :0;
            $out =   doubleval($row['amount']) < 0 ? 0-$row['amount']  :0;
            $detail[] = array(
                'notes'    => $row['notes'],
                'dt'    => H::fd(strtotime($row['createdon'])),
                'doc'   => $row['document_number'],
                'begin' => H::fa($b),
                'in'    => H::fa($in),
                'out'   => H::fa($out),
                'end'   => H::fa($b + $in - $out)
            );


            $b = $b + $in - $out;
        }    
        
        $this->_tvars['mempacc']  =  $detail;
           
    }

}

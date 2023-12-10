<?php

namespace App\Pages\Report;

use App\Entity\Employee;
use App\Helper as H;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;
use App\Entity\SalType;

/**
 *  Отчет по  зарплате
 */
class SalaryRep extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('SalaryRep')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');

        $this->filter->add(new DropDownChoice('yfrom', \App\Util::getYears(), round(date('Y'))));
        $this->filter->add(new DropDownChoice('mfrom', \App\Util::getMonth(), round(date('m'))));
        $this->filter->add(new DropDownChoice('yto', \App\Util::getYears(), round(date('Y'))));
        $this->filter->add(new DropDownChoice('mto', \App\Util::getMonth(), round(date('m'))));

        $this->filter->add(new DropDownChoice('emp', Employee::findArray('emp_name', 'disabled<>1', 'emp_name')));

        $this->add(new Panel('detail'))->setVisible(false);

        $this->detail->add(new Label('preview'));
    }

    public function OnSubmit($sender) {


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);


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
        $conn = \ZDB\DB::getConnect();

        

        $detail = array();

        $from = strtotime($yfrom . '-' . $mfrom . '-01');
        $to = strtotime($yto . '-' . $mto . '-01 23:59:59');

        foreach (\App\Entity\Doc\Document::findYield("meta_name = 'OutSalary' and state >= 5 ") as $doc) {

            $date = strtotime($doc->headerdata['year'] . '-' . $doc->headerdata['month'] . '-01');

            $d1 = \App\Helper::fdt($from);
            $d2 = \App\Helper::fdt($to);
            $d3 = \App\Helper::fdt($date);

            if ($date < $from || $date > $to) {
                continue;
            }

            foreach ($doc->unpackDetails('detaildata') as $emp) {

                if ($emp_id > 0 && $emp->amount > 0) {
                    if ($emp->employee_id != $emp_id) {
                        continue;
                    }
                    if (is_array($detail[$doc->headerdata['year'] . $doc->headerdata['month']])) {
                        $detail[$doc->headerdata['year'] . $doc->headerdata['month']]['v'] += $emp->amount;
                    } else {
                        $detail[$doc->headerdata['year'] . $doc->headerdata['month']] = array('k' => $doc->headerdata['monthname'] . ' ' . $doc->headerdata['year'], 'v' => $emp->amount);
                    }
                } else {
                    if ($emp->amount > 0) {
                        if (is_array($detail[$emp->employee_id])) {
                            $detail[$emp->employee_id]['v'] += $emp->amount;
                        } else {
                            $detail[$emp->employee_id] = array('k' => $emp->emp_name, 'v' => $emp->amount);
                        }
                    }
                }
            }
        }
        $total = 0;
        foreach ($detail as $k => $item) {
            $total += $item['v'];
            $item['v'] = H::fa($item['v']);
        }

        //типы начислний
        

        $stlist = SalType::find("disabled<>1", "salcode");

        $stam = array();
        foreach ($stlist as $st) {
            $stam[$st->salcode] = 0;
        }

        foreach (\App\Entity\Doc\Document::findYield("meta_name = 'CalcSalary' and state >= 5 and document_date >= " . $conn->DBDate($from) . " and document_date <= " . $conn->DBDate($to)) as $doc) {


            foreach ($doc->unpackDetails('detaildata') as $emp) {
                if ($emp_id > 0 && $emp_id != $emp->employee_id) {
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



        $header = array(
            "_detail"  => array_values($detail),
            "_detail2"  => array_values($detail2),
            'yfrom'    => $yfrom,
            'mfrom'    => $mfromname,
            'yto'      => $yto,
            'mto'      => $mtoname,
            'isemp'    => $emp_id > 0,
            'total'    => H::fa($total),
            "emp_name" => $emp_name
        );

        $report = new \App\Report('report/salaryrep.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

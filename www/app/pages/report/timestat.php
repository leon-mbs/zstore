<?php

namespace App\Pages\Report;

use App\Entity\Employee;
use App\Entity\TimeItem;
use App\Helper as H;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;
use Zippy\Html\Form\Date;

/**
 *  Отчет по  рабочему времени
 */
class TimeStat extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('TimeStat')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');

        $dt = new \Carbon\Carbon;
        $dt->subMonth();
        $from = $dt->startOfMonth()->timestamp;
        $to = $dt->endOfMonth()->timestamp;

        $this->filter->add(new Date('from', $from));
        $this->filter->add(new Date('to', $to));
        $this->filter->add(new DropDownChoice('ttype', TimeItem::getTypeTiime(), 0));

    

        
        $this->detail->add(new RedirectLink('excel', "tsreport"));
        $this->detail->add(new RedirectLink('pdf', "tsreport"));
        $this->detail->add(new Label('preview'));

    }

    public function OnSubmit($sender) {


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);

        // \ZippyERP\System\Session::getSession()->storereport = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
        $reportpage = "App/Pages/ShowReport";
        $reportname = "timestat";


        $this->detail->excel->pagename = $reportpage;
        $this->detail->excel->params = array('xls', $reportname);
        $this->detail->pdf->pagename = $reportpage;
        $this->detail->pdf->params = array('pdf', $reportname);

        $this->detail->setVisible(true);
      

        $this->detail->preview->setText($html, true);
    }

    private function generateReport() {

        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();

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

            foreach ($doc->unpackDetails('detaildata') as $emp) {

                if ($emp_id > 0) {
                    if ($emp->employee_id != $emp_id) {
                        continue;
                    }

                    $detail[$doc->headerdata['year'] . $doc->headerdata['month']] = array('k' => $doc->headerdata['monthname'] . ' ' . $doc->headerdata['year'], 'v' => $emp->amount);

                } else {
                    if ($emp->amount > 0) {
                        if (is_array($detail[$emp->emp_id])) {
                            $detail[$emp->employee_id]['amount'] += $emp->amount;
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


        $header = array(
            "_detail" => array_values($detail),
            'yfrom' => $yfrom,
            'mfrom' => $mfromname,
            'yto' => $yto,
            'mto' => $mtoname,
            'isemp' => $emp_id > 0,
            'total' => H::fa($total),

            "emp_name" => $emp_name
        );


        $report = new \App\Report('report/timestat.tpl');

        $html = $report->generate($header);

        return $html;
    }


}

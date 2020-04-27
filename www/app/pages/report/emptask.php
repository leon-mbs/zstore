<?php

namespace App\Pages\Report;

use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\AutocompleteTextInput;
use \Zippy\Html\Label;
use \Zippy\Html\Link\RedirectLink;
use \Zippy\Html\Panel;
use \App\Entity\Employee;
use \App\Entity\Doc\Document;
use \App\Helper as H;

/**
 * Движение товара
 */
class EmpTask extends \App\Pages\Base {

    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowReport('EmpTask'))
            return;

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));

        $this->add(new Panel('detail'))->setVisible(false);
        $this->detail->add(new \Zippy\Html\Link\BookmarkableLink('print', ""));
        $this->detail->add(new RedirectLink('word', "movereport"));
        $this->detail->add(new RedirectLink('excel', "movereport"));
        $this->detail->add(new RedirectLink('pdf', "movereport"));
        $this->detail->add(new Label('preview'));
    }

    public function OnSubmit($sender) {


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

        // \ZippyERP\System\Session::getSession()->storereport = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
        $reportpage = "App/Pages/ShowReport";
        $reportname = "emptask";


            $this->detail->word->pagename = $reportpage;
        $this->detail->word->params = array('doc', $reportname);
        $this->detail->excel->pagename = $reportpage;
        $this->detail->excel->params = array('xls', $reportname);
        $this->detail->pdf->pagename = $reportpage;
        $this->detail->pdf->params = array('pdf', $reportname);

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
            if (count($emplist) == 0)
                continue;
            $total = 0;
            $hours = 0;
            foreach ($doc->unpackDetails('detaildata') as $service) {
                $total += $service->price;
                $hours += $service->hours;
            }
            if ($doc->headerdata['hours'] > 0)
                $hours = $doc->headerdata['hours'];

            $part = round($total / count($emplist)); //доля денег

            foreach ($emplist as $emp) {


                $elist[$emp->employee_id]->amount += $part;
                $elist[$emp->employee_id]->hours += $hours;
                $elist[$emp->employee_id]->cnt += 1;
            }
        };

        foreach ($elist as $emp_id => $emp) {
            if ($emp->cnt > 0) {
                $detail[] = array(
                    "name" => $emp->emp_name,
                    "cnt" => $emp->cnt,
                    "hours" => $emp->hours,
            
                    "amount" => round($emp->amount)
                );
            }
        }

        $header = array('datefrom' => date('d.m.Y', $from),
            "_detail" => $detail,
            'dateto' => date('d.m.Y', $to)
        );
        $report = new \App\Report('report/emptask.tpl');

        $html = $report->generate($header );

        return $html;
    }

}

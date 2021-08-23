<?php

namespace App\Pages\Report;

use App\Entity\Doc\Document;
use App\Entity\Employee;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 * Оплата по нарядам
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
            if (count($emplist) == 0) {
                continue;
            }
            $total = 0;
            $hours = 0;
            foreach ($doc->unpackDetails('detaildata') as $service) {
                $ser = \App\Entity\Service::load($service->service_id)  ;
                
                $total += $ser->cost * $service->quantity;
                $hours += $ser->hours * $service->quantity;
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

        $header = array('datefrom' => \App\Helper::fd($from),
                        "_detail"  => $detail,
                        'dateto'   => \App\Helper::fd($to)
        );
        $report = new \App\Report('report/emptask.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

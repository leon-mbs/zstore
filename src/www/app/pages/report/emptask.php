<?php

namespace App\Pages\Report;

use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;
use App\Entity\Employee;
use App\Entity\Doc\Document;
use App\Helper as H;

/**
 * Движение товара
 */
class EmpTask extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
       
        if(false ==\App\ACL::checkShowReport('EmpTask'))return;       

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));

        $this->add(new Panel('detail'))->setVisible(false);
        $this->detail->add(new RedirectLink('print', "movereport"));
        $this->detail->add(new RedirectLink('html', "movereport"));
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

  
        $this->detail->print->pagename = $reportpage;
        $this->detail->print->params = array('print', $reportname);
        $this->detail->html->pagename = $reportpage;
        $this->detail->html->params = array('html', $reportname);
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

        $header = array('datefrom' => date('d.m.Y', $from),
            'dateto' => date('d.m.Y', $to)
        );

        $elist = Employee::find("", "emp_name");


        $detail = array();
        $conn = \ZDB\DB::getConnect();

        $where = "   meta_name='Task' and state=9  
              AND DATE( document_date) >= " . $conn->DBDate($from) . "
              AND DATE( document_date) <= " . $conn->DBDate($to) . "
                
        ";

        $docs = Document::find($where);

        foreach ($docs as $doc) {


            foreach ($doc->detaildata as $item) {
                if ($item["employee_id"] > 0) {



                    if ($elist[$item["employee_id"]]->amount > 0)
                        $elist[$item["employee_id"]]->amount = $elist[$item["employee_id"]]->amount + $item['pay'];
                    else
                        $elist[$item["employee_id"]]->amount = $item['pay'];

                    if ($elist[$item["employee_id"]]->cnt > 0)
                        $elist[$item["employee_id"]]->cnt = $elist[$item["employee_id"]]->cnt + 1;
                    else
                        $elist[$item["employee_id"]]->cnt = 1;

                    if ($doc->headerdata['hours'] > 0) {
                        if ($elist[$item["employee_id"]]->hours > 0)
                            $elist[$item["employee_id"]]->hours = $elist[$item["employee_id"]]->hours + $doc->headerdata['hours'];
                        else
                            $elist[$item["employee_id"]]->hours = $doc->headerdata['hours'];
                    }
                }
            }
        };

        foreach ($elist as $emp_id => $emp) {
            if ($emp->cnt > 0) {
                $detail[] = array(
                    "name" => $emp->emp_name,
                    "cnt" => $emp->cnt,
                    "hours" => $emp->hours,
                    "amountpay" => $emp->amount, //todo расчет 
                    "amount" => $emp->amount
                );
            }
        }


        $report = new \App\Report('emptask.tpl');

        $html = $report->generate($header, $detail);

        return $html;
    }

}

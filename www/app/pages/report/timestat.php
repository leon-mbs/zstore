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

        $dt = new \App\DateTime();

        $from = $dt->startOfMonth()->getTimestamp();
        $to = $dt->endOfMonth()->getTimestamp();

        $this->filter->add(new Date('from', $from));
        $this->filter->add(new Date('to', $to));
        $this->filter->add(new DropDownChoice('ttype', TimeItem::getTypeTime(), TimeItem::TIME_WORK));

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

        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();
        $type = $this->filter->ttype->getValue();

        $conn = \ZDB\DB::getConnect();
        $_from = $conn->DBDate($from);
        $_to = $conn->DBDate($to);

        $detail = array();
        $total = 0;
        $sql = "select emp_name,sum(tm) as tm  from (select  emp_name,  (UNIX_TIMESTAMP(t_end)-UNIX_TIMESTAMP(t_start)  - t_break*60)   as  tm from timesheet_view where  t_type = {$type} and  t_start>={$_from} and   t_start<={$_to}  and  disabled <> 1) t  group by emp_name order by emp_name ";
    
        $stat = $conn->Execute($sql);
        foreach ($stat as $row) {

            $tm = number_format($row['tm'] / 3600, 2, '.', '');
            $detail[] = array('emp_name' => $row['emp_name'], 'tm' => $tm);
            $total += intval($tm);
        }


        $header = array(
            "_detail"  => array_values($detail),
            'from'     => \App\Helper::fd($from),
            'to'       => \App\Helper::fd($to),
            'total'    => number_format($total, 2, '.', ''),
            "typename" => $this->filter->ttype->getValueName()
        );

        $report = new \App\Report('report/timestat.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

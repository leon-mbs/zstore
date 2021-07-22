<?php

namespace App\Pages\Report;

use App\Helper as H;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 * отчет по  производству
 */
class Prod extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('Prod')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));
        $this->filter->add(new DropDownChoice('parea', \App\Entity\Prodarea::findArray("pa_name", ""), 0));

        $this->add(new Panel('detail'))->setVisible(false);
        $this->detail->add(new \Zippy\Html\Link\BookmarkableLink('print', ""));
        $this->detail->add(new RedirectLink('word', "movereport"));
        $this->detail->add(new RedirectLink('excel', "movereport"));
        $this->detail->add(new RedirectLink('pdf', "abc"));
        $this->detail->add(new Label('preview'));
    }

    public function OnSubmit($sender) {


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

        // \ZippyERP\System\Session::getSession()->storereport = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
        $reportpage = "App/Pages/ShowReport";
        $reportname = "prod";

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
        $parea = $this->filter->parea->getValue();

        $detail = array();
        $detail2 = array();
        $sum1 = 0;
        $sum2 = 0;
        $conn = \ZDB\DB::getConnect();

        $wparea = "";
        if ($parea > 0) {
            $wparea = " and content like '%<parea>{$parea}</parea>%' ";
        }

        $sql = "
          select i.`itemname`,i.`item_code`,0-sum(e.`quantity`) as qty, 0-sum((partion )*quantity) as summa
              from `entrylist_view`  e

              join `items` i on e.`item_id` = i.`item_id`
             join `documents_view` d on d.`document_id` = e.`document_id`
               where e.`item_id` >0  and e.`quantity` < 0
               and d.`meta_name` in ('ProdIssue','ProdReceipt')
               {$wparea}
              AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
                group by  i.`itemname`,i.`item_code`
               order  by i.`itemname`
        ";

        $rs = $conn->Execute($sql);

        foreach ($rs as $row) {
            $detail[] = array(
                "code"  => $row['item_code'],
                "name"  => $row['itemname'],
                "qty"   => H::fqty($row['qty']),
                "summa" => H::fa($row['summa'])
            );
            $sum1 += $row['summa'];
        }

        $sql = "
          select i.`itemname`,i.`item_code`,sum(e.`quantity`) as qty, sum((partion  )*quantity) as summa
              from `entrylist_view`  e

              join `items` i on e.`item_id` = i.`item_id`
             join `documents_view` d on d.`document_id` = e.`document_id`
               where e.`item_id` >0  and e.`quantity`  >0
               and d.`meta_name` in ('GoodsIssue','ProdIssue','ProdReceipt')
 
              AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
                group by  i.`itemname`,i.`item_code`
               order  by i.`itemname`
        ";

        $rs = $conn->Execute($sql);

        foreach ($rs as $row) {
            $detail2[] = array(
                "code"  => $row['item_code'],
                "name"  => $row['itemname'],
                "qty"   => H::fqty($row['qty']),
                "summa" => H::fa($row['summa'])
            );
            $sum2 += $row['summa'];
        }

        $header = array('datefrom' => \App\Helper::fd($from),
                        "_detail"  => $detail,
                        "_detail2" => $detail2,
                        'dateto'   => \App\Helper::fd($to),
                        'parea'    => null,
                        'sum1'     => H::fa($sum1),
                        'sum2'     => H::fa($sum2)
        );
        if ($parea > 0) {
            $header['parea'] = $this->filter->parea->getValueName();
        }
        $report = new \App\Report('report/prod.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

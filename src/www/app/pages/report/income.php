<?php

namespace App\Pages\Report;

use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;
use App\Entity\Item;
use App\Entity\Store;
use App\Helper as H;

/**
 * Отчет по закупкам
 */
class Income extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('Income'))
            return;


        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));
        $this->filter->add(new DropDownChoice('type', array(1 => 'По товарам', 2 => 'По поставщикам', 3 => 'По датам'), 1));


        $this->add(new Panel('detail'))->setVisible(false);
        $this->detail->add(new RedirectLink('print', "movereport"));
        $this->detail->add(new RedirectLink('html', "movereport"));
        $this->detail->add(new RedirectLink('word', "movereport"));
        $this->detail->add(new RedirectLink('excel', "movereport"));
        $this->detail->add(new RedirectLink('pdf', "abc"));
        $this->detail->add(new Label('preview'));
    }

    public function OnAutoItem($sender) {
        $r = array();


        $text = Item::qstr('%' . $sender->getText() . '%');
        $list = Item::findArray('itemname', " disabled <> 1  and (itemname like {$text} or item_code like {$text} ) ");
        foreach ($list as $k => $v) {
            $r[$k] = $v;
        }
        return $r;
    }

    public function OnSubmit($sender) {


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

        // \ZippyERP\System\Session::getSession()->storereport = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
        $reportpage = "App/Pages/ShowReport";
        $reportname = "income";


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

        $type = $this->filter->type->getValue();

        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();





        $detail = array();
        $conn = \ZDB\DB::getConnect();

        if ($type == 1) {    //по товарам
            $sql = "
          select i.`itemname`,i.`item_code`,sum(e.`quantity`) as qty, sum(e.`amount`) as summa
              from `entrylist_view`  e

              join `items` i on e.`item_id` = i.`item_id`
             join `documents_view` d on d.`document_id` = e.`document_id`
               where e.`item_id` >0  and e.`quantity` >0
               and d.`meta_name` in ('GoodsReceipt')
 
              AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
                group by  i.`itemname`,i.`item_code`
               order  by i.`itemname`
        ";
        }
        if ($type == 2) {  //по постащикам
            $sql = "
          select c.`customer_name` as itemname,c.`customer_id`,  sum(e.`amount`) as summa
          from `entrylist_view`  e

         join `customers`  c on c.`customer_id` = e.`customer_id`
         join `documents_view`  d on d.`document_id` = e.`document_id`
           where e.`customer_id` >0  and e.`quantity` >0
           and d.`meta_name` in ('GoodsReceipt')
          AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
  group by  c.`customer_name`,c.`customer_id`
  order  by c.`customer_name`
        ";
        }
        if ($type == 3) {   //по датам
            $sql = "
          select e.`document_date` as dt  ,  sum(e.`amount`) as summa
              from `entrylist_view`  e

              join `items` i on e.`item_id` = i.`item_id`
             join `documents_view` d on d.`document_id` = e.`document_id`
               where e.`item_id` >0  and e.`quantity` >0
               and d.`meta_name` in ('GoodsReceipt')
             
               AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
         group by  e.`document_date`
  order  by e.`document_date`
        ";
        }



        $rs = $conn->Execute($sql);

        foreach ($rs as $row) {
            $detail[] = array(
                "code" => $row['item_code'],
                "name" => $row['itemname'],
                "dt" => date('Y-m-d', strtotime($row['dt'])),
                "qty" => H::fqty($row['qty']),
                "summa" => $row['summa']
            );
        }

         $header = array('datefrom' => date('d.m.Y', $from),
           "_detail" => $detail,
             'dateto' => date('d.m.Y', $to)
        );
        if ($type == 1) {
            $header['_type1'] = true;
            $header['_type2'] = false;
            $header['_type3'] = false;
        }
        if ($type == 2) {
            $header['_type1'] = false;
            $header['_type2'] = true;
            $header['_type3'] = false;
        }
        if ($type == 3) {
            $header['_type1'] = false;
            $header['_type2'] = false;
            $header['_type3'] = true;
        }
        $report = new \App\Report('income.tpl');

        $html = $report->generate($header, $detail);

        return $html;
    }

}

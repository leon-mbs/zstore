<?php

namespace App\Pages\Report;

use App\Entity\Item;
use App\Helper as H;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 * Отчет по продажам
 */
class Outcome extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('Outcome')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));
        $this->filter->add(new DropDownChoice('type', array(1 => H::l('repbyitems'), 5 => H::l('repbycat'), 2 => H::l('repbybyers'), 3 => H::l('repbydates'), 4 => H::l('repbyservices')), 1))->onChange($this, "OnType");
        $this->filter->add(new DropDownChoice('emp', \App\Entity\User::findArray('username', "user_id in (select user_id from documents_view  where  meta_name  in('GoodsIssue','ServiceAct','Task','Order','POSCheck'))", 'username'), 0));
        $this->filter->add(new DropDownChoice('cat', \App\Entity\Category::findArray('cat_name', "", 'cat_name'), 0))->setVisible(false);

        $this->add(new Panel('detail'))->setVisible(false);
        $this->detail->add(new \Zippy\Html\Link\BookmarkableLink('print', ""));
        $this->detail->add(new RedirectLink('word', "outcome"));
        $this->detail->add(new RedirectLink('excel', "outcome"));
        $this->detail->add(new RedirectLink('pdf', "outcome"));
        $this->detail->add(new Label('preview'));
    }

    public function OnType($sender) {
        $type = $this->filter->type->getValue();
        $this->filter->cat->setValue(0);
        if ($type == 5) {
            $this->filter->cat->setVisible(true);
        } else {
            $this->filter->cat->setVisible(false);
        }
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
        $reportname = "outcome";


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
        $user = $this->filter->emp->getValue();
        $cat_id = $this->filter->cat->getValue();

        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();


        $u = "";

        if ($user > 0) {
            $u = " and d.user_id={$user} ";
        }

        $detail = array();
        $conn = \ZDB\DB::getConnect();
        $cat = "";
        if ($type == 5 && $cat_id > 0) {
            $cat = " and cat_id=" . $cat_id;
        }

        if ($type == 1 || strlen($cat) > 0) {    //по товарам
            $sql = "
          select i.`itemname`,i.`item_code`,sum(0-e.`quantity`) as qty, sum(0-e.`amount`) as summa, sum(e.extcode*(0-e.`quantity`)) as navar
              from `entrylist_view`  e

              join `items_view` i on e.`item_id` = i.`item_id`
             join `documents_view` d on d.`document_id` = e.`document_id`
               where e.`item_id` >0 {$u} and e.`quantity` <> 0   {$cat}
               and d.`meta_name` in ('GoodsIssue','ServiceAct' ,'POSCheck','ReturnIssue')
 
              AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
                group by  i.`itemname`,i.`item_code`
               order  by i.`itemname`
        ";
        }
        if ($type == 2) {  //по покупателям
            $sql = "
          select c.`customer_name` as itemname,c.`customer_id`,  sum(0-e.`amount`) as summa, sum(e.extcode*(0-e.`quantity`)) as navar
          from `entrylist_view`  e

         join `customers`  c on c.`customer_id` = e.`customer_id`
         join `documents_view`  d on d.`document_id` = e.`document_id`
           where e.`customer_id` >0 {$u} and e.`quantity` <>0
             and d.`meta_name` in ('GoodsIssue','ServiceAct',  'POSCheck','ReturnIssue')         AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
  group by  c.`customer_name`,c.`customer_id`
  order  by c.`customer_name`
        ";
        }
        if ($type == 3) {   //по датам
            $sql = "
          select e.`document_date` as dt  ,  sum(0-e.`amount`) as summa
              from `entrylist_view`  e

              join `items` i on e.`item_id` = i.`item_id`
             join `documents_view` d on d.`document_id` = e.`document_id`
               where e.`item_id` >0 {$u} and e.`quantity` <>0
              and d.`meta_name` in ('GoodsIssue','ServiceAct' ,'POSCheck','ReturnIssue')           
               AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
         group by  e.`document_date`
  order  by e.`document_date`
        ";
        }

        if ($type == 4) {    //по сервисам
            $sql = "
         select s.`service_name` as itemname, sum(0-e.`quantity`) as qty, sum(0-e.`amount`) as summa
              from `entrylist_view`  e

              join `services` s on e.`service_id` = s.`service_id`
             join `documents_view` d on d.`document_id` = e.`document_id`
               where e.`service_id` >0 {$u} and e.`quantity` <>0
              and d.`meta_name` in ('GoodsIssue','ServiceAct' ,'POSCheck','ReturnIssue')
                AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
                   group by s.`service_name`
               order  by s.`service_name`      ";
        }

        if ($type == 5 && strlen($cat) == 0) {    //по категориях
            $sql = "
            select  i.`cat_name` as itemname,sum(0-e.`quantity`) as qty, sum(0-e.`amount`) as summa, sum(e.extcode*(0-e.`quantity`)) as navar
              from `entrylist_view`  e

              join `items_view` i on e.`item_id` = i.`item_id`
             join `documents_view` d on d.`document_id` = e.`document_id`
               where e.`item_id` >0 {$u} and e.`quantity` <>0
               and d.`meta_name` in ('GoodsIssue','ServiceAct' ,'POSCheck','ReturnIssue')
 
              AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
                group by    i.`cat_name`
               order  by i.`cat_name`
        ";
        }

        $totsum = 0;
        $totnavar = 0;

        $rs = $conn->Execute($sql);

        foreach ($rs as $row) {
            $detail[] = array(
                "code" => $row['item_code'],
                "name" => $row['itemname'],
                "dt" => \App\Helper::fd(strtotime($row['dt'])),
                "qty" => H::fqty($row['qty']),
                "navar" => H::fa($row['navar']),
                "navarsign" => $row['navar'] > 0,
                "summa" => H::fa($row['summa'])
            );
            
            $totnavar += $row['navar'];
            $totsum += $row['summa'];
        }

        $header = array('datefrom' => \App\Helper::fd($from),
            "_detail" => $detail,
            'dateto' => \App\Helper::fd($to)
        );
     
        $header['totsumma'] = H::fa($totsum);
        $header['totnavar'] = H::fa($totnavar);
     
        
        if ($type == 1 || strlen($cat) > 0) {
            $header['_type1'] = true;
            $header['_type2'] = false;
            $header['_type3'] = false;
            $header['_type4'] = false;
            $header['_type5'] = false;
        }
        if ($type == 2) {
            $header['_type1'] = false;
            $header['_type2'] = true;
            $header['_type3'] = false;
            $header['_type4'] = false;
            $header['_type5'] = false;
        }
        if ($type == 3) {
            $header['_type1'] = false;
            $header['_type2'] = false;
            $header['_type3'] = true;
            $header['_type4'] = false;
            $header['_type5'] = false;
        }
        if ($type == 4) {
            $header['_type1'] = false;
            $header['_type2'] = false;
            $header['_type3'] = false;
            $header['_type4'] = true;
            $header['_type5'] = false;
        }
        if ($type == 5 && strlen($cat) == 0) {
            $header['_type1'] = false;
            $header['_type2'] = false;
            $header['_type3'] = false;
            $header['_type4'] = false;
            $header['_type5'] = true;
        }


        $report = new \App\Report('report/outcome.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

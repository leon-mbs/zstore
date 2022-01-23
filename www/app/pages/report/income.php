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
 * Отчет по закупкам
 */
class Income extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('Income')) {
            return;
        }


        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));
        $this->filter->add(new DropDownChoice('type', array(1 => H::l('repbyitems'), 2 => H::l('repbysellers'), 3 => H::l('repbydates')), 1));

        $this->add(new Panel('detail'))->setVisible(false);
 
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

 
        $this->detail->setVisible(true);
    }

    private function generateReport() {

        $type = $this->filter->type->getValue();

        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();

        $br = "";
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $br = " and d.branch_id in ({$brids}) ";
        }

        $detail = array();
        $conn = \ZDB\DB::getConnect();

        if ($type == 1) {    //по товарам
            $sql = "
          select i.`itemname`,i.`item_code`,sum(e.`quantity`) as qty, sum(e.`outprice` * e.`quantity`) as summa
              from `entrylist_view`  e

              join `items` i on e.`item_id` = i.`item_id`
             join `documents_view` d on d.`document_id` = e.`document_id`
               where e.`item_id` >0  and (e.`tag` = 0 or e.`tag` = -2 ) 
               and d.`meta_name` in ('GoodsReceipt','RetCustIssue')
               {$br}
              AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
                group by  i.`itemname`,i.`item_code`
               order  by i.`itemname`
        ";
        }
        if ($type == 2) {  //по постащикам
            $sql = "
          select c.`customer_name` as itemname,c.`customer_id`,  sum(e.`outprice` * e.`quantity`) as summa
          from `entrylist_view`  e

         join `customers`  c on c.`customer_id` = e.`customer_id`
         join `documents_view`  d on d.`document_id` = e.`document_id`
           where e.`customer_id` >0  and (e.`tag` = 0 or e.`tag` = -2 ) 
           and d.`meta_name` in ('GoodsReceipt','RetCustIssue')
           {$br}

          AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
              AND c.detail not like '%<isholding>1</isholding>%'  
  group by  c.`customer_name`,c.`customer_id`
  order  by c.`customer_name`
        ";
        }
        if ($type == 3) {   //по датам
            $sql = "
          select e.`document_date` as dt  ,  sum(e.`outprice` * e.`quantity`) as summa
              from `entrylist_view`  e

              join `items` i on e.`item_id` = i.`item_id`
             join `documents_view` d on d.`document_id` = e.`document_id`
               where e.`item_id` >0  and (e.`tag` = 0 or e.`tag` = -2 ) 
               and d.`meta_name` in ('GoodsReceipt','RetCustIssue')
                {$br}

               AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
         group by  e.`document_date`
  order  by e.`document_date`
        ";
        }

        $total = 0;
        $rs = $conn->Execute($sql);

        foreach ($rs as $row) {
            $detail[] = array(
                "code"  => $row['item_code'],
                "name"  => $row['itemname'],
                "dt"    => H::fd(strtotime($row['dt'])),
                "qty"   => H::fqty($row['qty']),
                "summa" => H::fa($row['summa'])
            );
            $total += $row['summa'];
        }


        $header = array('datefrom' => H::fd($from),
                        "_detail"  => $detail,
                        'dateto'   => H::fd($to)
        );

        $header['total'] = H::fa($total);

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
        $report = new \App\Report('report/income.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

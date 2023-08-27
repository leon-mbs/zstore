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
        $this->filter->add(new DropDownChoice('type', array(1 => "За товарами", 2 => "За постачальниками", 3 => "За датами",4 => "Послуги, роботи",5 => "Товари за постачальниками",6=>'За категоріями'), 1))->onChange($this, "OnType");
        $this->filter->add(new DropDownChoice('cat', \App\Entity\Category::getList(false, false), 0))->setVisible(false);


        $this->filter->add(new \Zippy\Html\Form\AutocompleteTextInput('cust'))->onText($this, 'OnAutoCustomer');
        $this->filter->cust->setVisible(false);


        $this->add(new Panel('detail'))->setVisible(false);

        $this->detail->add(new Label('preview'));
    }

    public function OnAutoCustomer($sender) {
        $text = \App\Entity\Customer::qstr('%' . $sender->getText() . '%');
        return \App\Entity\Customer::findArray("customer_name", "status=0 and (customer_name like {$text}  or phone like {$text} )");
    }

    public function OnType($sender) {
        $type = $this->filter->type->getValue();

        $this->filter->cust->setVisible($type == 5);

        $this->filter->cat->setVisible($type == 6);

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
        $cust_id = $this->filter->cust->getKey();
        $cat_id = $this->filter->cat->getValue();

        $cust = "";
        $sql = "";

        $br = "";
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $br = " and d.branch_id in ({$brids}) ";
        }
        $cat = "";
        if ($type == 6 && $cat_id > 0) {
            $cat = " and cat_id=" . $cat_id;
        }

        $detail = array();
        $conn = \ZDB\DB::getConnect();

        if ($type == 1 || $type==5 || strlen($cat) > 0) {    //по товарам

            $cust = "";

            if (($type == 5) && $cust_id > 0) {
                $cust = " and d.customer_id=" . $cust_id;

            }

            $sql = "
             select i.itemname,i.item_code,sum(e.quantity) as qty, sum(e.outprice * e.quantity) as summa
              from entrylist_view  e

              join items i on e.item_id = i.item_id
             join documents_view d on d.document_id = e.document_id
               where e.item_id >0  and (e.tag = 0 or e.tag = -2 or e.tag = -8  ) {$cat}   {$cust} 
               and d.meta_name in ('GoodsReceipt','RetCustIssue')
               {$br}
              AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
                group by  i.itemname,i.item_code
               order  by i.itemname
        ";
        }
        if ($type == 2) {  //по постащикам
            $sql = "
          select c.customer_name as itemname,c.customer_id,  sum(e.outprice * e.quantity) as summa
          from entrylist_view  e

         join customers  c on c.customer_id = e.customer_id
         join documents_view  d on d.document_id = e.document_id
           where e.customer_id >0  and (e.tag = 0 or e.tag = -2 or e.tag = -8  ) 
           and d.meta_name in ('GoodsReceipt','RetCustIssue')
           {$br}

          AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
              AND c.detail not like '%<isholding>1</isholding>%'  
  group by  c.customer_name,c.customer_id
  order  by c.customer_name
        ";
        }
        if ($type == 3) {   //по датам
            $sql = "
          select e.document_date as dt  ,  sum(e.outprice * e.quantity) as summa
              from entrylist_view  e

              join items i on e.item_id = i.item_id
             join documents_view d on d.document_id = e.document_id
               where e.item_id >0  and (e.tag = 0 or e.tag = -2 or e.tag = -8 ) 
               and d.meta_name in ('GoodsReceipt','RetCustIssue')
                {$br}

               AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
         group by  e.document_date
  order  by e.document_date
        ";
        }
        if ($type == 4) {    //по сервисам
            $sql = "
         select s.service_name as itemname, sum(e.quantity) as qty, sum(e.outprice*e.quantity) as summa    ,0 as navar
              from entrylist_view  e

              join services s on e.service_id = s.service_id
             join documents_view d on d.document_id = e.document_id
               where e.service_id >0  and e.quantity <>0      {$cust}  
              and d.meta_name in (  'IncomeService'  )
               {$br}  AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
                   group by s.service_name
               order  by s.service_name      ";
        }

        if ($type == 6 && strlen($cat) == 0) {    //по категориях
            $sql = "
            select  i.cat_name as itemname,sum(e.quantity) as qty, sum( e.quantity*e.partion) as summa, sum((e.outprice-e.partion )*(0-e.quantity)) as navar
              from entrylist_view  e

              join items_view i on e.item_id = i.item_id
             join documents_view d on d.document_id = e.document_id
               where  e.partion  is  not null and  e.item_id >0  and (e.tag = 0 or e.tag = -2 or e.tag = -8  ) 
               and d.meta_name in ('GoodsReceipt','RetCustIssue' )
                {$br} 
              AND DATE(e.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(e.document_date) <= " . $conn->DBDate($to) . "
                group by    i.cat_name
               order  by i.cat_name
        ";
        }


        H::log($sql) ;
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

        if ($type == 1 || $type==5 || strlen($cat) > 0) {
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


        if ($type == 6 && strlen($cat) == 0) {
            $header['_type1'] = false;
            $header['_type2'] = false;
            $header['_type3'] = false;
            $header['_type4'] = false;
            $header['_type5'] = true;
        }

        $report = new \App\Report('report/income.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

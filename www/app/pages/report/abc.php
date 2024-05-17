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
 * АВС анализ
 */
class ABC extends \App\Pages\Base
{
    private $typelist = array();
    private $br       = '';

    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowReport('ABC')) {
            return;
        }

        $this->typelist[1] = "Товари, Маржа";
        $this->typelist[2] = "Постачальники, об`єм поставок";
        $this->typelist[3] = "Покупці, об`єм продаж";
        $this->typelist[4] = "Послуги, виторг";
        $this->typelist[5] = "Покупці, Маржа";


        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));
        $this->filter->add(new DropDownChoice('type', $this->typelist, 1));

        $this->add(new Panel('detail'))->setVisible(false);

        $this->detail->add(new Label('preview'));

        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $this->br = " and documents_view.branch_id in ({$brids}) ";
        }
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

        $detail = array();

        if ($type == 1) {     //Товары,  прибыль
            $detail = $this->find1();
        }
        if ($type == 2) {    //Поставщики, объем поставок
            $detail = $this->find2();
        }
        if ($type == 3) {  //Покупатели, объем продаж"
            $detail = $this->find3();
        }
        if ($type == 4) {   //Услуги, выручка
            $detail = $this->find4();
        }
        if ($type == 5) {  //Покупатели, прибыль
            $detail = $this->find5();
        }

        $detail = $this->calc($detail);

        $total = 0;
        $totala = 0;
        $totalb = 0;
        $totalc = 0;
        foreach ($detail as $d) {
            $total += $d['value'];
            if ($d['group'] == 'A') {
                $totala += $d['value'];
            }
            if ($d['group'] == 'B') {
                $totalb += $d['value'];
            }
            if ($d['group'] == 'C') {
                $totalc += $d['value'];
            }
        }


        $header = array('from'    => \App\Helper::fd($from),
                        "_detail" => $detail,
                        'to'      => \App\Helper::fd($to),
                        "type"    => $this->typelist[$type],
                        'totala'  => $totala,
                        'totalb'  => $totalb,
                        'totalc'  => $totalc,
                        'total'   => $total
        );
        $report = new \App\Report('report/abc.tpl');

        $html = $report->generate($header);

        return $html;
    }

    private function find1() {
        $list = array();
        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT * FROM (
                    SELECT items.itemname as name, ABS( SUM( (outprice-partion )*quantity ) ) AS value
                    FROM  entrylist_view 
                       join items on entrylist_view.item_id = items.item_id 
                       join documents_view  on entrylist_view.document_id = documents_view.document_id 
                       
                    WHERE partion  is  not null and outprice>partion and documents_view.meta_name   in('GoodsIssue', 'POSCheck','ReturnIssue','TTN','OrderFood') 
                    AND entrylist_view.tag in(0,-1,-4)  
                    AND entrylist_view.document_date >= " . $conn->DBDate($this->filter->from->getDate()) . "
                    AND entrylist_view.document_date <= " . $conn->DBDate($this->filter->to->getDate()) . "
                    {$this->br} 
                    GROUP BY name
                    )t
                 
                    ORDER BY value DESC";

        $rs = $conn->Execute($sql);
        foreach ($rs as $row) {
            $row['value'] = round($row['value']);
            $list[] = $row;
        }

        return $list;
    }

    private function find2() {
        $list = array();
        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT * FROM (
                    SELECT customers.customer_name as name, SUM( ABS( partion *quantity ) ) AS value
                    FROM  entrylist_view 
                    join customers on entrylist_view.customer_id = customers.customer_id 
                    join documents_view  on entrylist_view.document_id = documents_view.document_id 
                    WHERE  partion  is  not null and   entrylist_view.quantity  >0 and meta_name in('GoodsReceipt','RetCustIssue') 
                    AND entrylist_view.tag in(0,-2,-8)  
                    AND entrylist_view.document_date >= " . $conn->DBDate($this->filter->from->getDate()) . "
                    AND entrylist_view.document_date <= " . $conn->DBDate($this->filter->to->getDate()) . "
                    AND customers.detail not like '%<isholding>1</isholding>%' 
                    {$this->br} 
                    GROUP BY name
                    )t    
                    ORDER BY value DESC";

        $rs = $conn->Execute($sql);
        foreach ($rs as $row) {
            $row['value'] = round($row['value']);
            $list[] = $row;
        }

        return $list;
    }

    private function find3() {
        $list = array();
        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT * FROM (
                    SELECT customers.customer_name as name, SUM( ABS( partion *quantity ) ) AS value
                    FROM  entrylist_view 
                    join customers on entrylist_view.customer_id = customers.customer_id 
                    join documents_view  on entrylist_view.document_id = documents_view.document_id 
                    WHERE   partion  is  not null and  entrylist_view.quantity <0 and meta_name in('GoodsIssue',  'ReturnIssue',  'POSCheck','TTN','OrderFood' )  
                    AND entrylist_view.document_date >= " . $conn->DBDate($this->filter->from->getDate()) . "
                    AND entrylist_view.document_date <= " . $conn->DBDate($this->filter->to->getDate()) . "
                    AND customers.detail not like '%<isholding>1</isholding>%' 
                    {$this->br} 
                    GROUP BY name
                    )t      
                    ORDER BY value DESC";

        $rs = $conn->Execute($sql);
        foreach ($rs as $row) {
            $row['value'] = round($row['value']);
            $list[] = $row;
        }

        return $list;
    }

    private function find4() {
        $list = array();
        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT * FROM (
                    SELECT services.service_name as name, SUM( ABS( entrylist_view.outprice *entrylist_view.quantity ) ) AS value
                    FROM  entrylist_view 
                       join services on entrylist_view.service_id = services.service_id 
                       join documents_view  on entrylist_view.document_id = documents_view.document_id 
                       
                    WHERE     entrylist_view.outprice>0  and meta_name in('ServiceAct') 
                    AND entrylist_view.document_date >= " . $conn->DBDate($this->filter->from->getDate()) . "
                    AND entrylist_view.document_date <= " . $conn->DBDate($this->filter->to->getDate()) . "
                    {$this->br}  
                    GROUP BY name
                    )t  
                    ORDER BY value DESC";

        $rs = $conn->Execute($sql);
        foreach ($rs as $row) {
            $row['value'] = round($row['value']);
            $list[] = $row;
        }

        return $list;
    }

    private function find5() {
        $list = array();
        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT * FROM (
                    SELECT customers.customer_name as name, SUM( ABS( (outprice-partion )*quantity  ) ) AS value
                    FROM   entrylist_view  
                    join customers on entrylist_view.customer_id = customers.customer_id 
                    join documents_view  on entrylist_view.document_id = documents_view.document_id 
                    WHERE partion  is  not null and outprice>partion and entrylist_view.quantity <0 and meta_name in('GoodsIssue', 'ReturnIssue',   'POSCheck','TTN','OrderFood' )  
                    AND entrylist_view.document_date >= " . $conn->DBDate($this->filter->from->getDate()) . "
                    AND entrylist_view.document_date <= " . $conn->DBDate($this->filter->to->getDate()) . "
                    {$this->br} 
                    GROUP BY name
                    )t      
                    ORDER BY value DESC";

        $rs = $conn->Execute($sql);
        foreach ($rs as $row) {
            $row['value'] = round($row['value'] / 1000);
            $list[] = $row;
        }

        return $list;
    }

    //выполняет расчет  АВС
    private function calc($detail) {


        $sum = 0;
        $_detail = array();
        foreach ($detail as $row) {

            $row['value'] = round($row['value']);
            $sum += $row['value'];
            $row['perc'] = 0;
            $row['percsum'] = 0;
            $row['group'] = '';
            $row['color'] = '';
            $_detail[] = $row;
        }
        $val = 0;
        for ($i = 0; $i < count($_detail); $i++) {
            if ($sum == 0) {
                continue;
            }
            $_detail[$i]['perc'] = $_detail[$i]['value'] / $sum * 100;
            //  $_detail[$i]['value'] = $_detail[$i]['value'];
            $_detail[$i]['percsum'] = $_detail[$i]['perc'] + $val;
            if ($_detail[$i]['percsum'] <= 80) {
                $_detail[$i]['group'] = 'A';
                $_detail[$i]['color'] = '#AAFFAA';
            } else {
                if ($_detail[$i]['percsum'] <= 95) {
                    $_detail[$i]['group'] = 'B';
                    $_detail[$i]['color'] = 'CCCCFF';
                } else {
                    $_detail[$i]['group'] = 'C';
                    $_detail[$i]['color'] = 'yellow';
                }
            }
            $val = $_detail[$i]['percsum'];
            $_detail[$i]['perc'] = number_format($_detail[$i]['perc'], 2, '.', '');
            $_detail[$i]['percsum'] = number_format($_detail[$i]['percsum'], 2, '.', '');
        }
        return $_detail;
    }

}

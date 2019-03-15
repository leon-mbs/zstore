<?php

namespace App\Pages\Report;

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

    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowReport('ABC'))
            return;

        $this->typelist[1] = "Товары,  прибыль";
        $this->typelist[2] = "Поставщики, объем поставок";
        $this->typelist[3] = "Покупатели, объем продаж";
        $this->typelist[4] = "Услуги, выручка";

        $dt = new \Carbon\Carbon;
        $dt->subMonth();
        $from = $dt->startOfMonth()->timestamp;
        $to = $dt->endOfMonth()->timestamp;

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('from', $from));
        $this->filter->add(new Date('to', $to));
        $this->filter->add(new DropDownChoice('type', $this->typelist, 1));


        $this->add(new Panel('detail'))->setVisible(false);
        $this->detail->add(new RedirectLink('print', "abc"));
        $this->detail->add(new RedirectLink('html', "abc"));
        $this->detail->add(new RedirectLink('excel', "abc"));
        $this->detail->add(new RedirectLink('pdf', "abc"));
        $this->detail->add(new Label('preview'));
    }

    public function OnSubmit($sender) {

        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

        $reportpage = "App/Pages/ShowReport";
        $reportname = "abc";


        $this->detail->print->pagename = $reportpage;
        $this->detail->print->params = array('print', $reportname);
        $this->detail->html->pagename = $reportpage;
        $this->detail->html->params = array('html', $reportname);
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
//$image = 'z:/home/local.zstore/www/assets/images/noimage.jpg';
//$imageData = base64_encode(file_get_contents($image));
//$fi=getimagesize($image);
//$src = 'data:'.$fi['mime'].';base64,'.$imageData;




        $detail = array();

        if ($type == 1) {
            $detail = $this->find1();
        }
        if ($type == 2) {
            $detail = $this->find2();
        }
        if ($type == 3) {
            $detail = $this->find3();
        }
        if ($type == 4) {
            $detail = $this->find4();
        }

        $detail = $this->calc($detail);

        $header = array('from' => date('d.m.Y', $from),
            "_detail" => $detail,
            'to' => date('d.m.Y', $to),
            // 'img'=>  '<img src="'.$src.'">' ,
            "type" => $this->typelist[$type]
        );
        $report = new \App\Report('abc.tpl');

        $html = $report->generate($header);

        return $html;
    }

    private function find1() {
        $list = array();
        $conn = \ZDB\DB::getConnect();
        $sql = "SELECT * FROM (
                    SELECT items.itemname as name, SUM( ABS( extcode*quantity ) ) AS value
                    FROM  `entrylist_view` 
                       join items on entrylist_view.item_id = items.item_id 
                       join documents_view  on entrylist_view.document_id = documents_view.document_id 
                       
                    WHERE   extcode <>0  and meta_name in('GoodsIssue','ReturnIssue') 
                    AND entrylist_view.document_date >= " . $conn->DBDate($this->filter->from->getDate()) . "
                    AND entrylist_view.document_date <= " . $conn->DBDate($this->filter->to->getDate()) . "
                     
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
                    SELECT customers.customer_name as name, SUM( ABS( entrylist_view.amount ) ) AS value
                    FROM  `entrylist_view` 
                    join customers on entrylist_view.customer_id = customers.customer_id 
                    join documents_view  on entrylist_view.document_id = documents_view.document_id 
                    WHERE   entrylist_view.amount  >0 and meta_name in('GoodsReceipt') 
                    AND entrylist_view.document_date >= " . $conn->DBDate($this->filter->from->getDate()) . "
                    AND entrylist_view.document_date <= " . $conn->DBDate($this->filter->to->getDate()) . "
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
                    SELECT customers.customer_name as name, SUM( ABS( entrylist_view.amount ) ) AS value
                    FROM  `entrylist_view` 
                    join customers on entrylist_view.customer_id = customers.customer_id 
                    join documents_view  on entrylist_view.document_id = documents_view.document_id 
                    WHERE   entrylist_view.amount <0 and meta_name in('GoodsIssue')  
                    AND entrylist_view.document_date >= " . $conn->DBDate($this->filter->from->getDate()) . "
                    AND entrylist_view.document_date <= " . $conn->DBDate($this->filter->to->getDate()) . "
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
                    SELECT services.service_name as name, SUM( ABS( entrylist_view.amount*entrylist_view.quantity ) ) AS value
                    FROM  `entrylist_view` 
                       join services on entrylist_view.service_id = services.service_id 
                       join documents_view  on entrylist_view.document_id = documents_view.document_id 
                       
                    WHERE  entrylist_view.amount>0  and meta_name in('ServiceAct') 
                    AND entrylist_view.document_date >= " . $conn->DBDate($this->filter->from->getDate()) . "
                    AND entrylist_view.document_date <= " . $conn->DBDate($this->filter->to->getDate()) . "
                     
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

    //выполняет расчет  АВС
    private function calc($detail) {

        //   $detail =  \Pinq\Traversable::from($detail)
        //       ->orderByAscending(function($row){return $row['value'];})
        //       ->select(function($row){ return array('name'=>$row['name'],'value'=>$row['value'])   ;})->asArray();


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
            $_detail[$i]['perc'] = $_detail[$i]['value'] / $sum * 100;
            $_detail[$i]['value'] = $_detail[$i]['value'];
            $_detail[$i]['percsum'] = $_detail[$i]['perc'] + $val;
            if ($_detail[$i]['percsum'] <= 80) {
                $_detail[$i]['group'] = 'A';
                $_detail[$i]['color'] = '#AAFFAA';
            } else if ($_detail[$i]['percsum'] <= 95) {
                $_detail[$i]['group'] = 'B';
                $_detail[$i]['color'] = 'CCCCFF';
            } else {
                $_detail[$i]['group'] = 'C';
                $_detail[$i]['color'] = 'yellow';
            }
            $val = $_detail[$i]['percsum'];
            $_detail[$i]['perc'] = number_format($_detail[$i]['perc'], 2, '.', '');
            $_detail[$i]['percsum'] = number_format($_detail[$i]['percsum'], 2, '.', '');
        }
        return $_detail;
    }

}

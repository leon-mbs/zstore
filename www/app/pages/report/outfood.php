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
 * отчет по  кафе
 */
class OutFood extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('OutFood')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));
        $this->filter->add(new DropDownChoice('rtype'));
        $this->filter->add(new DropDownChoice('btype'));

        $this->add(new Panel('detail'))->setVisible(false);

        $this->detail->add(new Label('preview'));
    }

    public function OnSubmit($sender) {


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";


        $this->detail->setVisible(true);
    }

    private function generateReport() {

        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();
        $rtype = $this->filter->rtype->getValue();
        $btype = $this->filter->btype->getValue();

        $detail = array();
        $detail2 = array();

        $conn = \ZDB\DB::getConnect();

        $forbar="";
        if($btype == 1) {
           $forbar = " and content like " .$conn->qstr('%<forbar>0</forbar>%' );    
        }        
        if($btype == 2) {
           $forbar = " and content like " .$conn->qstr('%<forbar>1</forbar>%' );    
        }        


        if($rtype ==0) {

            $sql = "
              SELECT  document_date  AS dt,COUNT(*) AS qty  FROM documents_view dv  
                WHERE  dv.meta_name='OrderFood' AND  state = 9
                AND dv.document_date >= " . $conn->DBDate($from) . "
                AND dv.document_date <= " . $conn->DBDate($to) . "
                {$forbar}
                GROUP BY dt
                ORDER BY dt 
                        ";

            $rs = $conn->Execute($sql);


            $days =[];
            $days[1]= 'Пн';
            $days[2]= 'Вт';
            $days[3]= 'Ср';
            $days[4]= 'Чт';
            $days[5]= 'Пт';
            $days[6]= 'Сб';
            $days[0]= 'Нд';

            foreach ($rs as $row) {
                $t = strtotime($row['dt']) ;
                $w = date('w', $t);
                $detail[] = array(
                    "dt"  =>  H::fd($t),
                    "day"  => $days[$w],

                    "qty" => intval($row['qty'])
                );

            }

        }

        if($rtype == 1) {
  
            
            $sql = " dv.meta_name='OrderFood' AND  state = 9      
               AND DATE(dv.document_date) >= " . $conn->DBDate($from) . "
                AND DATE(dv.document_date) <= " . $conn->DBDate($to).' ' ;

            $sql="select i.itemname, sum(0-e.quantity) as qty,sum((e.outprice )*(0-e.quantity)) as am from entrylist_view e 
          join  items i on e.item_id = i.item_id 
          where   (e.tag = 0 or e.tag = -1  or e.tag = -4)
          and document_id in( select document_id from documents_view dv where  dv.meta_name='OrderFood' AND  state = 9      
              {$forbar}
               AND DATE(dv.document_date) >= " . $conn->DBDate($from) . "
                AND DATE(dv.document_date) <= " . $conn->DBDate($to).' ) 
               group  by  i.itemname
               order  by  i.itemname ';


            $rs = $conn->Execute($sql);

            foreach ($rs as $row) {

                $detail2[] = array(
                    "name"  => $row['itemname'],
                    "qty"  => H::fqty($row['qty']),

                    "sum" => H::fa($row['am'])
                );

            }

        }

        $header = array('datefrom' => \App\Helper::fd($from),
                        "_detail"  => $detail,
                        "_detail2" => $detail2,
                        "isd1" => $rtype ==0,
                        "isd2" => $rtype==1,
                        'dateto'   => \App\Helper::fd($to)
                      );

        $report = new \App\Report('report/outfood.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

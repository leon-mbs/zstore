<?php

namespace App\Pages\Report;

use App\Entity\Item;
use App\Helper as H;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 * возвраты  от  покупателей
 */
class Returnselled extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowReport('Returnselled')) {
            return;
        }

        $this->add(new Panel('detail'))->setVisible(false);

        $this->detail->add(new Label('preview'));


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";


        $this->detail->setVisible(true);

    }



    private function generateReport() {
        $conn = \ZDB\DB::getConnect();


        $br = "";
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $br = " and d.branch_id in ({$brids}) ";
        }

        $conn = $conn = \ZDB\DB::getConnect();
        $this->data = array();


        $sql = "
            select itemname,item_code,sellqty,rqty,rqty/(0-sellqty) as pr from (
            select * from (
          select i.itemname,i.item_code,
              sum( case when e.quantity < 0 then e.quantity  else 0 end ) as sellqty,    
              sum( case when e.quantity > 0 then e.quantity  else 0 end ) as rqty    
              from entrylist_view  e

              join items_view i on e.item_id = i.item_id    and i.disabled<> 1 
              join documents_view d on d.document_id = e.document_id
               where e.partion  is  not null and  e.item_id >0  and e.quantity <> 0    
               and d.meta_name in ('GoodsIssue', 'POSCheck','ReturnIssue','TTN' )
               {$br}   
               group by  i.itemname,i.item_code
               )  t where  t.rqty >0  ) t2   where  rqty/(0-sellqty) >= 0.01 order  by rqty/(0-sellqty) desc 
        ";


        $detail = array();
        $res = $conn->Execute($sql);
        foreach ($res as $item) {

            $item['sellqty'] = H::fqty(0-$item['sellqty']);
            $item['rqty'] = H::fqty($item['rqty']);
            $item['pr'] = number_format($item['pr'] *100, 1, '.', '') ;
            $detail[] = $item;
        }


        $header = array(
            "_detail" => $detail

        );
        $report = new \App\Report('report/returnselled.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

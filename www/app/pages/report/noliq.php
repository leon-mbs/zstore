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
 * Неликвидные товары
 */
class NoLiq extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowReport('NoLiq')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');

        $this->filter->add(new DropDownChoice('mqty', array("1" => "1", "3" => "3", "6" => "6", "12" => "12"), 1));

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

        $mqty = $this->filter->mqty->getValue();

        $cstr = \App\ACL::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = " and st.store_id in ({$cstr})    ";
        }

        $conn = $conn = \ZDB\DB::getConnect();
        $this->data = array();
        $date = strtotime('-' . $mqty . ' month');

        $sql = "select  st.item_id from  store_stock_view  st where st.itemdisabled <> 1  and  st.qty >0 
               {$cstr} and   st.stock_id not  in(select   stock_id    
               from  entrylist_view  
               where    document_date >" . $conn->DBDate($date) . "  and  quantity < 0  AND stock_id  IS  NOT  null) 
               and   st.stock_id    in(select   stock_id    
               from  entrylist_view  
               where    document_date <" . $conn->DBDate($date) . "  and  quantity > 0  AND stock_id  IS  NOT  null) 
                
               group by st.item_id
               order by st.itemname
                 ";

        $detail = array();
        $res = $conn->Execute($sql);
        foreach ($res as $_it) {

            $sql = "  select coalesce(sum(qty),0) as totqty  from  store_stock  where item_id = {$_it['item_id']} ";

            
            
            $item=[];
            $item['qty'] = H::fqty($conn->GetOne($sql));
            if($item['qty']  >0) {
                $i = Item::load($_it['item_id']) ;
                $item['itemname']  = $i->itemname;
                $item['item_code']  = $i->item_code;
                $item['bar_code']  = $i->bar_code;
                $item['msr']  = $i->msr;
                $item['brand']  = $i->manufacturer;
                $detail[] = $item;
            }

        }


        $header = array(
            "_detail" => $detail,
            'mqty'    => $this->filter->mqty->getValue()
        );
        $report = new \App\Report('report/noliq.tpl');

        $html = $report->generate($header);

        return $html;
    }

}

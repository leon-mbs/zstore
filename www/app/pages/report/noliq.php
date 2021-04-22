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
        $this->detail->add(new \Zippy\Html\Link\BookmarkableLink('print', ""));
        $this->detail->add(new RedirectLink('word', "movereport"));
        $this->detail->add(new RedirectLink('excel', "movereport"));
        $this->detail->add(new RedirectLink('pdf', "movereport"));
        $this->detail->add(new Label('preview'));
    }

    public function OnSubmit($sender) {


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

        // \ZippyERP\System\Session::getSession()->storereport = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
        $reportpage = "App/Pages/ShowReport";
        $reportname = "emptask";

        $this->detail->word->pagename = $reportpage;
        $this->detail->word->params = array('doc', $reportname);
        $this->detail->excel->pagename = $reportpage;
        $this->detail->excel->params = array('xls', $reportname);
        $this->detail->pdf->pagename = $reportpage;
        $this->detail->pdf->params = array('pdf', $reportname);

        $this->detail->setVisible(true);
    }

    private function generateReport() {

        $mqty = $this->filter->mqty->getValue();

        $cstr = \App\Acl::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = " and st.store_id in ({$cstr})    ";
        }

        $conn = $conn = \ZDB\DB::getConnect();
        $this->data = array();
        $date = strtotime('-' . $mqty . ' month');

        $sql = "select coalesce(sum(st.qty),0) as qty, st.itemname,st.item_code,st.storename from  store_stock_view  st where st.itemdisabled <> 1  and  st.qty >0 
               {$cstr} and   st.stock_id not  in(select   stock_id    
               from  entrylist_view  
               where    document_date >" . $conn->DBDate($date) . "  and  quantity < 0 ) 
               and   st.stock_id    in(select   stock_id    
               from  entrylist_view  
               where    document_date <" . $conn->DBDate($date) . "  and  quantity > 0 ) 
                
               group by  st.itemname,st.item_code,st.storename 
               order by  qty  desc
                 ";

        $detail = array();
        $res = $conn->Execute($sql);
        foreach ($res as $item) {
            $item['qty'] = H::fqty($item['qty']);
            $detail[] = $item;
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

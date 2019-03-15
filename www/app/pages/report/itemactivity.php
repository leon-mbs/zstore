<?php

namespace App\Pages\Report;

use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\AutocompleteTextInput;
use \Zippy\Html\Label;
use \Zippy\Html\Link\RedirectLink;
use \Zippy\Html\Panel;
use \App\Entity\Item;
use \App\Entity\Store;
use \App\Helper as H;
use App\Application as App;

/**
 * Движение товара
 */
class ItemActivity extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('ItemActivity'))
            return;

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));
        $this->filter->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));


        $this->filter->add(new AutocompleteTextInput('item'))->onText($this, 'OnAutoItem');
         $this->add(new \Zippy\Html\Link\ClickLink('autoclick'))->onClick($this, 'OnAutoLoad', true);

        $this->add(new Panel('detail'))->setVisible(false);
        $this->detail->add(new RedirectLink('print', "movereport"));
        $this->detail->add(new RedirectLink('html', "movereport"));
        $this->detail->add(new RedirectLink('word', "movereport"));
        $this->detail->add(new RedirectLink('excel', "movereport"));
        $this->detail->add(new RedirectLink('pdf', "movereport"));
        $this->detail->add(new Label('preview'));
        \App\Session::getSession()->issubmit = false;
    
    }

    public function OnAutoItem($sender) {
        $r = array();


        $text = Item::qstr('%' . $sender->getText() . '%');
        $list = Item::findArray('itemname', " (itemname like {$text} or item_code like {$text} ) ");
        foreach ($list as $k => $v) {
            $r[$k] = $v;
        }
        return $r;
    }

    public function OnSubmit($sender) {
        $itemid = $this->filter->item->getKey();


        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

        // \ZippyERP\System\Session::getSession()->storereport = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
        $reportpage = "App/Pages/ShowReport";
        $reportname = "movereport";


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
        
        $this->detail->preview->setText("<b >Загрузка...</b>",true);
        \App\Session::getSession()->printform = "";
        \App\Session::getSession()->issubmit = true;
                 
    }

    private function generateReport() {

        $storeid = $this->filter->store->getValue();
        $itemid = $this->filter->item->getKey();

        $it = $itemid > 0 ? "st.item_id=" . $itemid : "1=1";
        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();




        $i = 1;
        $detail = array();
        $conn = \ZDB\DB::getConnect();

        $sql = "
         SELECT  t.*,
          
         (
        SELECT  
          
          COALESCE(SUM(sc2.`quantity`), 0)  
         FROM entrylist_view sc2
          JOIN store_stock_view st2
            ON sc2.stock_id = st2.stock_id
          JOIN documents dc2
            ON sc2.document_id = dc2.document_id
              WHERE st2.item_id = t.item_id  
              AND st2.store_id = {$storeid} 
              AND sc2.document_date  < t.dt   
              GROUP BY st2.item_id 
                                 
         ) as begin_quantity   
         
          from (
           select
          st.item_id,
          st.itemname,
          st.item_code,
            st.stock_id,
          date(sc.document_date) AS dt,
          SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) AS obin,
          SUM(CASE WHEN quantity < 0 THEN 0 - quantity ELSE 0 END) AS obout,
          GROUP_CONCAT(dc.document_number) AS docs
        FROM entrylist_view sc
          JOIN store_stock_view st
            ON sc.stock_id = st.stock_id
          JOIN documents dc
            ON sc.document_id = dc.document_id
              WHERE {$it}  
              AND st.store_id = {$storeid}
              AND DATE(sc.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(sc.document_date) <= " . $conn->DBDate($to) . "
              GROUP BY st.item_id,
                       DATE(sc.document_date) ) t
              ORDER BY t.dt  
        ";


        $rs = $conn->Execute($sql);

        foreach ($rs as $row) {
            $detail[] = array(
                "code" => $row['item_code'],
                "name" => $row['itemname'],
                "date" => date("d.m.Y", strtotime($row['dt'])),
                "documents" => $row['docs'],
                "in" => H::fqty(strlen($row['begin_quantity']) > 0 ? $row['begin_quantity'] : 0),
                "obin" => H::fqty($row['obin']),
                "obout" => H::fqty($row['obout']),
                "out" => H::fqty($row['begin_quantity'] + $row['obin'] - $row['obout'])
            );
        }

        $header = array('datefrom' => date('d.m.Y', $from),
            "_detail" => $detail,
            'dateto' => date('d.m.Y', $to),
            "store" => Store::load($storeid)->storename
        );
        $report = new \App\Report('itemactivity.tpl');

        $html = $report->generate($header);

        return $html;
    }
  
    public function OnAutoLoad($sender) {

        if (\App\Session::getSession()->issubmit === true) {
            $html = $this->generateReport();
            \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
            $this->detail->preview->setText($html, true);
            $this->updateAjax(array('preview'));
        }
    }

    public function beforeRender() {
        parent::beforeRender();

        App::addJavaScript("\$('#autoclick').click()", true);
    }
}

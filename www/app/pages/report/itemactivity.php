<?php

namespace App\Pages\Report;

use App\Application as App;
use App\Entity\Item;
use App\Entity\Stock;
use App\Entity\Store;
use App\Helper as H;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 * Движение товара
 */
class ItemActivity extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('ItemActivity')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));

        $this->filter->add(new TextInput('snumber'))->setVisible(false);
        $this->filter->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));

        $this->filter->add(new AutocompleteTextInput('item'))->onText($this, 'OnAutoItem');
        $this->filter->item->onChange($this, "onItem");

        $this->add(new Panel('detail'))->setVisible(false);

        $this->detail->add(new Label('preview'));
        \App\Session::getSession()->issubmit = false;
    }

    public function OnAutoItem($sender) {
        $r = array();

        $text = Item::qstr('%' . $sender->getText() . '%');
        $list = Item::findArray('itemname', " (itemname like {$text} or item_code like {$text} or bar_code like {$text}  ) ");
        foreach ($list as $k => $v) {
            $r[$k] = $v;
        }
        return $r;
    }

    public function onItem($sender) {
        $this->filter->snumber->setVisible(false);

        $item = Item::load($sender->getKey());
        if ($item != null) {
            if ($item->useserial == 1) {
                $this->filter->snumber->setVisible(true);

            }

        }

    }

    public function OnSubmit($sender) {


        $this->detail->setVisible(true);

        $html = $this->generateReport();
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
        $this->detail->preview->setText($html, true);

        // $this->addJavaScript("loadRep()",true) ;

    }

    private function generateReport() {

        $storeid = $this->filter->store->getValue();
        $itemid = $this->filter->item->getKey();
        $snumber = $this->filter->snumber->getText();


        $it = "1=1";
        if ($itemid > 0) {
            $it .= " and st.item_id=" . $itemid;
        }
        if (strlen($snumber) > 0) {
            $it .= " and st.snumber=" . Stock::qstr($snumber);
        }

        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();

        $i = 1;
        $detail = array();
        $conn = \ZDB\DB::getConnect();
        $gd = " GROUP_CONCAT(distinct dc.document_number) ";
     

        $sql = "
         SELECT  t.*,
          
         (
        SELECT  
          
          COALESCE(SUM(sc2.quantity), 0)  
         FROM entrylist_view sc2
          JOIN store_stock_view st2
            ON sc2.stock_id = st2.stock_id
          JOIN documents dc2
            ON sc2.document_id = dc2.document_id
              WHERE st2.item_id = t.item_id  
              
              " . ($storeid > 0 ? " AND st2.store_id = {$storeid}  " : "") . "  
              AND sc2.document_date  < t.dt   
              GROUP BY st2.item_id 
                                 
         ) as begin_quantity ,
          
    (
        SELECT  
          
          COALESCE(SUM((st3.partion*sc3.quantity )), 0)  
         FROM entrylist_view sc3
          JOIN store_stock_view st3
            ON sc3.stock_id = st3.stock_id
          JOIN documents dc3
            ON sc3.document_id = dc3.document_id
              WHERE st3.item_id = t.item_id  
             " . ($storeid > 0 ? " AND st3.store_id = {$storeid}  " : "") . "  
              AND sc3.document_date  < t.dt   
              GROUP BY st3.item_id 
                                 
         ) as begin_amount  
                
          from (
           select
          st.item_id,
          st.itemname,
          st.item_code,
         
    
          date(sc.document_date) AS dt,
          SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) AS obin,
          SUM(CASE WHEN quantity < 0 THEN 0 - quantity ELSE 0 END) AS obout,
          SUM(CASE WHEN (st.partion*sc.quantity ) > 0 THEN (st.partion*sc.quantity ) ELSE 0 END) AS obinamount,
          SUM(CASE WHEN (st.partion*sc.quantity )< 0 THEN 0 - (st.partion*sc.quantity ) ELSE 0 END) AS oboutamount
          
        FROM entrylist_view sc
          JOIN store_stock_view st
            ON sc.stock_id = st.stock_id
          JOIN documents dc
            ON sc.document_id = dc.document_id
              WHERE {$it}  
           " . ($storeid > 0 ? " AND st.store_id = {$storeid}  " : "") . "  
             AND DATE(sc.document_date) >= " . $conn->DBDate($from) . "
              AND DATE(sc.document_date) <= " . $conn->DBDate($to) . "
              GROUP BY st.store_id,st.item_id,
          st.itemname,
          st.item_code,   

                       DATE(sc.document_date) ) t
              ORDER BY t.dt  
        ";
        //  H::log($sql)  ;
        $rs = $conn->Execute($sql);
        $ba = 0;
        $bain = 0;
        $baout = 0;
        $bq = 0;
        $bqin = 0;
        $bqout = 0;


        foreach ($rs as $row) {

            $row['begin_quantity'] = doubleval($row['begin_quantity'])  ;
            $row['obin'] = doubleval($row['obin'])  ;
            $row['obout'] = doubleval($row['obout'])  ;

            $r = array(
                "code"  => $row['item_code'],
                "name"  => $row['itemname'],

                "date"      => \App\Helper::fd(strtotime($row['dt'])),
                "documents" => '',
                "in"        => H::fqty($row['begin_quantity']),
                "obin"      => H::fqty($row['obin']),
                "obout"     => H::fqty($row['obout']),
                "out"       => H::fqty($row['begin_quantity'] + $row['obin'] - $row['obout'])
            );

            $detail[] = $r;
            $ba = $ba + $row['begin_amount'];
            $bain = $bain + $row['obinamount'];
            $baout = $baout + $row['oboutamount'];
            $bq = $bq + $row['begin_quantity'];
            $bqin = $bqin + $row['obin'];
            $bqout = $bqout + $row['obout'];





        }


        $header = array('datefrom'      => \App\Helper::fd($from),
                        "_detail"       => $detail,
                        'noshowpartion' => \App\System::getUser()->noshowpartion,
                        'dateto'        => \App\Helper::fd($to),
                        "store"         => Store::load($storeid)->storename
        );

        $header['ba'] = H::fa($ba);
        $header['bain'] = H::fa($bain);
        $header['baout'] = H::fa($baout);
        $header['baend'] = H::fa($ba + $bain - $baout);
        $header['bq'] = H::fqty($bq);
        $header['bqin'] = H::fqty($bqin);
        $header['bqout'] = H::fqty($bqout);
        $header['bqend'] = H::fqty($bq + $bqin - $bqout);
        $header['showqty'] =$itemid >0 ;

        $report = new \App\Report('report/itemactivity.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function getData() {


        $html = $this->generateReport();
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

        return $html;

    }


}

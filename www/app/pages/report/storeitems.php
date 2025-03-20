<?php

namespace App\Pages\Report;

use App\Application as App;
use App\Entity\Item;
use App\Entity\Stock;
use App\Entity\Store;
use App\Entity\Category;
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
 * Состояние  складов
 */
class StoreItems extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('StoreItems')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new CheckBox('fminus'));
        $this->filter->add(new CheckBox('fmin'));
        $this->filter->add(new DropDownChoice('searchcat', Category::getList(), 0));
        $this->filter->add(new TextInput('searchkey'));

        $this->add(new Panel('detail'))->setVisible(false);

        $this->detail->add(new Label('preview'));
        \App\Session::getSession()->issubmit = false;
    }



    public function OnSubmit($sender) {


        $this->detail->setVisible(true);

        $html = $this->generateReport();
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
        $this->detail->preview->setText($html, true);

        // $this->addJavaScript("loadRep()",true) ;

    }

    private function generateReport() {

        $fmin = $this->filter->fmin->isChecked();
        $fminus = $this->filter->fminus->isChecked();
        $cat = $this->filter->searchcat->getValue();
        $searchkey = trim($this->filter->searchkey->getText());
        $where = 'disabled<>1 ' . ($cat>0 ? ' and cat_id=' . $cat : '') ;
        if(strlen($searchkey)>0) {
            $t = Item::qstr($searchkey)  ;
            $where .= " and (itemname  like ". Item::qstr('%'.$searchkey.'%') ." or item_code=".Item::qstr($searchkey)." ) ";
        }

        $itemlist = Item::find($where, 'itemname asc') ;
        $storelist = Store::getList() ;

        if(\App\System::getUser()->showotherstores) {
            $storelist = Store::getListAll() ;

        }
        $siqty = array();
        $stlist = array();


        $conn = \ZDB\DB::getConnect();


        $rs = $conn->Execute("select store_id, item_id, coalesce(sum(qty) ,0) as qty from store_stock_view where   itemdisabled<>1 group  by store_id,item_id    ") ;

        foreach ($rs as $row) {
            $qty = doubleval($row['qty']) ;

            $siqty[$row['store_id'].'_'.$row['item_id']] = $qty;

        }


        $detail = array();

        foreach ($itemlist as $item) {

            $r = array();
            $r['itemname']  =  $item->itemname;
            $r['item_code']  =  $item->item_code;
            $r['minqty']  =  $item->minqty>0 ? H::fqty($item->minqty) : '';

            $flag = true;
            $r['stlist']  =  array() ;
            foreach($storelist as $store_id=>$storename) {

                $qty =  $siqty[$store_id.'_'.$item->item_id] ?? 0;
                if(strlen($qty)==0) {
                    $qty=0;
                }

                if($fminus) {
                    if($qty <0) {
                        $flag = false;
                    }

                }
                if($fmin && $item->minqty>0) {
                    if($qty < $item->minqty) {
                        $flag = false;
                    }


                }

                if(!$fminus && !$fmin) {
                    if($qty >0) {
                        $flag = false;
                    }


                }
                $r['stlist'][]=array('qty'=>H::fqty($qty)) ;


            }

            if($flag) {
                continue;
            } //все  нули


            $detail[] = $r;

        }


        $header = array(  
                         "date"=>H::fd(time()),
                         "cols"=>count($storelist)+3 ,
                        "_detail"       => $detail,
                        "stores"         => \App\Util::tokv($storelist)
        );

        $report = new \App\Report('report/storeitems.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function getData() {


        $html = $this->generateReport();
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

        return $html;

    }


}

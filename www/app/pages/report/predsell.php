<?php

namespace App\Pages\Report;

use App\Helper as H;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

/**
 *  прогноз  продаж
 */
class PredSell extends \App\Pages\Base
{
    private $_cci = array();


    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowReport('PredSell')) {
            return;
        }

        $cats = \App\Entity\Category::findArray('cat_name', 'cat_id in(select cat_id from items where  disabled <>1)', 'cat_name');
        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new DropDownChoice('cat', $cats, 0));
        $this->filter->add(new DropDownChoice('type'));
        $this->filter->add(new TextInput('brand'));
        $this->filter->brand->setDataList(\App\Entity\Item::getManufacturers());

        $this->add(new Panel('detail'))->setVisible(false);

        $this->detail->add(new ClickLink('cci', $this, "onCCI"));
        $this->detail->add(new Label('preview'));


    }


    public function OnSubmit($sender) {

        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";


        $this->detail->setVisible(true);
    }

    private function generateReport() {
        $this->_cci = [];

        $cat = (int)$this->filter->cat->getValue();
        $type = (int)$this->filter->type->getValue();
        $brand = trim( $this->filter->brand->getText() );
        $conn = \ZDB\DB::getConnect();

        $tp = " (i.item_type=0 || i.item_type=1 ) ";
        if ($type == 1) {
            $tp = " (i.item_type=4 || i.item_type=5 ) ";
        }
        $br = "";
        if (strlen($brand)>0) {
            $br = " and i.manufacturer= ".$conn->qstr($brand);
        }
        $cs='';
        $c = \App\ACL::getBranchIDsConstraint();
        if($c != '') {
           $cs = " and store_id in ( select store_id from stores where  branch_id in ({$c}) ) "; 
           $c = " and d.branch_id in ({$c}) "; 
        }
        $onstore = [];
        $sql = "select sum(qty) as q,item_id from store_stock where  item_id in (select item_id from items where  disabled <> 1)  {$cs} group  by item_id";
        foreach ($conn->Execute($sql) as $r) {
            if ($r['q'] > 0) {
                $onstore[$r['item_id']] = $r['q'];
            }
        }
        $minqty = [];
        $sql = "select minqty,item_id from  items where  disabled <> 1 and minqty >0";
        foreach ($conn->Execute($sql) as $r) {
            if ($r['minqty'] > 0) {
                $minqty[$r['item_id']] = $r['minqty'];
            }
        }

        $inorder = [];

        $where = "   meta_name='OrderCust'  and  state= " . \App\Entity\Doc\Document::STATE_INPROCESS;

        foreach (\App\Entity\Doc\Document::findYield($where) as $doc) {

            foreach ($doc->unpackDetails('detaildata') as $item) {
                if (!isset($inorder[$item->item_id])) {
                    $inorder[$item->item_id] = 0;
                }
                $inorder[$item->item_id] += $item->quantity;

            }
        }
     

        $m1 = $conn->DBDate(strtotime('-1 month'));
        $m2 = $conn->DBDate(strtotime('-2 month'));
    
        $cati="";
        if($cat > 0) {
           $cati = " and i.cat_id=".$cat; 
        }
    

        $sql = "select i.item_id,i.itemname,i.item_code, 
        sum( case when d.document_date < now() and d.document_date >= {$m1} then 0-e.quantity else 0 end ) as m1,
        sum( case when d.document_date < {$m1} and d.document_date >= {$m2} then 0-e.quantity else 0 end ) as m2
        from entrylist_view e 
        join items i on e.item_id = i.item_id 
        join documents_view d on e.document_id = d.document_id 
        where  i.disabled <> 1 and d.meta_name in ('GoodsIssue','TTN','POSCheck','OrderFood','ReturnIssue') 
        {$cati} and i.item_id in(select item_id from entrylist_view ee where ee.quantity <0 and  ee.document_date < {$m2} ) 
        and {$tp}   {$br} {$c}  
        group  by i.item_id,i.itemname,i.item_code 
        order  by i.itemname 
        ";

        $rows = $conn->Execute($sql);

        $detail = [];

        foreach ($rows as $r) {

            $rqty = $r['m1'] + ($r['m1'] - $r['m2']);
            if ($rqty > 0) {
                $r['qty'] = H::fqty($rqty);

                if (($onstore[$r['item_id']]??0 ) > 0) {
                    $r['onstore'] = H::fqty($onstore[$r['item_id']]);
                    $rqty = $rqty - $onstore[$r['item_id']];  //на  складе
                }
                if (($inorder[$r['item_id']] ??0 )> 0) {
                    $rqty = $rqty - $inorder[$r['item_id']];  //заказано
                }

                $r['tobay'] = $rqty;

                if (($minqty[$r['item_id']] ??0 ) > 0) {
                    $r['tobay'] = $r['tobay'] + $minqty[$r['item_id']];  //плюс  минимальное  оличество
                }
                if ($r['tobay'] > 0) {
                    if ($type == 0) {
                        $this->_cci[$r['item_id']] = $r['tobay'];
                    }
                    if ($type == 1) {   //продукция

                        $set = \App\Entity\ItemSet::find("pitem_id=" . $r['item_id']);
                        foreach ($set as $part) {

                            if (!isset($this->_cci[$part->item_id])) {
                                $this->_cci[$part->item_id] = 0;
                                if ($onstore[$part->item_id] > 0) {
                                    $this->_cci[$part->item_id] = 0 - $onstore[$part->item_id];
                                }
                            }
                            $this->_cci[$part->item_id] += $part->qty;

                        }

                    }

                    $r['tobay'] = H::fqty($r['tobay']);
                    $detail[$r['item_id']] = $r;
                }
            }


        }

        $header = array("_detail" => array_values($detail),
                        "tovar"   => $type == 0
        );
        $report = new \App\Report('report/predsell.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function onCCI($sender) {

        foreach ($this->_cci as $item_id => $qty) {
            if ($qty > 0) {
                $this->addItemToCO([$item_id, $qty]);
            }
        }
    }

}

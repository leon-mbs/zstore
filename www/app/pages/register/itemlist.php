<?php

namespace App\Pages\Register;

use App\Entity\Category;
use App\Entity\Item;
use App\Entity\Stock;
use App\Entity\Store;
use App\Entity\Entry;
use App\Entity\Doc\Document;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\Application as App;

/**
* Товари на складі
*/
class ItemList extends \App\Pages\Base
{
    public $_item;
    public $_itemr=[];
    public $_itemb=[];
   

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('ItemList')) {
            \App\Application::RedirectHome() ;
        }
        $common = System::getOptions('common');
    
        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->filter->add(new TextInput('searchkey'));

        $catlist = array();
        $catlist[-1] = "Без категорії";
        foreach (Category::getList() as $k => $v) {
            $catlist[$k] = $v;
        }

        $this->filter->add(new DropDownChoice('searchcat', $catlist, 0));


        $prices = [];
        if($this->_tvars["noshowpartion"] == false) {
            $prices['price'] = "Закупівельна ціна";
        }

        foreach(Item::getPriceTypeList() as $k=>$v) {
            $prices[$k] = $v ;
        }

        $keys=array_keys($prices);
        $p=array_shift($keys);

        $this->filter->add(new DropDownChoice('searchprice', $prices, $p));
        $storelist = Store::getList() ;

        if(\App\System::getUser()->showotherstores) {
            $storelist = Store::getListAll() ;

        }
        $this->filter->add(new DropDownChoice('searchtype', [], 0));
        $this->filter->add(new DropDownChoice('searchqty', [], 0));
        $this->filter->add(new DropDownChoice('searchstore', $storelist, 0));
        $this->filter->add(new TextInput('searchbrand'));
        $this->filter->searchbrand->setDataList(Item::getManufacturers());

        $emplist = \App\Entity\Employee::findArray('emp_name','disabled<>1','emp_name')  ;
        
        $this->filter->add(new DropDownChoice('searchemp', $emplist, 0));
        $this->filter->add(new CheckBox('searchterm' ));
        
        $this->add(new Panel('itempanel'));

        $this->itempanel->add(new DataView('itemlist', new ItemDataSource($this), $this, 'itemlistOnRow'));

        $this->itempanel->itemlist->setPageSize(H::getPG());
        $this->itempanel->add(new \Zippy\Html\DataList\Paginator('pag', $this->itempanel->itemlist));

        $this->itempanel->add(new ClickLink('csv', $this, 'oncsv'));
        $this->itempanel->add(new ClickLink('printqty', $this, 'onprint', true));
        $this->itempanel->add(new Label('totamount'));

        $this->add(new Panel('detailpanel'))->setVisible(false);
        $this->detailpanel->add(new ClickLink('back'))->onClick($this, 'backOnClick');
        $this->detailpanel->add(new Label('itemdetname'));

        $this->detailpanel->add(new DataView('stocklist', new DetailDataSource($this), $this, 'detailistOnRow'));
        $this->detailpanel->add(new Form('iformbay'))->onSubmit($this,'OnToPay');
        $this->detailpanel->iformbay->add(new TextInput('iformbayqty'));
        
        //в закупке
        $where = "   meta_name='OrderCust'  and  state= " . Document::STATE_INPROCESS;
     
        foreach (Document::findYield($where) as $doc) {

            foreach ($doc->unpackDetails('detaildata') as $item) {
                if (!isset($this->_itemb[$item->item_id])) {
                    $this->_itemb[$item->item_id] = 0;
                }
                $this->_itemb[$item->item_id] += $item->quantity;
               
            }
        }
        
        //в резерве
        $conn = \ZDB\DB::getConnect() ;

        $sql = "SELECT  i.item_id, sum(ev.quantity) as qty  FROM entrylist_view ev 
                 JOIN items i ON ev.item_id = i.item_id
                 WHERE  tag = -64 
                 GROUP  BY   i.item_id   ";
                  

        $res = $conn->Execute($sql);        
        foreach($res as $r) {
            if (!isset($this->_itemr[$r['item_id']])) {
                $this->_itemr[$r['item_id']] = 0;
            }
            $this->_itemr[$r['item_id']] += (0- $r['qty'] );
                 
        }    
         
        $this->OnFilter(null);
        $this->_tvars['usecf'] = count($common['cflist']??[]) >0;
               

    }

    public function itemlistOnRow(  $row) {
        $item = $row->getDataItem();
        $store = $this->filter->searchstore->getValue();
        $emp = $this->filter->searchemp->getValue();
    
        $row->add(new ClickLink('itemname',$this, 'showOnClick'))->setValue($item->itemname);
   

        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('brand', $item->manufacturer));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('cell', $item->cell));

        $qty = $item->getQuantity($store,'',0,$emp);
        $row->add(new Label('iqty', H::fqty($qty)));
        $row->add(new Label('iqtyr',  ( $this->_itemr[$item->item_id] ??0) > 0 ?  H::fqty($this->_itemr[$item->item_id]) :'' )  );
        $row->add(new Label('iqtyb',  ( $this->_itemb[$item->item_id] ??0) > 0 ?  H::fqty($this->_itemb[$item->item_id]) :'' )  );
       
      //  $row->add(new Label('minqty', H::fqty($item->minqty)));
      
        $inprice="";
        if($this->_tvars['noshowpartion'] != true) {
          $inprice = $item->getPartion($store,"",$emp);  
        }
        $row->add(new Label('inprice', H::fa($inprice)));
        $pt = $this->filter->searchprice->getValue();
        if($pt=='price') {
            $am = H::fa( $inprice)* H::fqty( $item->getQuantity($store,"",0,$emp));
            
            $pr = ''; 
           
        } else {
            $pr= H::fa( $item->getPrice($pt, $store) );
            
            $am = $qty * $pr;
        }


        $row->add(new Label('iprodprice',$pr ));
        $row->add(new Label('iamount', H::fa(abs($am))));

        $row->add(new Label('cat_name', $item->cat_name));

 
        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
        if ($qty < 0) {
            $row->setAttribute('class', 'text-danger');
        }
        if ($qty == 0) {
            $row->setAttribute('class', 'text-warning');
        }

        $row->add(new \Zippy\Html\Link\BookmarkableLink('imagelistitem'))->setValue($item->getImageUrl());
        $row->imagelistitem->setAttribute('href', $item->getImageUrl());
        if ($item->image_id == 0) {
            $row->imagelistitem->setVisible(false);
        }
        
      //  $store = $this->filter->searchstore->getValue();
        
        $row->add(new Label('cfval'))->setText("") ;
        if($this->_tvars['usecf'] ?? false) {
           $cf="";
           foreach($item->getcf() as $f){
               if( strlen($f->val??'')>0){
                  $cf=$cf. "<small style=\"display:block\">". $f->name.": ".$f->val."</small>" ; 
               }
           }
           if(strlen($cf) >0) {
               $row->cfval->setText($cf,true) ;
           }
        }          

    }

    public function OnFilter($sender) {
   
         $pt = $this->filter->searchprice->getValue();
         $this->_tvars['nohowprodprice'] = $pt == 'price';  
         

        $this->itempanel->itemlist->Reload();

        $am = $this->getTotalAmount();
        $this->itempanel->totamount->setText((H::fa($am)));
    }

    public function getTotalAmount() {

        $store = $this->filter->searchstore->getValue();
        $emp = $this->filter->searchemp->getValue();
        $pt = $this->filter->searchprice->getValue();

        $src = new ItemDataSource($this) ;
        $sqty = $this->filter->searchqty->getValue();
 
        $items = $src->getItems(-1, -1) ;
        $total = 0;
        foreach($items as $item) {
            $qty = H::fqty($item->getQuantity($store,"",0,$emp) ); 
            
            if($pt=='price') {
                $am = H::fa( $item->getAmount($store,$emp) );
                if( $sqty==0 && $qty >0) {
                   $total += $am;
                }
                if( $sqty==1 && $qty <0) {
                   $total += $am ;
                }
                if( $sqty==2 ) {
                   $total += $am ;
                }
            } else {
                $am= H::fa( $qty * $item->getPrice($pt, $store) );
                if( $sqty==0 && $qty >0) {
                   $total += $am;
                }
                if( $sqty==1 && $qty <0) {
                   $total += $am ;
                }
                if( $sqty==2 ) {
                   $total += $am ;
                }
            }

        }


        return $total;
    }

    public function detailistOnRow($row) {
        $stock = $row->getDataItem();
        $row->add(new Label('storename', $stock->storename));
        $row->add(new Label('emp_name', $stock->emp_name));
        $row->add(new Label('snumber', $stock->snumber));
        $row->add(new Label('sdate', ''));

        if (strlen($stock->snumber ?? '') > 0 && strlen($stock->sdate ?? '') > 0) {
            $row->sdate->setText(H::fd($stock->sdate));
        }
        $row->add(new Label('partion', H::fa($stock->partion)));


        $row->add(new Label('qty', H::fqty($stock->qty)));
        $row->add(new Label('amount', H::fa($stock->qty * $stock->partion)));

        if ($stock->qty < 0) {
            $row->setAttribute('class', 'text-danger');
        }
        if ($stock->qty == 0) {
            $row->setAttribute('class', 'text-warn');
        }

     
        $row->add(new \Zippy\Html\Link\RedirectLink("createmove", "\\App\\Pages\\Doc\\MovePart", array(0, $stock->stock_id)))->setVisible($stock->qty < 0);


    }

    public function backOnClick($sender) {

        $this->itempanel->setVisible(true);
        $this->detailpanel->setVisible(false);
    }

    public function showOnClick($sender) {
        $this->_item = $sender->getOwner()->getDataItem();
        $options = \App\System::getOptions('common');
        
        $item = Item::load($this->_item->item_id);
        $this->itempanel->setVisible(false);
        $this->detailpanel->setVisible(true);
        $this->detailpanel->itemdetname->setText($this->_item->itemname);
        $this->detailpanel->stocklist->Reload();
        
        $store = $this->filter->searchstore->getValue();
        
        $this->_tvars['i_plist'] =[];

 
        if ($this->_item->price1 > 0) {
            $p = $this->_item->getPrice('price1', $store);

            $this->_tvars['i_plist'][]=array('i_pricename'=>$options['price1'] ,'i_price'=>$p); 
        }
        if ($this->_item->price2 > 0) {
            $p = $this->_item->getPrice('price2', $store);
            $this->_tvars['i_plist'][]=array('i_pricename'=>$options['price2'],'i_price'=>$p); 
        }
        if ($this->_item->price3 > 0) {
            $p = $this->_item->getPrice('price3', $store);
            $this->_tvars['i_plist'][]=array('i_pricename'=>$options['price3'],'i_price'=>$p); 
        }
        if ($this->_item->price4 > 0) {
            $p = $this->_item->getPrice('price4', $store);
            $this->_tvars['i_plist'][]=array('i_pricename'=>$options['price4'],'i_price'=>$p); 
        }
        if ($this->_item->price5 > 0) {
            $p = $this->_item->getPrice('price5', $store);
            $this->_tvars['i_plist'][]=array('i_pricename'=>$options['price5'],'i_price'=>$p); 
        }
      
  
        $this->_tvars["i_lastsell"] ="";             
        $this->_tvars["i_lastbay"] ="";             
        $st="";
        if($store >0) {
            $st = " and stock_id in (select stock_id from store_stock  where  store_id={$store}) " ;
        }
    
     
        $e = \App\Entity\Entry::getFirst("item_id={$this->_item->item_id} and quantity < 0 {$st}  and document_id in (select document_id from documents_view where  meta_name in ('GoodsIssue','TTN','POSCheck','OrderFood','ServiceAct')  ) ","entry_id desc")  ;
        if($e != null)  {
           $d = \App\Entity\Doc\Document::load($e->document_id)  ;    
           $this->_tvars["i_lastsell"] =  $d->document_number .' вiд '. H::fd($d->document_date) .'.'  ; 
           $this->_tvars["i_lastsell"] .= (' Продано  '. H::fqty(0-$e->quantity) .' по '. H::fa($e->outprice)  ) ; 
        }

        $this->detailpanel->iformbay->setVisible(false);

        $e = \App\Entity\Entry::getFirst("item_id={$this->_item->item_id} and quantity > 0 {$st} and document_id in (select document_id from documents_view where  meta_name in ('GoodsReceipt')  ) ","entry_id desc")  ;
        if($e != null)  {
           $d = \App\Entity\Doc\Document::load($e->document_id)  ;    
           $this->_tvars["i_lastbay"] =  $d->document_number .' вiд '. H::fd($d->document_date) .'.'  ; 
           $this->_tvars["i_lastbay"] .= (' Закуплено  '. H::fqty($e->quantity) .' по '. H::fa($e->partion)  ) ; 
           
           $this->detailpanel->iformbay->setVisible(true);
           $this->detailpanel->iformbay->iformbayqty->setText($e->quantity);
           
           
        }
        $this->_tvars["i_rate"] ='';
        if ($this->_tvars["useval"] && $item->rate > 0) {
           $this->_tvars["i_rate"]  = $item->rate . $item->val;
        }     
        
        
        
        $this->_tvars["i_avgout"] ='';
        $e = \App\Entity\Entry::getFirst("item_id={$this->_item->item_id} and quantity < 0 {$st} and document_id in (select document_id from documents_view where  meta_name in ('GoodsIssue','TTN','POSCheck','OrderFood')  ) ","entry_id asc")  ;
        if($e != null) {


            $d1 = new \DateTime( date('Y-m-d', $e->document_date ));
            $d2 = new \DateTime( date('Y-m-d',time() ));

            $interval = date_diff($d1,$d2);

            if($interval->days >30)  {
                $conn=\ZDB\DB::getConnect()  ;
                $sql="select sum(0-quantity) from entrylist_view where item_id={$item->item_id} and quantity < 0 {$st} and document_id in (select document_id from documents_view where  meta_name in ('GoodsIssue','TTN','POSCheck','OrderFood')  ) ";
                $sell =   $conn->GetOne($sql)  ;
                $sell =  number_format($sell/$interval->days*30, 1, '.', '');
                $this->_tvars["i_avgout"] = "Середня продажа  {$sell} в мiс.";
                
                
            }  
            
  
            
            
        }
        
        
        
    }

    public function OnToPay($sender){
       $qty=$sender->iformbayqty->getText() ;
       if($qty >0) {
           
          $r = $this->addItemToCO([$this->_item->item_id,$qty]);
          if($r==""){
             $sender->iformbayqty->setText('') ;                 
             $this->setSuccess('Додано') ;
          } else {
              $this->setError($r) ;
          }
       }
       
       
       
    }
    
    public function oncsv($sender) {
        $store = $this->filter->searchstore->getValue();
        $list = $this->itempanel->itemlist->getDataSource()->getItems(-1, -1, 'itemname');

        $common = System::getOptions('common') ;
        $pt = $this->filter->searchprice->getValue();


        $header = array();
        $data = array();

        $header['A1'] = "Наименуваня";
        $header['B1'] = "Артикул";
        $header['C1'] = "Штрих-код";
        $header['D1'] = "Од.";
        $header['E1'] = "Категорiя";
        $header['F1'] = "Бренд";
        $header['G1'] = "Комірка";
        $header['H1'] = "Кiл.";
        $header['I1'] = "Обл. цiна";
        if($this->_tvars["noshowpartion"] == true) {
            $header['I1'] ='';
        }

        if(strlen($common['price1'])) {
            $header['J1'] = $common['price1'];
        }
        if(strlen($common['price2'])) {
            $header['K1'] = $common['price2'];
        }
        if(strlen($common['price3'])) {
            $header['L1'] = $common['price3'];
        }
        if(strlen($common['price4'])) {
            $header['M1'] = $common['price4'];
        }
        if(strlen($common['price5'])) {
            $header['N1'] = $common['price5'];
        }

        $header['O1'] = "На суму";
        $header['P1'] = "Опис";

        $i = 1;
        foreach ($list as $item) {
            
            $itemor = Item::load($item->item_id) ;
            
            $i++;
            $data['A' . $i] = $item->itemname;
            $data['B' . $i] = $item->item_code;
            $data['C' . $i] = $item->bar_code;
            $data['D' . $i] = $item->msr;
            $data['E' . $i] = $item->cat_name;
            $data['F' . $i] = $item->manufacturer;
            $data['G' . $i] = $itemor->cell;
            $qty = $item->getQuantity($store);
            $pr = $item->getPartion($store);
            
            
            $data['H' . $i] = H::fqty($qty);
            $data['I' . $i] = H::fa($pr);
            if($this->_tvars["noshowpartion"] == true) {
                $data['I' . $i] ='';
            }


            if ($item->price1 > 0) {
                $data['J' . $i] = $item->getPrice('price1', $store);
            }
            if ($item->price2 > 0) {
                $data['K' . $i] = $item->getPrice('price2', $store);
            }
            if ($item->price3 > 0) {
                $data['L' . $i] = $item->getPrice('price3', $store);
            }
            if ($item->price4 > 0) {
                $data['M' . $i] = $item->getPrice('price4', $store);
            }
            if ($item->price5 > 0) {
                $data['N' . $i] = $item->getPrice('price5', $store);
            }

            if($pt=='price') {
                $am = $qty * $pr;
            } else {
                $am = $qty * $item->getPrice($pt, $store) ;
            }
            $data['O' . $i] = H::fa(abs($am));
            $data['P' . $i] = $itemor->description;

        }


        H::exportExcel($data, $header, 'itemlist.xlsx');
    }

    public function onprint($sender) {
        $store = $this->filter->searchstore->getValue();

        $items = array();
        $onpage = (new ItemDataSource($this))->getItems(-1, -1, "itemname") ;
        foreach ($onpage as $it) {

            $qty = intval($it->getQuantity($store));
            if($qty >0) {
                $it->quantity = $qty;
                $items[] = $it;
            }


        }
        if (count($items) == 0) {
            return;
        }
        
        $user= \App\System::getUser() ;
        $ret = H::printItems($items);   
        
        if(intval($user->prtypelabel) == 0) {

         
            if(\App\System::getUser()->usemobileprinter == 1) {
                \App\Session::getSession()->printform =  $ret;

                $this->addAjaxResponse("   $('.seldel').prop('checked',null); window.open('/index.php?p=App/Pages/ShowReport&arg=print')");
            } else {
                $this->addAjaxResponse("  $('#tag').html('{$ret}') ;$('.seldel').prop('checked',null); $('#pform').modal()");

            }
            return;
        }

        try {
            $buf=[];
           
            if(intval($user->prtypelabel) == 1) {
               $buf = \App\Printer::xml2comm($ret);
                        
             }
          
            if(intval($user->prtypelabel) == 2) {
               $buf = \App\Printer::arr2comm($ret);
                         
             }
             $b = json_encode($buf) ;   
             $this->addAjaxResponse("$('.seldel').prop('checked',null); sendPSlabel('{$b}') ");
        } catch(\Exception $e) {
            $message = $e->getMessage()  ;
            $message = str_replace(";", "`", $message)  ;
            $this->addAjaxResponse(" toastr.error( '{$message}' )         ");

        }

    }

}

class ItemDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere($p=false) {
        $conn = $conn = \ZDB\DB::getConnect();

        $form = $this->page->filter;
        $sqty = $form->searchqty->getValue();
        $where = "   disabled <> 1 ";
   


        $cstr = \App\ACL::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "    store_id in ({$cstr})      ";
        }
        if(\App\System::getUser()->showotherstores) {
            $cstr ="";

        }
        if(strlen(trim($cstr))==0) {
            $cstr = "1=1 ";
        }
        
        $cat = $form->searchcat->getValue();
        $store = $form->searchstore->getValue();
        $emp = $form->searchemp->getValue();
    
        if ($cat != 0) {
            if ($cat == -1) {
                $where = $where . " and cat_id=0";
            } else {


                $c = Category::load($cat) ;
                $ch = $c->getChildren();
                $ch[]=$cat;

                $cats = implode(",", $ch)  ;
                $where = $where . " and cat_id in ({$cats}) " ;
            }
        }
        $str="";
        $wemp="";
        
        if ($emp > 0) {
            $wemp =   " and  emp_id={$emp}  ";
            $str .= " and emp_id={$emp} ";            
        }        
        if ($store > 0) {
            $where = $where . " and item_id in (select item_id from store_stock where {$cstr}   and store_id={$store} {$wemp} ) ";
            $str .= " and store_id={$store}";
        } else {
            $where = $where . " and item_id in (select item_id from store_stock where  {$cstr}  {$wemp} ) ";
        }
        if ($form->searchterm->isChecked()  ) {   
            $where = $where . " and item_id in (select item_id from store_stock where sdate is not null and sdate < CURDATE()  ) ";
            
        }      
        if($sqty==0) {
           $where .= "  and  ( select coalesce(sum(st1.qty),0 ) from store_stock st1 where st1.item_id= items_view.item_id {$str} ) >0 ";
        }
        if($sqty==1) {
           $where .= "  and  ( select coalesce(sum(st1.qty),0 ) from store_stock st1 where st1.item_id= items_view.item_id {$str}) <0 ";
        }
        $stype = $form->searchtype->getValue();
        if ($stype > 0) {
            $where = $where . " and   item_type={$stype}  ";
        }     
        
        $text = trim($form->searchkey->getText());
        if (strlen($text) > 0) {
         
            if ($p == false) {
                $det = Item::qstr('%' . "<cflist>%{$text}%</cflist>" . '%');
                $text = Item::qstr('%' . $text . '%');
                $where = $where . " and (itemname like {$text} or item_code like {$text}  or bar_code like {$text}  or description like {$text}  or detail like {$det}  )  ";
            } else {
                $text = Item::qstr($text);
                $text_ = trim($text,"'") ;
                
                $where = $where . " and (itemname = {$text} or item_code = {$text}  or bar_code = {$text}   or detail like '%<bar_code1><![CDATA[{$text_}]]></bar_code1>%'   or detail like '%<bar_code2><![CDATA[{$text_}]]></bar_code2>%'  or item_id in (select item_id from store_stock where snumber like {$text} ) or item_id in (select item_id from taglist where  tag_type=3 and tag_name={$text}  ) )   ";
            }
    

        }
        $brand = $form->searchbrand->getText();

        if (strlen($brand) > 0) {
            $brand = Item::qstr($brand);
            $where = $where . " and item_id in (select item_id from items where  manufacturer = {$brand}  ) ";

        }
       

        return $where;
    }

    public function getItemCount() {
        return Item::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        $l = Item::find($this->getWhere(), "itemname asc", $count, $start);
        
        foreach (Item::findYield($this->getWhere(true), "itemname asc", $count, $start) as $k => $v) {
            $l[$k] = $v;
        }
        return $l;

    }

    public function getItem($id) {
        return Stock::load($id);
    }

}

class DetailDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {


        $form = $this->page->filter;
        $where = "item_id = {$this->page->_item->item_id} and   qty <> 0   ";
        
        
        $cstr = \App\ACL::getStoreBranchConstraint();
        if (strlen($cstr) > 0) {
            $cstr = "  and  store_id in ({$cstr})      ";
        }
        if(\App\System::getUser()->showotherstores) {
            $cstr ="";

        }          
        $where = $where . $cstr ;
        $store = $form->searchstore->getValue();
        if ($store > 0) {
            $where = $where . " and   store_id={$store}  ";
        }
        if ($form->searchterm->isChecked()  ) {   
             $where = $where . "   and sdate is not null and sdate < CURDATE()   ";
            
        }         
        $emp = $form->searchemp->getValue();
        if ($emp > 0) {
            $where = $where . " and  emp_id={$emp}  ";
        }
          
        return $where;
    }

    public function getItemCount() {
        return Stock::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        return Stock::find($this->getWhere(), "stock_id", $count, $start);
    }

    public function getItem($id) {
        return Stock::load($id);
    }

}

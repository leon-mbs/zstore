<?php

namespace App\Pages\Service;

use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Category;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Image;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Binding\PropertyBinding as Bind ;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * АРМ кассира общепита
 */
class ARMFood extends \App\Pages\Base
{

    private $_pricetype;
    private $_foodtype;
    private $_pos;
 
    private $_doc;
    public  $_itemlist   = array();
    public  $_catlist    = array();
    public  $_prodlist = array();
    public  $_doclist    = array();

    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowSer('ARMFood')) {
            return;
        }
        $food = System::getOptions("food");
        if (!is_array($food)) {
            $food = array( );
            $this->setWarn('nocommonoptions') ;
        }
        $this->_pricetype = $food['pricetypew'] ?? 'price1';
        $this->_tvars['delivery'] = $food['delivery'] ?? 0;
        $this->_tvars['tables'] = $food['tables']?? 0 ;
        $this->_tvars['pack'] = $food['pack'] ?? 0;
        $this->_tvars['bar'] = $food['bar'] ?? 0;
        
        
        
       
        $filter = \App\Filter::getFilter("armfood");
        if ($filter->isEmpty()) {
            $filter->pos = 0;
            $filter->store = H::getDefStore();
          
            $filter->nal = H::getDefMF();
            $filter->beznal = H::getDefMF();
           
        }
         
        //обшие настройки
        $this->add(new Form('setupform'))->onSubmit($this, 'setupOnClick');

        $this->setupform->add(new DropDownChoice('pos', \App\Entity\Pos::findArray('pos_name', ''), $filter->pos));
        $this->setupform->add(new DropDownChoice('store', \App\Entity\Store::getList(), $filter->store));
        $this->setupform->add(new DropDownChoice('nal', \App\Entity\MoneyFund::getList(false, false, 1), $filter->nal));
        $this->setupform->add(new DropDownChoice('beznal', \App\Entity\MoneyFund::getList(false, false, 2), $filter->beznal ));
        
        //список  заказов
        $this->add(new Panel('orderlistpan'))->setVisible(false);
        $this->add(new ClickLink('neworder', $this, 'onNewOrder'));
        $this->orderlistpan->add(new DataView('orderlist', new ArrayDataSource($this, '_doclist'), $this, 'onDocRow'));

        //панель статуса,  просмотр
        $this->orderlistpan->add(new Panel('statuspan'))->setVisible(false);
        
        $this->orderlistpan->statuspan->add(new \App\Widgets\DocView('docview'));
        
        
        
        
        
        //оформление заказа
        
        $this->add(new Panel('docpanel'))->setVisible(false);
        $this->docpanel->add(new ClickLink('toorderlist', $this, 'onOrderList'));

        $this->docpanel->add(new Panel('catpan'))->setVisible(false);
        $this->docpanel->catpan->add(new DataView('catlist', new ArrayDataSource($this, '_catlist'), $this, 'onCatRow'));

        $this->docpanel->add(new Panel('prodpan'))->setVisible(false);
        $this->docpanel->prodpan->add(new DataView('prodlist', new ArrayDataSource($this, '_prodlist'), $this, 'onProdRow'));

        $this->docpanel->add(new Form('navform'));
          
        $this->docpanel->navform->add(new TextInput('barcode'));
        $this->docpanel->navform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');

        
        $this->docpanel->navform->add(new SubmitButton('baddnewpos'))->onClick($this, 'addnewposOnClick');

        $this->docpanel->add(new Form('listsform'))->setVisible(false);
        $this->docpanel->listsform->add(new DataView('itemlist', new ArrayDataSource($this, '_itemlist'), $this, 'onItemRow'));

        $this->docpanel->listsform->add(new SubmitButton('btopay'))->onClick($this, 'topayOnClick');
        $this->docpanel->listsform->add(new SubmitButton('btoprod'))->onClick($this, 'toprodOnClick');
        $this->docpanel->listsform->add(new SubmitButton('btodel'))->onClick($this, 'todelOnClick');
        $this->docpanel->listsform->add(new Label('totalamount',"0")) ;
        
        $this->docpanel->listsform->add(new TextInput('notes')) ;
        $this->docpanel->listsform->add(new TextInput('table')) ;
         
        
        $this->docpanel->add(new Form('payform'))->setVisible(false);
        $this->docpanel->payform->add(new ClickLink('addcust'))->onClick($this, 'addcustOnClick');
        $this->docpanel->payform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docpanel->payform->add(new TextInput('pfamount')) ;
        $this->docpanel->payform->add(new TextInput('pfdisc')) ;
        $this->docpanel->payform->add(new TextInput('pfforpay')) ;
        $this->docpanel->payform->add(new TextInput('pfpayed')) ;
        $this->docpanel->payform->add(new TextInput('pfrest')) ;
   
        

        $this->docpanel->payform->pt = -1;
        $bind = new  \Zippy\Binding\PropertyBinding($this->docpanel->payform,'pt') ;
        $this->docpanel->payform->add(new \Zippy\Html\Form\RadioButton('pfnal',$bind,1)  ) ;
        $this->docpanel->payform->add(new \Zippy\Html\Form\RadioButton('pfbeznal',$bind,2)  ) ;
        
        $this->docpanel->payform->add(new ClickLink('bbackitems'))->onClick($this, 'backItemsOnClick');
        $this->docpanel->payform->add(new SubmitButton('btocheck'))->onClick($this, 'savedoc');

         
        $this->docpanel->add(new Panel('checkpan'))->setVisible(false);
        $this->docpanel->checkpan->add(new ClickLink('bnewcheck'))->onClick($this, 'onNewOrder');
        

        
        
    }

    public function setupOnClick($sender) {
        $store = $this->setupform->store->getValue();
        $nal = $this->setupform->nal->getValue();
        $beznal = $this->setupform->beznal->getValue();
        
        $this->_pos = \App\Entity\Pos::load($this->setupform->pos->getValue());

        if ($store == 0 || $nal == 0 || $beznal == 0 ||  $this->_pos == null) {
            $this->setError(H::l("notalldata"));
            return;
        }
        $filter = \App\Filter::getFilter("armfood");

        $filter->pos = $this->setupform->pos->getValue();
        $filter->store = $store;
     
        $filter->nal = $nal;
        $filter->beznal = $beznal;
               

        $this->setupform->setVisible(false);
        $this->docpanel->setVisible(false);
        $this->onOrderList($sender);
    }

    public function onNewOrder($sender) {
        $this->orderlistpan->statuspan->setVisible(true);
        $this->docpanel->setVisible(true);
        
        $this->docpanel->listsform->setVisible(true);
        $this->docpanel->navform->setVisible(true);

        $this->orderlistpan->setVisible(false);
        
        $this->_doc = \App\Entity\Doc\Document::create('OrderFood');
          
        
        $this->_itemlist = array();
        
        $this->docpanel->listsform->itemlist->Reload(); 
        $this->calcTotal() ;        
        
    }

    public function onOrderList($sender) {
        $this->docpanel->setVisible(false);
        $this->docpanel->prodpan->setVisible(false);
        $this->docpanel->catpan->setVisible(false);
        $this->docpanel->catpan->setVisible(false);
        $this->docpanel->payform->setVisible(false);
        $this->docpanel->checkpan->setVisible(false);

        $this->orderlistpan->setVisible(true);
        $this->orderlistpan->statuspan->setVisible(true);
        $this->updateorderlist();
    }

    public function addnewposOnClick($sender) {
        $this->docpanel->catpan->setVisible(true);
        $this->docpanel->prodpan->setVisible(false);
        $this->_catlist = Category::find('coalesce(parent_id,0)=0');
        $this->docpanel->catpan->catlist->Reload();
    }

    public function onDocRow($row) {
        $doc = $row->getDataItem();           
        $row->add(new ClickLink('docnumber',$this,'OnDocViewClick' ))->setValue($doc->document_number);
        $row->add(new Label('state', Document::getStateName($doc->state)));

        if ($doc->document_id == $this->_doc->document_id) {
            $row->setAttribute('class', 'table-success');
        }    
    
    }

    private function updateorderlist() {
        $where = "meta_name='OrderFood' and state not in(9,15) ";
        $this->_doclist = Document::find($where, 'document_id');
        $this->orderlistpan->orderlist->Reload();
    }

    public function onCatRow($row) {
        $cat = $row->getDataItem();
        $row->add(new ClickLink('catbtn'))->onClick($this, 'onCatBtnClick');
        $row->catbtn->add(new Label('catname', $cat->cat_name));
        $row->catbtn->add(new Image('catimage', "/loadimage.php?id=" . $cat->image_id));
    }
    public function onProdRow($row) {
        //$store_id = $this->setupform->store->getValue();
          
        $prod = $row->getDataItem();
        $prod->price = $prod->getPrice($this->_pricetype, $store_id);
        $row->add(new ClickLink('prodbtn'))->onClick($this, 'onProdBtnClick');
        $row->prodbtn->add(new Label('prodname', $prod->itemname));
        $row->prodbtn->add(new Label('prodprice', H::fa($prod->price)));
        $row->prodbtn->add(new Image('prodimage', "/loadimage.php?id=" . $prod->image_id));
    }


    public function onCatBtnClick($sender) {
        $cat = $sender->getOwner()->getDataItem();
        $catlist = Category::find('coalesce(parent_id,0)='.$cat->cat_id);
        if(count($catlist)>0) {
             $this->_catlist    = $catlist;
             $this->docpanel->catpan->catlist->Reload();
        } else {
            $this->_prodlist  = Item::find('disabled<>1  and  item_type in (1,4)  and cat_id='.$cat->cat_id) ;
            $this->docpanel->catpan->setVisible(false);
            $this->docpanel->prodpan->setVisible(true);
            $this->docpanel->prodpan->prodlist->Reload();
        }
        
    }

    public function onProdBtnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
         $store_id = $this->setupform->store->getValue();
       
        $qty = $item->getQuantity($store_id);
        if ($qty <= 0) {

            $this->setWarn("noitemonstore", $item->itemname);
        }

         
        if(isset($this->_itemlist[$item->item_id])) {
            $this->_itemlist[$item->item_id]->quantity++;           
        }   else {                                                 
            $item->myself = 1!=$this->_foodtype?1:0;
            $item->quantity = 1;
           // $item->price = $item->getPrice($this->setupform->pricetype->getValue(), $store_id);
            $this->_itemlist[$item->item_id] = $item;
        }
         $this->docpanel->prodpan->setVisible(false);
         $this->docpanel->listsform->itemlist->Reload(); 
         $this->calcTotal() ;        
    }
    
    public function addcodeOnClick($sender) {
        $code = trim($this->docpanel->navform->barcode->getText());
        $this->docpanel->navform->barcode->setText('');
        if ($code == '') {
            return;
        }

        foreach ($this->_itemlist as $ri => $_item) {
            if ($_item->bar_code == $code || $_item->item_code == $code) {
                $this->_itemlist[$ri]->quantity += 1;
                $this->docpanel->listsform->itemlist->Reload();
                
                return;
            }
        }


        $store_id = $this->setupform->store->getValue();
    

        $code_ = Item::qstr($code);
        $item = Item::getFirst(" item_id in(select item_id from store_stock where store_id={$store_id}) and   (item_code = {$code_} or bar_code = {$code_})");

        if ($item == null) {

            $this->setWarn("noitemcode", $code);
            return;
        }


  

        $qty = $item->getQuantity($store_id);
        if ($qty <= 0) {

            $this->setWarn("noitemonstore", $item->itemname);
        }


        $price = $item->getPrice($this->_pricetype, $store_id);
        $item->price = $price;
        $item->quantity = 1;
        $item->myself = 1==$this->_foodtype?1:0;
 
    
        $this->_itemlist[$item->item_id] = $item; 

        $this->docpanel->listsform->itemlist->Reload();
        $this->calcTotal() ;

         
    }
    
    
    
    public function onItemRow($row) {
        $item = $row->getDataItem();
        
        $row->add(new Label('itemname', $item->itemname));
        $row->add(new Label('item_code', $item->item_code));
        $row->add(new Label('qty', H::fqty($item->quantity)));
        $row->add(new Label('price', H::fa($item->price)));
        $row->add(new Label('amount', H::fa($item->price*$item->quantity)));
        $row->add(new ClickLink('myselfon',$this, 'onMyselfClick'))->setVisible($item->myself!=1);
        $row->add(new ClickLink('myselfoff',$this, 'onMyselfClick'))->setVisible($item->myself==1);
        $row->add(new ClickLink('qtymin'))->onClick($this, 'onQtyClick');
        $row->add(new ClickLink('qtyplus'))->onClick($this, 'onQtyClick');
        $row->add(new ClickLink('removeitem'))->onClick($this, 'onDelItemClick');
    }   
    
    public function onQtyClick($sender) {
         $item = $sender->getOwner()->getDataItem();
         if( strpos($sender->id,"qtyplus")===0){
             $item->quantity++;
         }
         if(strpos($sender->id,"qtymin")===0 && $item->quantity>1){
             $item->quantity--;
         }
        
         $this->docpanel->listsform->itemlist->Reload();  
         $this->calcTotal() ;         
    }
    
    public function onMyselfClick($sender) {
         $item = $sender->getOwner()->getDataItem();
         
         $item->myself = strpos($sender->id,"myselfon") === 0  ?1:0 ;
         $this->docpanel->listsform->itemlist->Reload();
         
    }
    
    public function onDelItemClick($sender) {
         $item = $sender->getOwner()->getDataItem();
         $this->_itemlist = array_diff_key($this->_itemlist, array($item->item_id => $this->_itemlist[$item->item_id]));
        
         $this->docpanel->listsform->itemlist->Reload(); 
         $this->calcTotal() ;
    }
    

     public function OnDocViewClick($sender) {
         $this->_doc = $sender->getOwner()->getDataItem()  ;
         $this->OnDocView() ;
         
     }
     public function OnDocView() {
        $this->orderlistpan->statuspan->setVisible(true);
  
        $this->orderlistpan->statuspan->docview->setDoc($this->_doc);
        $this->orderlistpan->orderlist->Reload(false);
  //      $this->updateStatusButtons();
  //      $this->goAnkor('dankor');       
     }
     
     
     public function calcTotal() {
        $amount=0;
        foreach($this->_itemlist as $item){
           $amount += ($item->quantity*$item->price);      
        }
        $this->docpanel->listsform->totalamount->setText(H::fa($amount)); 
     }

     public function OnAutoCustomer($sender) {
        return  \App\Entity\Customer::getList($sender->getText(), 1);
     }
     
     public function topayOnClick($sender) {
       
        if(false == $this->createdoc())  return;
       
       
        $cust = $this->form3->customer->getKey();
        if($cust>0){
            $this->_doc->customer_id = $cust;   
        }
         
       
        $this->_doc->payed = $this->docpanel->pfpayed->payed->getText();
        $this->_doc->headerdata['exchange'] = $this->docpanel->payform->pfrest->getText();
        $this->_doc->headerdata['paydisc'] = $this->docpanel->pfdisc->paydisc->getText();
        if($this->docpanel->payform->pt==2) {
           $this->_doc->headerdata['payment'] = $this->setupform->beznal->getValue();
        }  else {
           $this->_doc->headerdata['payment'] = $this->setupform->nal->getValue();            
        }
       
        if ($this->_doc->payamount > $this->_doc->payed && $this->_doc->customer_id == 0) {
            $this->setError("mustsel_cust");
            return;
        }
          $conn = \ZDB\DB::getConnect();
          $conn->BeginTrans();
      
         try {
        
            //если  оплачен  проводим  и закрываем
            if ($this->_doc->payamount == $this->_doc->payed  ){
                $this->_doc->updateStatus(Document::STATE_EXECUTED);
                $this->_doc->updateStatus(Document::STATE_CLOSED);
             
            }
         } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);
            return;
        }     
        $this->docpanel->payform->clean() ;
        $this->docpanel->payform->pt=null;      
        
        
        $this->docpanel->listsform->setVisible(false);
        $this->docpanel->navform->setVisible(false);
        $this->docpanel->payform->setVisible(true);
     } 
    
     public function backItemsOnClick($sender) {
        $this->docpanel->listsform->setVisible(true);
        $this->docpanel->navform->setVisible(true);
        $this->docpanel->payform->setVisible(false);
       
     }
     
     public function createdoc() {
       if(count($this->_itemlist)==0) {
            $this->setError('noenterpos') ;
            return false;
        }
        if($this->_doc->document_id>0)  return true;
        
  
        $this->_doc->document_number = $this->_doc->nextNumber();
  
        if (false == $this->_doc->checkUniqueNumber()) {
            $next = $this->_doc->nextNumber();
            $this->_doc->document_number = $next;
            if (strlen($next) == 0) {
                $this->setError('docnumbercancreated');
                return false;
            }
        }
        $this->_doc->document_date = time();
        $this->_doc->headerdata['time'] = time();
         $this->_doc->notes = $this->docpanel->listsform->notes->getText();
        $this->_doc->headerdata['pos'] = $this->pos->pos_id;
        $this->_doc->headerdata['pos_name'] = $this->pos->pos_name;
        $this->_doc->headerdata['store'] = $this->_store_id;
        $this->_doc->headerdata['pricetype'] = $this->_pt;

        $this->_doc->firm_id = $this->_pos->firm_id;

        $firm = H::getFirmData($this->_doc->firm_id);
        $this->_doc->headerdata["firm_name"] = $firm['firm_name'];
        $this->_doc->headerdata["inn"] = $firm['inn'];
        $this->_doc->headerdata["address"] = $firm['address'];
        $this->_doc->headerdata["phone"] = $firm['phone'];

        $this->_doc->packDetails('detaildata', $this->_itemlist);
        $this->_doc->amount = $this->docpanel->listsform->totalamount->getText();
        $this->_doc->save();
        $this->_doc->updateStatus(Document::STATE_NEW);
     
        
        
        return true;
     }
    
    
        
}

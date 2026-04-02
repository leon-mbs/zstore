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
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use Zippy\Html\Link\BookmarkableLink;
             
/**
 * АРМ официанта  кафе
 */
class ARMFoodW extends \App\Pages\Base
{
    private $_pricetype;
    private $_worktype = 0;
    private $_pos;
    private $_store;
    public  $_pt       = -1;  //тип оплаты
    public  $_ct       = -1;  //тип чека


    private $_doc;
    public $_itemlist = [];
    public $_catlist  = [];
    public $_prodlist = [];
    public $_doclist  = [];
    
    public $_prodvarlist  = [];
    public $_vblist  = [];
    public $_vbdetlist  = [];
    private $_vbitem;
  
     
    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowSer('ARMFoodW')) {
            return;
        }
        $food = System::getOptions("food");
        if (!is_array($food)) {
            $food = array();
            $this->setWarn('Не вказано параметри в налаштуваннях');
        }
        $this->_worktype = intval( $food['worktype'] );

    
        $this->_tvars['tables'] = $food['tables'] ?? 0;
        $this->_tvars['packicon'] = $food['pack'] ?? 0;
        $this->_tvars['diffbp'] = $food['diffbp'] ?? 0;
        $this->_tvars['baricon'] = $this->_worktype > 0  ;
       
        if($this->_worktype==0) {
           $this->_tvars['diffbp'] = 0;
        }
        if($this->_tvars['diffbp'] == 1) {
           $this->_tvars['baricon'] =0; 
        }

        

        $filter = \App\Filter::getFilter("armfood");
        if ($filter->isEmpty()) {
            $filter->pos = 0;
            $filter->menuimages = 0;
            $filter->store = H::getDefStore();
            $filter->pricetype = $food['pricetype'] ?? 'price1';

            $filter->nal = H::getDefMF();
            $filter->beznal = H::getDefMF();

        }
       
        if($this->_tvars['useimages'] == false || $filter->menuimages==0){
             $this->_tvars['menuimage'] = false ;
        }      
        $this->add(new Form('setupform'))->onSubmit($this, 'setupOnClick');

        $this->setupform->add(new DropDownChoice('pos', \App\Entity\Pos::findArray('pos_name', ''), $filter->pos));
        $this->setupform->add(new DropDownChoice('store', \App\Entity\Store::getList(), $filter->store));
        $this->setupform->add(new DropDownChoice('nal', \App\Entity\MoneyFund::getList(1), $filter->nal));
        $this->setupform->add(new DropDownChoice('beznal', \App\Entity\MoneyFund::getList(2), $filter->beznal));
        $this->setupform->add(new CheckBox('menuimages', $filter->menuimages ));
        $this->setupform->add(new ClickLink('options', $this, 'onOptions'));
        $this->setupform->add(new ClickLink('variations', $this, 'onVariations'));
    
        
        $this->add(new Panel('docpanel'))->setVisible(false) ;  
                 
        $this->docpanel->add(new Form('navform'))  ;
        $this->docpanel->navform->add(new SubmitButton('baddnewpos'))->onClick($this, 'addnewposOnClick');
        $this->docpanel->navform->add(new AutocompleteTextInput('itemfast'))->onText($this, 'OnAutoItem');
        $this->docpanel->navform->add(new SubmitButton('addfast'))->onClick($this, 'addfastOnClick');

        $this->docpanel->add(new Form('listsform')) ;
        $this->docpanel->listsform->add(new DataView('itemlist', new ArrayDataSource($this, '_itemlist'), $this, 'onItemRow'));
        $this->docpanel->listsform->add(new SubmitButton('btosave'))->onClick($this, 'tosaveOnClick');
        $this->docpanel->listsform->add(new SubmitButton('btoprod'))->onClick($this, 'toprodOnClick');
        $this->docpanel->listsform->add(new Label('totalamount')) ;
       
    }
 
 
     public function setupOnClick($sender) {
        $store = $this->setupform->store->getValue();
        $nal = $this->setupform->nal->getValue();
        $beznal = $this->setupform->beznal->getValue();

        $this->_pos = \App\Entity\Pos::load($this->setupform->pos->getValue());

        if ($store == 0 || $nal == 0 || $beznal == 0 || $this->_pos == null) {
            $this->setError("Не зазначено всі дані");
            return;
        }
        $filter = \App\Filter::getFilter("armfood");


        $filter->store = $store;
        $filter->pos = $this->_pos->pos_id;

        $filter->menuimages = $this->setupform->menuimages->isChecked() ?1:0;
        $filter->nal = $nal;
        $filter->beznal = $beznal;
        $this->_store = $store;
        $this->_pricetype = $filter->pricetype;

        if($this->_pos->usefisc != 1) {
            $this->_tvars['fiscal']  = false;
        }
        if($this->_pos->usefreg != 1) {
            $this->_tvars['freg']  = false;
        } else {
            $this->_tvars['scriptfreg']  = $this->_pos->scriptfreg;
        }
        $this->_tvars['fiscaltestmode']  = $this->_pos->testing==1;
        if($this->_tvars['useimages'] == false || $filter->menuimages==0){
             $this->_tvars['menuimage'] = false ;
        }

        $this->setupform->setVisible(false);
  
        $this->onNewOrder();
    }

    public function onNewOrder($sender = null) {
    
        $this->docpanel->setVisible(true);

        $this->docpanel->listsform->setVisible(true);
      
        $this->docpanel->listsform->clean();

        $this->docpanel->navform->setVisible(true);
        $this->docpanel->navform->clean();

      //  $this->orderlistpan->setVisible(false);
      //  $this->orderlistpan->searchform->clean();
      
      //  $this->docpanel->checkpan->setVisible(false);

        $this->_doc = \App\Entity\Doc\Document::create('OrderFood');
       
        $this->_itemlist = array();

        $this->docpanel->listsform->itemlist->Reload();
        $this->calcTotal();
      
       
        $this->_pt = -1;
        $this->_ct = -1;


    }

 
    public function addfastOnClick($sender) {
         $key=$this->docpanel->navform->itemfast->getKey();  

         if($key >0){
             
           $item = Item::load($key);
           $item = $this->calcitem($item);
     
           $this->addItem($item);
 
         }
         
         $this->docpanel->navform->itemfast->setKey(0);  
         $this->docpanel->navform->itemfast->setText('');  
         
    }
 
    public function OnAutoItem($sender) {
        $text = trim($sender->getText());
        $like = Item::qstr('%' . $text . '%');
         
        return Item::findArray('itemname',"disabled<>1  and  item_type in (1,4,5 )  and  (itemname like {$like} or item_code like {$like} ) and cat_id in (select cat_id from item_cat where detail  not  like '%<nofastfood>1</nofastfood>%')  and detail  not  like '%<isbasevarfood>1</isbasevarfood>%' "  );        
        

    }
    
    public function addnewposOnClick($sender) {
        $this->docpanel->catpan->setVisible(true);
        $this->docpanel->prodpan->setVisible(false);
     //   $this->docpanel->listsform->setVisible(false);
     //   $this->docpanel->navform->setVisible(false);


        $this->_catlist = Category::find(" cat_id in(select cat_id from  items where  disabled <>1  ) and detail  not  like '%<nofastfood>1</nofastfood>%' ");
        usort($this->_catlist, function ($a, $b) {
            return $a->order > $b->order;
        });

        $this->docpanel->catpan->catlist->Reload();
    }
   
    private function addItem($item) {

         
        $store_id = $this->setupform->store->getValue();

        $qty = $item->getQuantity($store_id);
        if ($qty <= 0 && $item->autoincome != 1) {

            $this->setWarn("Товару {$item->itemname} немає на складі");
        }

        $found=false;
        foreach($this->_itemlist as $i=>$it) {
            if($it->item_id==$item->item_id && intval($it->foodstate)==0) {
                $this->_itemlist[$i]->quantity++;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $item->myself = $this->_worktype == 0;
            if ($this->_tvars['packicon'] == 0) {
                $item->myself = 0;
            }
            $item->quantity = 1;
            $item->foodstate = 0;
            // $item->price = $item->getPrice($this->_pricetype, $this->_store);

            $item->forbar= $item->isforbar==1 ? 1:0;
            
  
            $this->_itemlist[] = $item;
        }

        $this->setSuccess("Позиція додана");
        $this->docpanel->listsform->itemlist->Reload();
        $this->calcTotal(); 
    }
  
    public function calcTotal() {
        $amount = 0;
       
        foreach ($this->_itemlist as $item) {
            $amount += H::fa($item->quantity * $item->price);
        }
       
        $this->docpanel->listsform->totalamount->setText(H::fa($amount));

    }
  
   
  //список позиций
    public function onItemRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('itemname', $item->itemname));
       
        $qty = H::fqty($item->quantity) ;
        $row->add(new Label('qty', $qty));


        $row->add(new Label('price', H::fa($item->price)));
        $row->add(new Label('amount', H::fa($item->price * $item->quantity)));
        $row->add(new ClickLink('myselfon', $this, 'onMyselfClick'))->setVisible($item->myself == 1);
        $row->add(new ClickLink('myselfoff', $this, 'onMyselfClick'))->setVisible($item->myself != 1);
        $row->add(new ClickLink('forbaron', $this, 'onForbarClick'))->setVisible($item->forbar == 1);
        $row->add(new ClickLink('forbaroff', $this, 'onForbarClick'))->setVisible($item->forbar != 1);
          
        $row->add(new ClickLink('qtymin'))->onClick($this, 'onQtyClick');
        $row->add(new ClickLink('qtyplus'))->onClick($this, 'onQtyClick');
        $row->add(new ClickLink('removeitem'))->onClick($this, 'onDelItemClick');
       


        $state="Новий";
        if ($item->foodstate == 1) {
            $state="В черзi";
        }
        if ($item->foodstate == 2) {
            $state="Готується";
        }
        if ($item->foodstate == 3) {
            $state="Готово";
        }
        if ($item->foodstate == 4) {
            $state="Видано";
        }
        $row->add(new Label('state', $state));

        if ($item->foodstate > 0) {
            $row->removeitem->setVisible(false);
            $row->myselfon->setVisible(false);
            $row->myselfoff->setVisible(false);
            $row->qtymin->setVisible(false);
            $row->qtyplus->setVisible(false);
           
        }    
        if ($item->foodstate ==1 ) {
            $row->removeitem->setVisible(true);
        }
    
    }
    public function onDelItemClick($sender) {
        $item = $sender->getOwner()->getDataItem();

        $rowid =  array_search($item, $this->_itemlist, true);

        $this->_itemlist = array_diff_key($this->_itemlist, array($rowid => $this->_itemlist[$rowid]));

        $this->docpanel->listsform->itemlist->Reload();
        $this->calcTotal();
    }

    public function onQtyClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        if (strpos($sender->id, "qtyplus") === 0) {
            $item->quantity++;
        }
        if (strpos($sender->id, "qtymin") === 0 && $item->quantity > 1) {
            $item->quantity--;
        }

        $this->docpanel->listsform->itemlist->Reload();
        $this->calcTotal();
    }

    //с собой
    public function onMyselfClick($sender) {
        $item = $sender->getOwner()->getDataItem();

        $item->myself = strpos($sender->id, "myselfon") === 0 ? 0 : 1;
        $this->docpanel->listsform->itemlist->Reload();

    }
    //бар
    public function onForbarClick($sender) {
        $item = $sender->getOwner()->getDataItem();

        $item->forbar = strpos($sender->id, "forbaron") === 0 ? 0 : 1;
               
        $this->docpanel->listsform->itemlist->Reload();
        
         //запоминаем  последнее
      
        if($item->forbar != $item->isforbar) {
            $it = Item::load($item->item_id) ;
            $it->isforbar = $item->forbar;
            $it->save();
        }    
        

    }
   private function calcitem($prod){
         $prod->price = $prod->getPriceEx(
            array(
              'pricetype'=>$this->_pricetype,
              'store'=>$this->_store )
        );

        $prod->pureprice = $prod->getPurePrice($this->_pricetype, $this->_store);

        $prod->disc=0;
        if($prod->price >0 && $prod->pureprice >0) {
            $prod->disc = number_format((1 - ($prod->price/($prod->pureprice)))*100, 1, '.', '') ;
        }
        if($prod->disc < 0) {
            $prod->disc=0;
        }

        
        
        
        return $prod; 
    }
 
  // в  производство
    public function toprodOnClick($sender) {
        $this->docpanel->catpan->setVisible(false);
        $this->docpanel->prodpan->setVisible(false);
 
        $pass=  $this->docpanel->listsform->passprod->isChecked() ? 1:0;
        if($this->_tvars['diffbp']==1 && $this->_ct<1 && $pass ==0 )  {
            $this->setError('Не вказано тип чеку') ;
            return;
        }
        
        if ($this->createdoc() == false) {
            return;
        }
        $this->_doc->setHD('passprod',$pass) ;
       
 
        if($this->_tvars['diffbp']==1 && $pass == 0)  {
           $this->_doc->setHD('forbar',$this->_ct==2 ? 1:0) ;
        }
        $this->toprod()  ;
        
       // $this->onOrderList();
        $this->onOrderList(null);
    }

    private function toprod() {



        $n = new \App\Entity\Notify();
        $n->user_id = \App\Entity\Notify::ARMFOODPROD;
        $n->dateshow = time();
        $n->message = serialize(array('cmd' => 'update'));

        $inprod=0;
        foreach($this->_itemlist as $i=>$p) {
            
            
            
            if(intval($this->_itemlist[$i]->foodstate) ==0  ) {
                $this->_itemlist[$i]->foodstate = 1;
         
                 
                if($this->_doc->getHD('passprod',0)==1) {
                    $this->_itemlist[$i]->foodstate = -1 ;
                    continue;
                }
                if($this->_ct > 0) {
                    $this->_itemlist[$i]->forbar=$this->_ct ==2 ?1:0;
                }                
                $inprod++;
            }

        }

        
        
        $this->_doc->packDetails('detaildata', $this->_itemlist);
        $this->_doc->save();


        if($this->_doc->state < 4) {
            $this->_doc->updateStatus(Document::STATE_INPROCESS);
            $n->message = serialize(array('cmd' => 'new','document_id'=>$this->_doc->document_id));

        }
        if($this->_doc->getHD('passprod',0)==1) {
            $this->setInfo('Відправлено');
            return ;
        }
        
        if($inprod==0) {
            $this->setWarn('Нема  позицiй для виробництва') ;
            return;
        }
        if($inprod==0) {
            $this->setWarn('Нема  позицiй для виробництва') ;
            return;
        }
        
        
        $n->save();



        $this->setInfo('Відправлено у виробництво');

    }

   
    // сохранить
    public function tosaveOnClick($sender) {
        $this->docpanel->catpan->setVisible(false);
        $this->docpanel->prodpan->setVisible(false);
 
        if($this->_doc instanceof Document) {
            if($this->_doc->hasPayments()  || $this->_doc->hasStore()  ){
                $this->setError("У документа  вже є проводки") ;
                return;
            }    
        }
        

        if ($this->createdoc() == false) {
            return;
        }

   
    
        if($this->_doc->state != Document::STATE_NEW) {
            $this->_doc->updateStatus(Document::STATE_SHIFTED);

        }
   
     //   $this->_doc->save();


        $this->onNewOrder();
    }
        
}

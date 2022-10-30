<?php

namespace App\Pages\Service;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\Entity\Item;
use App\Entity\Store;


class Min2prod extends \App\Pages\Base
{

 
    public  $_itemlist = array();

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowSer('Min2prod')) {
            return;
        }


        $this->add(new Form('searchform'))->onSubmit($this, 'updatelist');
        $this->searchform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));
        $this->searchform->add(new TextInput('search'));

        $this->add(new Form('exportform'))->onSubmit($this, 'onExport');
        $this->exportform->add(new DataView('itemlist', new ArrayDataSource($this, '_itemlist'), $this, 'onRow'));

 
        $this->updatelist(null);

    }

    public function onRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('itemname', $item->itemname));
    //    $row->add(new Label('item_code', $item->item_code));
     //   $row->add(new Label('minqty', H::fqty($item->minqty)) );
        $row->add(new Label('qty', H::fqty( $item->qty)) );
        $row->add(new Label('prodqty', H::fqty( $item->minqty - $item->qty)) );
        $row->add(new \Zippy\Html\Form\CheckBox('sel', new \Zippy\Binding\PropertyBinding($item, 'sel')));
 
    }
 
   
 
    public function updatelist($sender) {
           $conn =   \ZDB\DB::getConnect();
 
           $cstr = '';
           $cstr = \App\Acl::getStoreBranchConstraint();
           if (strlen($cstr) > 0) {
              $cstr = "    store_id in ({$cstr})  and   ";
           }
           $store = $this->searchform->store->getValue();
           $name=""    ;
           $text = trim($this->searchform->search->getText() );
           if(strlen($text)>0) {
                $text = $conn->qstr('%'.$text.'%') ;  
                $name = " and (i.itemname like {$text}  or  cat_name  like {$text}) " ;    
                
           }
        
           
        
           $sql = "select t.qty, i.item_id, i.minqty,i.itemname,i.item_code   from (select store_id, item_id, coalesce(sum( qty),0) as qty   
            from  store_stock
            where  {$cstr} store_id = {$store}  group by  item_id    ) t
            join items_view  i  on t.item_id = i.item_id   
            where i.disabled  <> 1 and  t.qty < i.minqty and i.minqty>0 and   i.item_type in(4,5)  {$name}
            order  by  i.itemname ";
            $rs = $conn->Execute($sql);
            $this->_itemlist = array();
            foreach ($rs as $row) {
                $this->_itemlist[] = new \App\DataItem($row);
            }


            $this->exportform->itemlist->Reload();

    }

    public function OnExport($sender) {
        if (false == \App\ACL::checkDelRef('ItemList')) {
            return;
        }

     
        $doc = Document::create('ProdReceipt')  ;
        $doc->user_id  = System::getUser()->user_id;
        $doc->document_number  = $doc->nextNumber();
        $doc->headerdata['store'] = $this->searchform->store->getValue() ;
        
        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
          $items = array();  
          foreach ($this->exportform->itemlist->getDataRows() as $row) {
            $it = $row->getDataItem();
            if ($it->sel == true) {
              $item = Item::load($it->item_id);
              $item->quantity = $it->minqty - $it->qty;
              $item->price = $item->getProdprice();
              
              if($item->quantity > 0){
                 $items[$item->item_id] = $item; 
                 $doc->amount += ($item->quantity * $item->price)  ; 
              }
              
            }
          }
          
          
          
          $doc->packDetails('detaildata', $items);
          $doc->save();          
          $doc->updateStatus(Document::STATE_NEW) ;
         // $doc->updateStatus(Document::STATE_EXECUTED) ;
          
          
          $conn->CommitTrans();
          $this->setInfo("Создан документ " . $doc->document_number) ;
          $this->updatelist(null);
          
          
        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $doc->meta_desc);

            return;
        }
    }    
}

//INSERT  INTO `metadata` ( `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`) VALUES( 5, 'Min2prod', 'Min2prod', '', 0);

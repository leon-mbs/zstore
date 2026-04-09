<?php

namespace App\Modules\DF\Public;

use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Customer;
use App\Entity\Category;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Form\Button;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use App\Helper as H;
use App\Application as App;

/**
* номенклатура
*/
class Items extends Base
{
    private  $_item;
    private  $_store_id;
    
    public function __construct() {
        parent::__construct();
  
        $modules = \App\System::getOptions("modules");
        
        $this->_store_id = round($modules['dfstore'] );
        
        if($modules['df'] != 1) {
            http_response_code(404);
            die;
        } 
  
        $catlist = array();
        $catlist[-1] = "Без категорії";
        foreach (Category::getList() as $k => $v) {
            $catlist[$k] = $v;
        }
        $this->add(new Panel('itemtable')) ;
        $this->itemtable->add(new Form('filter'))->onSubmit($this, 'OnFilter');
 
        $this->itemtable->filter->add(new TextInput('searchkey'));
        $this->itemtable->filter->add(new TextInput('searchbrand'));
        $this->itemtable->filter->add(new DropDownChoice('searchcat', $catlist, 0));

        $this->itemtable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
 
        $this->itemtable->add(new DataView('itemlist', new ItemDataSource($this), $this, 'itemlistOnRow'));
        $this->itemtable->itemlist->setPageSize(25);
        $this->itemtable->add(new \Zippy\Html\DataList\Paginator('pag', $this->itemtable->itemlist));
        $this->itemtable->itemlist->Reload();
       
        $catlist = Category::findArray("cat_name", "childcnt = 0", "cat_name");
    
        $this->add(new Form('itemdetail'))->setVisible(false);
        $this->itemdetail->add(new TextInput('editname'));
        $this->itemdetail->add(new TextInput('editcode'));
        $this->itemdetail->add(new TextInput('editbrand'));
        $this->itemdetail->add(new TextInput('editprice'));
        $this->itemdetail->add(new DropDownChoice('editcat',$catlist));
       
        $this->itemdetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->itemdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
   
    }
   
    public function itemlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();
        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);


        $row->add(new ClickLink('itemname', $this, 'editOnClick'))->setValue($item->itemname);

        $row->add(new Label('item_code', $item->item_code));
        $row->add(new Label('brand', $item->manufacturer));
        $row->add(new Label('cat_name', $item->cat_name));
        $row->add(new Label('price', $item->price1));
        $row->add(new Label('qty', H::fqty( $item->getQuantity($this->_store_id))) );
        
        $row->add(new ClickLink('del'))->onClick($this, 'delOnClick');
        
    }  
  
    public function OnFilter($sender) {
  
          $this->itemtable->itemlist->Reload(); 
     }
 
   public function addOnClick($sender) {
       
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);
        // Очищаем  форму
        $this->itemdetail->clean();
        
        $this->_item = new Item();
        $this->_item->ffpartner=$this->_customer->customer_id;
        
        $this->itemdetail->editcode->setText(Item::getNextArticle());
         
        
   }
    public function editOnClick($sender) {
        
        $item = $sender->owner->getDataItem();
        $this->_item = Item::load($item->item_id);

        $this->itemdetail->editname->setText($this->_item->itemname) ;
        $this->itemdetail->editcode->setText($this->_item->item_code) ;
        $this->itemdetail->editcat->setValue($this->_item->cat_id) ;
        $this->itemdetail->editbrand->setText($this->_item->manufacturer) ;
        $this->itemdetail->editprice->setText($this->_item->price1) ;
        
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);
        
    }  

   public function saveOnClick($sender) {
        $this->_item->itemname= trim( $this->itemdetail->editname->getText() );
        $this->_item->item_code= trim(  $this->itemdetail->editcode->getText()) ;
        $this->_item->cat_id=   $this->itemdetail->editcat->getValue() ;
        $this->_item->manufacturer = trim(  $this->itemdetail->editbrand->getText()) ;
        $this->_item->price1 = trim(  $this->itemdetail->editprice->getText()) ;
       
        if (strlen($this->_item->itemname) == 0) {
            $this->setError('Не введено назву');
            return;
        }       
        if ($this->_item->checkUniqueArticle()==false) {
            $this->setError('Такий артикул вже існує');
            return;
        }   
       
        $this->_item->save();
         
        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
        $this->itemtable->itemlist->Reload(); 

   }   
   public function delOnClick($sender) {
      $item = $sender->owner->getDataItem();
      $conn = \ZDB\DB::getConnect();
   
       $sql = "  select count(*)  from  store_stock where   item_id = {$item->item_id}  ";
       $cnt = $conn->GetOne($sql);
        if ($cnt > 0) {
            $item->disabled=1;
            $item->save();
        } else {
            Item::delete($item->item_id) ;
        }     
        $this->itemtable->itemlist->Reload(); 

   }   
   public function cancelOnClick($sender) {
        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);

   }   
   
   
}

class ItemDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }    
    private function getWhere( ) {
       
     //   $conn = \ZDB\DB::getConnect();
        $form = $this->page->itemtable->filter;
        $where = "detail like '%<ffpartner>". \App\System::getCustomer() ."</ffpartner>%'  ";
        
        $text = trim($form->searchkey->getText());
        $brand = trim($form->searchbrand->getText());
        $cat = $form->searchcat->getValue();
    
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

        if (strlen($brand) > 0) {

            $brand = Item::qstr($brand);
            $where = $where . " and  manufacturer like {$brand}      ";
        }       

         if (strlen($text) > 0) {

             $text = Item::qstr('%' . $text . '%');
             $where = $where . " and (itemname like {$text} or item_code like {$text}  or bar_code like {$text}  or detail like {$det} )  ";
           
         }     
        
          return $where;
    }

    public function getItemCount() {
         
        return Item::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        $items = Item::find($this->getWhere(), "itemname,disabled  ", $count, $start);         //         $docs = Document::find($this->getWhere(), "priority desc,document_id desc", $count, $start);

        return $items;
    }

    public function getItem($id) {

    }

}
<?php

namespace App\Pages\Register;

use App\Entity\Customer;
use App\Entity\Category;
use App\Entity\Item;
use App\Entity\CustItem;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use Zippy\Html\Link\SubmitLink;

class CustItems extends \App\Pages\Base
{

    private $_item;
 

    public function __construct($add = false) {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('CustItems')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
 
        $this->filter->add(new TextInput('searchkey'));
        $this->filter->add(new DropDownChoice('searchcat', Category::getList(), 0));
      
        $this->filter->add(new DropDownChoice('searchcust', Customer::findArray("customer_name","status=0 and  (detail like '%<type>2</type>%'  or detail like '%<type>0</type>%' )","customer_name"), 0));
      
        $this->add(new Panel('itemtable'))->setVisible(true);
        $this->itemtable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');

        $this->itemtable->add(new Form('listform'));

        $this->itemtable->listform->add(new DataView('itemlist', new CustItemDataSource($this), $this, 'itemlistOnRow'));
        $this->itemtable->listform->itemlist->setPageSize(H::getPG());
        $this->itemtable->listform->add(new \Zippy\Html\DataList\Paginator('pag', $this->itemtable->listform->itemlist));
        $this->itemtable->listform->add(new SubmitLink('deleteall'))->onClick($this, 'OnDelAll');
   
        $this->add(new Form('itemdetail'))->setVisible(false);
        $this->itemdetail->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutoItem');
        $this->itemdetail->add(new TextInput('editprice'));
        $this->itemdetail->add(new TextInput('editqty'));
        $this->itemdetail->add(new TextInput('editcustcode'));
        $this->itemdetail->add(new TextArea('editdescription'));
        $this->itemdetail->add(new DropDownChoice('editcust', Customer::findArray("customer_name","status=0 and  (detail like '%<type>2</type>%'  or detail like '%<type>0</type>%' )","customer_name"), 0));
  
        $this->itemdetail->add(new SubmitButton('save'))->onClick($this, 'OnSubmit');
        $this->itemdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

   
    }

    public function itemlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();
        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);

        $row->add(new Label('itemname', $item->itemname));
        $row->add(new Label('item_code', $item->item_code));
        $row->add(new Label('cust_code', $item->cust_code));
        $row->add(new Label('customer_name', $item->customer_name));
        $row->add(new Label('qty', $item->quantity));

        $row->add(new Label('price', $item->price));
 
        $row->add(new Label('comment', $item->comment));
 
        $row->add(new CheckBox('seldel', new \Zippy\Binding\PropertyBinding($item, 'seldel')));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');

    }

 
    public function editOnClick($sender) {
        $this->_copy = false;
        $item = $sender->owner->getDataItem();
        $this->_item = CustItem::load($item->custitem_id);

        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);

        $this->itemdetail->edititem->setKey($this->_item->item_id);
        $this->itemdetail->edititem->setText($this->_item->itemname);
        $this->itemdetail->editprice->setText($this->_item->price);
        $this->itemdetail->editqty->setText($this->_item->quantity);
        $this->itemdetail->editcustcode->setText($this->_item->cust_code);
        $this->itemdetail->editcust->setValue($this->_item->customer_id);
        $this->itemdetail->editdescription->setText($this->_item->comment);
  
    }

    public function addOnClick($sender) {
        $this->_copy = false;
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);
        // Очищаем  форму
        $this->itemdetail->clean();
 
        $this->_item = new CustItem();

    
    }

    public function cancelOnClick($sender) {
        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
    }

    public function OnFilter($sender) {
        $this->itemtable->listform->itemlist->Reload();
    }

    public function OnSubmit($sender) {
        if (false == \App\ACL::checkEditRef('CustItems')) {
            return;
        }

        $this->_item->item_id = $this->itemdetail->edititem->getKey();
        $this->_item->customer_id = $this->itemdetail->editcust->getValue();
        $this->_item->price = $this->itemdetail->editprice->getText();
        $this->_item->quantity = $this->itemdetail->editqty->getText();
        $this->_item->cust_code = $this->itemdetail->editcustcode->getText();
        $this->_item->comment = $this->itemdetail->editdescription->getText();
    
        
        if ( $this->_item->item_id == 0) {
            $this->setError('noselitem');
            return;
        }
        if ( $this->_item->customer_id == 0) {
            $this->setError('noselsender');
            return;
        }
  
 

        $this->_item->save();
      

        $this->itemtable->listform->itemlist->Reload(false);

        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
    }

 
 
    public function OnDelAll($sender) {
        if (false == \App\ACL::checkDelRef('CustItems')) {
            return;
        }

        $ids = array();
        foreach ($this->itemtable->listform->itemlist->getDataRows() as $row) {
            $item = $row->getDataItem();
            if ($item->seldel == true) {
                $ids[] = $item->custitem_id;
            }
        }
        if (count($ids) == 0) {
            return;
        }

        $conn = \ZDB\DB::getConnect();
       
        foreach ($ids as $id) {
      
           $conn->Execute("delete from custitems  where   custitem_id={$id}");

         
        }

 
        $this->itemtable->listform->itemlist->Reload();

    }

    public function OnAutoItem($sender) {
        $text = trim($sender->getText());
        $stext = Item::qstr('%' . $text . '%');
        $text = Item::qstr( $text );

        return Item::findArray("itemname"," (itemname like {$stext} or item_code = {$text}    ) ");
    }

}

class CustItemDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere($p = false) {

        $form = $this->page->filter;
        $where = "1=1 ";
        $key = $form->searchkey->getText();
        $cat = $form->searchcat->getValue();
        $cust = $form->searchcust->getValue();
       
        if ($cat != 0) {
  
            $where = $where . " and cat_id=" . $cat;
            
        }
        if ($cust != 0) {
  
            $where = $where . " and customer_id=" . $cust;
      
        }

        if (strlen($key) > 0) {
       
                $skey = CustItem::qstr('%' . $key . '%');
                $key = CustItem::qstr($key);
                $where = $where  = "   (itemname like {$skey} or item_code = {$key}  or cust_code = {$key} )  ";
            
        }   

    
 
        return $where;
    }

    public function getItemCount() {
        return CustItem::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $sortfield = "itemname asc";
     
        $l = CustItem::find($this->getWhere(), $sortfield, $count, $start);
   
        return $l;
    }

    public function getItem($id) {
        return Item::load($id);
    }

}

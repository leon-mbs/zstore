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
        $this->itemtable->add(new ClickLink('imports'))->onClick($this, 'onImport');
        $this->itemtable->add(new ClickLink('csv', $this, 'oncsv'));

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

        
        $this->add(new Form('importform'))->setVisible(false);        
        $this->importform->add(new ClickLink('back'))->onClick($this, 'cancelOnClick');
        $this->importform->onSubmit($this, 'onLoad');
        
        
        $this->importform->add(new \Zippy\Html\Form\File("filename"));
        $cols = array(0 => '-', 'A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D', 'E' => 'E', 'F' => 'F', 'G' => 'G');
        $this->importform->add(new DropDownChoice("colcustcode", $cols));
        $this->importform->add(new DropDownChoice("colitemcode", $cols));
        $this->importform->add(new DropDownChoice("colqty", $cols));
        $this->importform->add(new DropDownChoice("colprice", $cols));
        $this->importform->add(new DropDownChoice("colcomment", $cols));
        $this->importform->add(new CheckBox("passfirst"));
        $this->importform->add(new DropDownChoice("icust", Customer::findArray("customer_name","status=0 and  (detail like '%<type>2</type>%'  or detail like '%<type>0</type>%' )","customer_name"), 0));
       
  
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
 
        $row->add(new Label('updatedon',H::fd($item->updatedon) ));
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
        $this->importform->setVisible(false);
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
        $this->_item->updatedon = time();
    
        
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
    
    
    
    public function onImport($sender) {
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(false);
        $this->importform->setVisible(true);
        $this->importform->clean();
    }
    
    public function onLoad($sender) {
      $cust = $sender->icust->getValue();
      $passfirst = $sender->passfirst->isChecked();
      $colcustcode = $sender->colcustcode->getValue();
      $colitemcode = $sender->colitemcode->getValue();
      $colprice = $sender->colprice->getValue();
      $colqty = $sender->colqty->getValue();
      $colcomment = $sender->colcomment->getValue();
      if ($colcustcode === '0') {
            $this->setError('noselcolcustcode');
            return;
      }
      if ($colitemcode === '0') {
            $this->setError('noselcolitemcode');
            return;
      }
      if ($colprice === '0') {
            $this->setError('noselcolprice');
            return;
      }
      if ( $cust == 0) {
            $this->setError('noselsender');
            return;
      }
    
      $file = $sender->filename->getFile();
      if (strlen($file['tmp_name']) == 0) {

            $this->setError('noselfile');
            return;
      }

 
        $oSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']); 

        $data = array();
 
        $oCells = $oSpreadsheet->getActiveSheet()->getCellCollection();

        for ($iRow = ($passfirst ? 2 : 1); $iRow <= $oCells->getHighestRow(); $iRow++) {

            $row = array();
            for ($iCol = 'A'; $iCol <= $oCells->getHighestColumn(); $iCol++) {
                $oCell = $oCells->get($iCol . $iRow);
                if ($oCell) {
                    $row[$iCol] = $oCell->getValue();
                }
            }
            $data[$iRow] = $row;
  
        }

        unset($oSpreadsheet);
        $cnt=0;
        foreach ($data as $row) {
            $price = doubleval(str_replace(',', '.', trim($row[$colprice])))   ;
            if($price==0) continue;
            $qty = doubleval(str_replace(',', '.', trim($row[$colqty])))   ;
            if($qty==0) $qty=null;
            $comment =  trim($row[$colcomment])   ;
            $itemcode =  trim($row[$colitemcode])   ;
            $custcode =  trim($row[$colcustcode])   ;

            if(strlen($custcode)==0) continue;
            
            
            $item = CustItem::getFirst("customer_id={$cust} and cust_code=".CustItem::qstr($custcode))   ;
            
            if($item == null){
              if(strlen($itemcode)==0) continue;
              $it = Item::getFirst('item_code='. Item::qstr($itemcode)) ; 
              if($it==null) continue;
                
              $item = new CustItem();
              $item->customer_id = $cust;
              $item->cust_code = $custcode;
              $item->item_id = $it->item_id;
            }
            $item->price = $price;
            $item->quantity = $qty;
            $item->comment =$comment;
            $item->updatedon = time();
    
           
            $item->save();
            $cnt++;
            
        }
        $this->setSuccess("imported_items", $cnt);
        $this->itemtable->listform->itemlist->Reload();
  
        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
        $this->importform->setVisible(false);
   
     
   }
    
    public function oncsv($sender) {
        $list = $this->itemtable->listform->itemlist->getDataSource()->getItems(-1, -1 );
        $header = array();
        $data = array();

        $i = 0;
        foreach ($list as $item) {
            $i++;
           
            $data['A' . $i] = $item->itemname;
            $data['B' . $i] = $item->item_code;
            $data['C' . $i] = $item->cust_code;
            $data['D' . $i] = $item->customer_name;
            $data['E' . $i] = $item->quantity;
            $data['F' . $i] = $item->price;
            $data['G' . $i] = $item->comment;
        }

        H::exportExcel($data, $header, 'custitems.xlsx');
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

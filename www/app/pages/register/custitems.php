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

/**
* Журнал товары   у поставщика
*/
class CustItems extends \App\Pages\Base
{
    private $_item;
    private $_edit;


    public function __construct( ) {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('CustItems')) {
            return;
        }

        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');

        $this->filter->add(new TextInput('searchkey'));
        $this->filter->add(new TextInput('searchbrand'));
        $this->filter->add(new TextInput('searchstore'));
        
        $this->filter->add(new DropDownChoice('searchcust', [], 0));
        $this->updateFilter();
        
        $this->add(new Panel('itemtable'))->setVisible(true);
        $this->itemtable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->itemtable->add(new ClickLink('imports'))->onClick($this, 'onImport');
        $this->itemtable->add(new ClickLink('csv', $this, 'oncsv'));
        $this->itemtable->add(new ClickLink('options', $this, 'onOption'));

        $this->itemtable->add(new Form('listform'));

        $this->itemtable->listform->add(new DataView('itemlist', new CustItemDataSource($this), $this, 'itemlistOnRow'));
        $this->itemtable->listform->itemlist->setPageSize(H::getPG());
        $this->itemtable->listform->add(new \Zippy\Html\DataList\Paginator('pag', $this->itemtable->listform->itemlist));
        $this->itemtable->listform->add(new SubmitLink('deleteall'))->onClick($this, 'OnDelAll');


        $this->add(new Form('itemdetail'))->setVisible(false);
        $this->itemdetail->add(new AutocompleteTextInput('editcust'))->onText($this, 'OnAutoCust');
        $this->itemdetail->add(new TextInput('editbrand'));
        $this->itemdetail->add(new TextInput('editstore'));
        $this->itemdetail->add(new TextInput('editprice'));
        $this->itemdetail->add(new TextInput('editqty'));
        $this->itemdetail->add(new TextInput('editcustcode'));
        $this->itemdetail->add(new TextInput('editcustname'));
        $this->itemdetail->add(new TextInput('editcustbarcode'));
        $this->itemdetail->add(new TextArea('editcomment'));

        $this->itemdetail->add(new SubmitButton('save'))->onClick($this, 'OnSubmit');
        $this->itemdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
  

       
        $this->add(new Form('importform'))->setVisible(false);
        $this->importform->add(new Button('back'))->onClick($this, 'cancelOnClick');
        $this->importform->add(new \Zippy\Html\Form\File("filename"));
        $cols = array(0=>'-','A'=>'A','B'=>'B','C'=>'C','D'=>'D','E'=>'E','F'=>'F','G'=>'G','H'=>'H','I'=>'I');
        
        $this->importform->add(new DropDownChoice("colcustname", $cols));
        $this->importform->add(new DropDownChoice("colcustcode", $cols));
        $this->importform->add(new DropDownChoice("colcustbarcode", $cols));
        $this->importform->add(new DropDownChoice("colbrand", $cols));
        $this->importform->add(new DropDownChoice("colstore", $cols));
        $this->importform->add(new DropDownChoice("colqty", $cols));
        $this->importform->add(new DropDownChoice("colprice", $cols));
        $this->importform->add(new DropDownChoice("colcomment", $cols));
        $this->importform->add(new CheckBox("passfirst"));
        $this->importform->add(new AutocompleteTextInput("icust"))->onText($this, 'OnAutoCust');
        $this->importform->add(new SubmitButton('loadimport'))->onClick($this, 'onLoad');
     
        $this->add(new Form('optionsform'))->onSubmit($this, 'OnSaveOption');
        $this->optionsform->setVisible(false); 
        $this->optionsform->add(new CheckBox("optupdate"))  ;
        $this->optionsform->add(new TextInput('optclean' ));
        $this->optionsform->add(new DropDownChoice('compare',[],0 ));
        $this->optionsform->add(new Button('cancelo'))->onClick($this, 'cancelOnClick');
                                            
                                   
        $this->itemtable->listform->itemlist->Reload();
    }

    public function itemlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();
        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);

        
     
        $row->add(new Label('cust_code', $item->cust_code));
        $row->add(new Label('cust_name', $item->cust_name));
        $row->add(new Label('brand', $item->brand));
        $row->add(new Label('store', $item->store));
        $row->add(new Label('bar_code', $item->bar_code));
        $row->add(new Label('item_code', $item->item_code));
        $row->add(new Label('customer_name', $item->customer_name));
        $row->add(new Label('qty', $item->quantity == 0 ? '-- ' : $item->quantity ));

        $row->add(new Label('price', $item->price));

        $row->add(new Label('updatedon', H::fd($item->updatedon)));
        $row->add(new Label('comment', $item->comment));

        $row->add(new CheckBox('seldel', new \Zippy\Binding\PropertyBinding($item, 'seldel')));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new Label('onstore'))->setVisible($item->item_id >0);
        $row->onstore->setAttribute('onclick',"itemInfo({$item->item_id})");
     

        $row->add(new TextInput('cartqty', new \Zippy\Binding\PropertyBinding($item, 'cartqty'))) ;
        $row->add(new SubmitLink('cart'))->onClick($this, 'cartOnClick');

    }

    public function addOnClick($sender) {
        $this->_edit = false;
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);
        // Очищаем  форму
        $this->itemdetail->clean();

        $this->_item = new CustItem();


    }

    public function editOnClick($sender) {
        $this->_edit = true;
        $item = $sender->owner->getDataItem();
        $this->_item = CustItem::load($item->custitem_id);

        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);

        $this->itemdetail->editcust->setKey($this->_item->customer_id);
        $this->itemdetail->editcust->setText($this->_item->customer_name);
        $this->itemdetail->editcustname->setText($this->_item->cust_name);
        $this->itemdetail->editcustbarcode->setText($this->_item->bar_code);
        $this->itemdetail->editprice->setText($this->_item->price);
        $this->itemdetail->editqty->setText($this->_item->quantity);
        $this->itemdetail->editcustcode->setText($this->_item->cust_code);
        $this->itemdetail->editbrand->setText($this->_item->brand);
        $this->itemdetail->editstore->setText($this->_item->store);
        $this->itemdetail->editcomment->setText($this->_item->comment);

    }

    public function cancelOnClick($sender) {
        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
        $this->importform->setVisible(false);
        $this->optionsform->setVisible(false);
        $this->updateFilter();
        $this->itemtable->listform->itemlist->Reload();
        
    }

    public function OnSubmit($sender) {
        if (false == \App\ACL::checkEditRef('CustItems')) {
            return;
        }
        $this->_item->customer_id = $this->itemdetail->editcust->getKey();
        $this->_item->price = $this->itemdetail->editprice->getText();
        $this->_item->quantity = $this->itemdetail->editqty->getText();
        $this->_item->cust_code = trim($this->itemdetail->editcustcode->getText());
        $this->_item->cust_name = $this->itemdetail->editcustname->getText();
        $this->_item->bar_code = $this->itemdetail->editcustbarcode->getText();
        $this->_item->brand = trim($this->itemdetail->editbrand->getText() );
        $this->_item->store = trim($this->itemdetail->editstore->getText() );
        $this->_item->comment = $this->itemdetail->editcomment->getText();
        $this->_item->updatedon = time();


    
        if ($this->_item->customer_id == 0) {
            $this->setError('Не обрано постачальника');
            return;
        }

        
        $it =  $this->_item->findItem();
        if($it != null) {
           $this->_item->item_id= $it->item_id; 
        }
        $this->_item->save();
        $this->updateFilter(); 

        if($this->_edit) {
            $this->itemtable->setVisible(true);
            $this->itemdetail->setVisible(false);
            $this->itemtable->listform->itemlist->Reload(false);
                   
        }  else {
            $this->itemdetail->editcustname->setText('');
            $this->itemdetail->editprice->setText('');
            $this->itemdetail->editqty->setText('');
            $this->itemdetail->editcustcode->setText('');
            $this->itemdetail->editcustbarcode->setText('');
            $this->itemdetail->editbrand->setText('');
            $this->itemdetail->editstore->setText('');
            $this->itemdetail->editcomment->setText('');
            $this->_item = new CustItem(); 

        }
    }
    
    public function OnFilter($sender) {
        $this->itemtable->listform->itemlist->Reload();
    }

    public function updateFilter( ) {
       $this->filter->searchcust->setOptionList( Customer::findArray("customer_name", "  customer_id in (select customer_id from custitems )", "customer_name") );

       $conn = \ZDB\DB::getConnect();
     
       $d=[];
       foreach($conn->GetCol("select distinct(brand) from custitems order  by brand ") as $b){
           if(strlen($b ??'') >0) {
              $d[]=$b; 
           }
       }
       $this->filter->searchbrand->setDataList($d);

       $d=[];
       foreach($conn->GetCol("select distinct(store) from custitems order  by store ") as $b){
           if(strlen($b ??'') >0) {
              $d[]=$b; 
           }
       }

       $this->filter->searchstore->setDataList($d);
       
       
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

    public function OnAutoCust($sender) {
        $text = trim($sender->getText());
        $stext = Customer::qstr('%' . $text . '%');

        return Customer::findArray("customer_name", "  customer_name like {$stext}   ");
    }

    public function onImport($sender) {
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(false);
        $this->importform->setVisible(true);
        $this->importform->clean();
    }

    public function onLoad($sender) {
        $cust =  $this->importform->icust->getKey();
        $passfirst =  $this->importform->passfirst->isChecked();
        $colcustname =  $this->importform->colcustname->getValue();
        $colcustcode =  $this->importform->colcustcode->getValue();
        $colcustbarcode =  $this->importform->colcustbarcode->getValue();
        $colbrand =  $this->importform->colbrand->getValue();
        $colstore =  $this->importform->colstore->getValue();
        $colprice =  $this->importform->colprice->getValue();
        $colqty =  $this->importform->colqty->getValue();
        $colcomment =  $this->importform->colcomment->getValue();
        if ( $colcustcode === '0') {
            $this->setError('Не вказано колонку з кодом постачальника');
            return;
        }
        if ($colcustname === '0') {
            $this->setError('Не вказано колонку з назвою');
            return;
        }
        if ($colprice === '0') {
            $this->setError('Не вказано колонку з ціною');
            return;
        }
        if ($cust == 0) {
            $this->setError('Не обрано постачальника');
            return;
        }

        $file =  $this->importform->filename->getFile();
        if (strlen($file['tmp_name']) == 0) {

            $this->setError('Не обрано файл');
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
            if($price==0) {
                continue;
            }
            $qty = doubleval(str_replace(',', '.', trim($row[$colqty])))   ;
            if($qty==0) {
                $qty=null;
            }
            $custname =  trim($row[$colcustname])   ;
            $comment  =  trim($row[$colcomment])   ;
            $brand    =  trim($row[$colbrand])   ;
            $store    =  trim($row[$colstore])   ;
            $custcode =  trim($row[$colcustcode])   ;
            $custbarcode =  trim($row[$colcustbarcode])   ;

            if(strlen($custcode)==0) {
                continue;
            }


            $item = CustItem::getFirst("customer_id={$cust} and cust_code=".CustItem::qstr($custcode) )   ;

            if($item == null) {
                $item = new CustItem();
            }
              
            $item->customer_id = $cust;
            $item->cust_name = $custname;
            $item->cust_code = $custcode;
            $item->bar_code = $custbarcode;
                    
            $item->price = $price;
            $item->quantity = $qty;
            $item->comment =$comment;
            $item->brand = $brand;
            $item->store = $store;
            $item->updatedon = time();

            $it =  $item->findItem();
            if($it != null) {
                $item->item_id= $it->item_id; 
            }
            $item->save();
            $cnt++;

        }
        $this->setSuccess("Імпортовано {$cnt} ТМЦ");
        $this->itemtable->listform->itemlist->Reload();

        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
        $this->importform->setVisible(false);

        $this->updateFilter();

    }

    public function onOption($sender) {
        $common = System::getOptions("common");
   
        $this->optionsform->optupdate->setChecked($common['ci_update'] ??0) ;
        $this->optionsform->optclean->setText($common['ci_clean'] ??'') ;
        $this->optionsform->compare->setValue($common['ci_compare'] ?? 0) ;
      
        $this->itemtable->setVisible(false);
        $this->optionsform->setVisible(true);
         
    }

    
    public function OnSaveOption($sender) {
        $common = System::getOptions("common");
   
        $common['ci_update'] = $this->optionsform->optupdate->isChecked() ? 1:0 ;
        $common['ci_clean'] = $this->optionsform->optclean->getText()  ;
        $common['ci_compare'] = $this->optionsform->compare->getValue()  ;
        System::setOptions("common",$common)  ;
        $this->itemtable->setVisible(true);
        $this->optionsform->setVisible(false);
   
    }
    
    public function cartOnClick($sender) {
        $ci =  $sender->getOwner()->getDataItem();
        if(intval($ci->cartqty)==0)  {
            $this->setError('Не задана кiлькiсть ') ;
            return   ;
        }
      
        try{
            
        $item = $ci->findItem();
        if($item==null){
           $item = new  Item();
           $item->itemname =  $ci->cust_name;
           $item->item_code =  $ci->cust_code;
           $item->bar_code =  $ci->bar_code;
           $item->manufacturer =  $ci->brand;
           
           $item->save(); 
        }   
          
           
        //ищем незакрытую заявку
        $co = \App\Entity\Doc\Document::getFirst("meta_name='OrderCust' and  customer_id={$ci->customer_id}   and state=1 ","document_id desc") ;
        
        if($co==null) {
            $co = \App\Entity\Doc\Document::create('OrderCust');
            $co->document_number = $co->nextNumber();        
            $co->customer_id = $ci->customer_id;        
            $co->save();
            $co->updateStatus(1);
        }  else {
            $co->document_date = time(); 
            $co->save();
        }      
      
     
        

            $items=  $co->unpackDetails('detaildata');
            $i=-1;
            foreach($items as $k=>$v)  {
                if($v->item_id == $item->item_id ) {
                    $i = $k;
                    break;
                }
            }
            if($i==-1)  {
            //  $item = \App\Entity\Item::load($item->item_id);
     
                $item->quantity = $ci->cartqty;
                $item->price = $ci->price;
                $item->rowid = $item->item_id;        
                $items[$item->rowid]=$item;
            }   else {
                $items[$i]->quantity += $ci->cartqty;  
            }
            $total = 0;


            foreach ($items as $item) {
                $item->amount = \App\Helper::fa($item->price * $item->quantity);

                $total = $total + $item->amount;
            }
            $co->amount= \App\Helper::fa($total);
            
            
            $co->packDetails('detaildata',$items);
            $co->save();

            $ci->cartqty='';     
            
            return "";
        } catch(\Exception $e){
            return $e->getMessage() ;
        }        
        
        
    }
    
    
    public function oncsv($sender) {
        $list = $this->itemtable->listform->itemlist->getDataSource()->getItems(-1, -1);
        $header = array();
        $data = array();

        $i = 0;
        foreach ($list as $item) {
            $i++;

            $data['A' . $i] = $item->customer_name;
            $data['B' . $i] = $item->custname;
            $data['C' . $i] = $item->cust_code;
            $data['D' . $i] = $item->bar_code;
            $data['E' . $i] = $item->brand;
            $data['F' . $i] = $item->store;
            $data['G' . $i] = $item->quantity;
            $data['H' . $i] = $item->price;
            $data['I' . $i] = $item->comment;
       
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

    private function getWhere( ) {

        $form = $this->page->filter;
        $where = "1=1 ";
        $key = $form->searchkey->getText();
        $brand = $form->searchbrand->getText();
        $store = $form->searchstore->getText();
  
        $cust = $form->searchcust->getValue();
        if ($cust  > 0) {
            $where = $where . " and customer_id=" . $cust;
        }
        $cust = $form->searchcust->getValue();
        if (strlen($brand)  > 0) {
            $where = $where . " and brand=" . CustItem::qstr($brand);  
        }
        if (strlen($store)  > 0) {
            $where = $where . " and store=" . CustItem::qstr($store);  
        }

        if (strlen($key) > 0) {

            $skey = CustItem::qstr('%' . $key . '%');
            $key = CustItem::qstr($key);
            $where  = "   (cust_name like {$skey} or cust_code = {$key}  or bar_code = {$key} )  ";

        }



        return $where;
    }

    public function getItemCount() {
        return CustItem::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $sortfield = "cust_name asc";

        $l = CustItem::find($this->getWhere(), $sortfield, $count, $start);

        return $l;
    }

    public function getItem($id) {
        return Item::load($id);
    }

}


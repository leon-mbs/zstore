<?php

namespace App\Pages\Doc;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\AutocompleteTextInput;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\SubmitLink;
use \App\Entity\Doc\Document;
use \App\Entity\Item;
use \App\Entity\Stock;
use \App\Entity\Store;
use \App\Application as App;
use \App\Helper as H;

/**
 * Инвентаризация    склада
 */
class Inventory extends \App\Pages\Base {

    public $_itemlist = array();
    private $_doc;
    private $_rowid = 0;

    public function __construct($docid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()))->onChange($this, 'OnChangeStore');
        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('barcode'));
        $this->docform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick'); 
 
        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->add(new Form('editdetail'))->setVisible(false);

        $this->editdetail->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutocompleteItem');
        $this->editdetail->edititem->onChange($this, 'OnChangeItem', false);

        $this->editdetail->add(new TextInput('editquantity'))->setText("1");

        
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');
        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->store->setValue($this->_doc->headerdata['store']);
             
            $this->docform->notes->setText($this->_doc->notes);

            foreach ($this->_doc->detaildata as $item) {
                $stock = new Stock($item);
                $this->_itemlist[$stock->stock_id] = $stock;
            }
        } else {
            $this->_doc = Document::create('MoveItem');
            $this->docform->document_number->setText($this->_doc->nextNumber());
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        
        if (false == \App\ACL::checkShowDoc($this->_doc))
            return;
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('item', $item->itemname));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? date('Y-m-d', $item->sdate) : ''));


        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('qfact', H::fqty($item->qfact)));
 
        $row->add(new ClickLink('plus'))->onClick($this, 'plusOnClick');
        $row->add(new ClickLink('minus'))->onClick($this, 'minusOnClick');
  
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
     
    }
    public function plusOnClick($sender) {
        $item = $sender->owner->getDataItem();
        $this->_itemlist[$item->item_id]->qfact  +=1;
  
        $this->docform->detail->Reload();
    }
    public function minusOnClick($sender) {
        $item = $sender->owner->getDataItem();
        $this->_itemlist[$item->item_id]->qfact  -=1;
   
        $this->docform->detail->Reload();
    }
    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $item = $sender->owner->getDataItem();
        // unset($this->_itemlist[$item->item_id]);

        $this->_itemlist = array_diff_key($this->_itemlist, array($item->stock_id => $this->_itemlist[$item->stock_id]));
        $this->docform->detail->Reload();
    }

    public function addrowOnClick($sender) {
        if ($this->docform->storefrom->getValue() == 0) {
            $this->setError("Выберите склад источник");
            return;
        }        
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setValue('');
         
    }

  
    public function saverowOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $id = $this->editdetail->edititem->getKey();
        if ($id == 0) {
            $this->setError("Не выбран товар");
            return;
        }


        $stock = Stock::load($id);
        $stock->quantity = $this->editdetail->editquantity->getText();



        $this->_itemlist[$stock->stock_id] = $stock;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();

        //очищаем  форму
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setValue('');
        $this->editdetail->editquantity->setText("1");
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setText('');

        $this->editdetail->editquantity->setText("1");
    }

    public function savedocOnClick($sender) {
        if ($this->checkForm() == false) {
            return;
        }
        $this->_doc->notes = $this->docform->notes->getText();


        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['storemame'] = $this->docform->store->getValueName();
  

        $this->_doc->detaildata = array();
        foreach ($this->_itemlist as $item) {
            $this->_doc->detaildata[] = $item->getData();
        }


        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

            $this->_doc->save();
            if ($sender->id == 'execdoc') {
                if (!$isEdited)
                    $this->_doc->updateStatus(Document::STATE_NEW);
                $this->_doc->updateStatus(Document::STATE_EXECUTED);
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }
            $conn->CommitTrans();
            App::RedirectBack();
        } catch (\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);

            return;
        }
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {

        if (strlen(trim($this->docform->document_number->getText())) == 0) {
            $this->setError("Не введен номер документа");
        }
        if (count($this->_itemlist) == 0) {
            $this->setError("Не введен ни один  товар");
        }
     


        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnChangeItem($sender) {
        $stock_id = $sender->getKey();
        $stock = Stock::load($stock_id);
         
        $this->editdetail->qtystock->setText(H::fqty($stock->qty));


         
    }

    public function OnChangeStore($sender) {
      
            //очистка  списка  товаров
            $this->_itemlist = array();
            $this->docform->detail->Reload();
        
    }

    public function OnItemType($sender) {
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setText('');

        $this->editdetail->editquantity->setText("1");
    }

    public function OnAutocompleteItem($sender) {
        $store_id = $this->docform->storefrom->getValue();
        $text = trim($sender->getText());
        return Stock::findArrayAC($store_id, $text);
    }
 
    public function addcodeOnClick($sender) {
        $code =  trim($this->docform->barcode->getText()) ;
        $this->docform->barcode->setText('');
     
         
        $store = $this->docform->storefrom->getValue() ;
        $code = Stock::qstr($code)  ;
        $item = Stock::getFirst("store_id={$store} and qty > 0  and (item_code = {$code} or bar_code = {$code})","qty desc"  );
        
        if($item == null) {
           $this->setError('Товар не  найден')     ;
                   return; 
        }
  
        if(!isset($this->_itemlist[$item->stock_id])){
   
            $this->_itemlist[$item->stock_id] = $item;
            $item->quantity=0;
            
        }   
        if($this->_itemlist[$item->stock_id]->quantity == (int)$item->qty) {
             $this->setError('Больше  нет товаров по цене '. $item->partion)     ;
                   return; 
          
        }
        $this->_itemlist[$item->stock_id]->quantity  +=1;
  
        $this->docform->detail->Reload();
    }
 }

 

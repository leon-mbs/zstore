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
 * Страница  ввода оприходование товаров
 */
class IncomeItem extends \App\Pages\Base {

    public $_itemlist = array();
    private $_doc;
    private $_rowid = 0;

    public function __construct($docid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));

        
        $this->docform->add(new DropDownChoice('storeto', Store::getList(), 0))->onChange($this, 'OnChangeStore');
        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('barcode'));
        $this->docform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');
        $this->docform->add(new Label('total'));
        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->add(new Form('editdetail'))->setVisible(false);

        $this->editdetail->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutocompleteItem');
        

        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editsnumber'));
        $this->editdetail->add(new Date('editsdate'));

        
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');
        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);
            
            $this->docform->storeto->setValue($this->_doc->headerdata['storeto']);
            $this->docform->notes->setText($this->_doc->notes);

            foreach ($this->_doc->detaildata as $_item) {
                $item = new Item($_item);
                $item->old = true;                
                $this->_itemlist[$item->item_id . $item->snumber] = $item;
            }
            $this->calcTotal();            
        } else {
            $this->_doc = Document::create('IncomeItem');
            $this->docform->document_number->setText($this->_doc->nextNumber());
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        $this->OnChangeStore($this->docform->storeto);
        if (false == \App\ACL::checkShowDoc($this->_doc))
            return;
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('item', $item->itemname));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? date('Y-m-d', $item->sdate) : ''));

        $row->add(new Label('price', H::fa($item->price)));

        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));

        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->edit->setVisible($item->old != true);
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $item = $sender->owner->getDataItem();
        $id = $item->item_id . $item->snumber;

        $this->_itemlist = array_diff_key($this->_itemlist, array($id => $this->_itemlist[$id]));
        $this->docform->detail->Reload();
    }

    public function addrowOnClick($sender) {

        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setValue('');
        
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity);


        $this->editdetail->edititem->setKey($item->item_id);
        $this->editdetail->edititem->setValue($item->itemname);
       $this->editdetail->editprice->setText($item->price);
        $this->editdetail->editsnumber->setText($item->snumber);
        $this->editdetail->editsdate->setDate($item->sdate);
 
        

        $this->_rowid = $item->item_id . $item->snumber;
    }

    public function saverowOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $id = $this->editdetail->edititem->getKey();
        if ($id == 0) {
            $this->setError("Не выбран товар");
            return;
        }

        $item = Item::load($id);

     
        $item->quantity = $this->editdetail->editquantity->getText();
        $item->price = $this->editdetail->editprice->getText();

        if ($item->price == 0) {
            $this->setWarn("Не указана цена");
        }
        
        $item->snumber = trim($this->editdetail->editsnumber->getText());
        if(strlen($item->snumber)==0 && $item->useserial==1 && $this->_tvars["usesnumber"] == true ){
            $this->setError("Товар требует ввода партии производителя");
            return;
        }
        $item->sdate = $this->editdetail->editsdate->getDate();
        if ($item->sdate == false)
            $item->sdate = '';
        if (strlen($item->snumber) > 0 && strlen($item->sdate) == 0) {
            $this->setError("К серии должна быть введена дата срока годности");
            return;
        }
        $this->_itemlist[$id . $item->snumber] = $item;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();
                $this->calcTotal();
        //очищаем  форму
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setValue('');
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("");
        $this->editdetail->editsnumber->setText("");
        $this->editdetail->editsdate->setText("");        
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setText('');

        $this->editdetail->editquantity->setText("1");
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;      
        if ($this->checkForm() == false) {
            return;
        }
        $this->_doc->notes = $this->docform->notes->getText();

        
        $this->_doc->headerdata['storeto'] = $this->docform->storeto->getValue();
        $this->_doc->headerdata['storetoname'] = $this->docform->storeto->getValueName();


        $this->_doc->detaildata = array();
        foreach ($this->_itemlist as $item) {
            $this->_doc->detaildata[] = $item->getData();
        }


        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->amount = $this->docform->total->getText();
        
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
     private function calcTotal() {

        $total = 0;

        foreach ($this->_itemlist as $item) {
            $item->amount = $item->price * $item->quantity;
            $total = $total + $item->amount;
        }
        $this->docform->total->setText(round($total));
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
        
        if (($this->docform->storeto->getValue() > 0 ) ==false) {
            $this->setError("Не выбран  склад");
        }
        


        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

  

    public function OnChangeStore($sender) {
 
            //очистка  списка  товаров
            $this->_itemlist = array();
            $this->docform->detail->Reload();
         
    }

 

    public function OnAutocompleteItem($sender) {
       
        $text = trim($sender->getText());
        return Item::findArrayAC($text );
    }

    public function addcodeOnClick($sender) {
        $code = trim($this->docform->barcode->getText());
        $this->docform->barcode->setText('');


        $code = Item::qstr($code);

        $item = Item::getFirst("    (item_code = {$code} or bar_code = {$code})");
        if ($item == null) {
            $this->setError('Товар не  найден');
            return;
        }

        if ($this->_tvars["usesnumber"] == true) {

            $this->editdetail->setVisible(true);
            $this->docform->setVisible(false);
            $this->editdetail->edititem->setKey($item->item_id);
            $this->editdetail->edititem->setText($item->itemname);
            $this->editdetail->editsnumber->setText('');
            $this->editdetail->editquantity->setText('1');
            return;
        }
        if (!isset($this->_itemlist[$item->item_id])) {

            $this->_itemlist[$item->item_id] = $item;
            $item->quantity = 0;
        }

        $this->_itemlist[$item->item_id]->quantity += 1;

        $this->docform->detail->Reload();
    }

}

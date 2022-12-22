<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Stock;
use App\Entity\Store;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода перемещение товаров
 */
class MoveItem extends \App\Pages\Base
{

    public  $_itemlist = array();
    private $_doc;
    private $_rowid    = 0;

    public function __construct($docid = 0,$basedocid=0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()))->onChange($this, 'OnChangeStore');
        $this->docform->add(new DropDownChoice('tostore', Store::getList(), H::getDefStore()))->onChange($this, 'OnChangeStore');

        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('barcode'));
        $this->docform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->add(new Form('editdetail'))->setVisible(false);

        $this->editdetail->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutocompleteItem');
        $this->editdetail->edititem->onChange($this, 'OnChangeItem', true);

        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editsnumber'))->setText("");

        $this->editdetail->add(new Label('qtystock'));
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');
        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->tostore->setValue($this->_doc->headerdata['tostore']);

            $this->docform->notes->setText($this->_doc->notes);

            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('MoveItem');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'ProdReceipt') {
                        $this->docform->store->setValue($basedoc->headerdata['store']);

                    
                        $this->_itemlist = $basedoc->unpackDetails('detaildata');
                    }
                    if ($basedoc->meta_name == 'GoodsReceipt') {
                        $this->docform->store->setValue($basedoc->headerdata['store']);

                    
                        $this->_itemlist = $basedoc->unpackDetails('detaildata');
                    }
                }
                 
            }
        
        
        
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();

        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('item', $item->itemname));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('snumber', $item->snumber));

        $row->add(new Label('quantity', H::fqty($item->quantity)));

        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');

        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $item = $sender->owner->getDataItem();
        if ($item->rowid > 0) {
            ;
        }               //для совместимости
        else {
            $item->rowid = $item->item_id;
        }

        $this->_itemlist = array_diff_key($this->_itemlist, array($item->rowid => $this->_itemlist[$item->rowid]));
      $this->docform->detail->Reload();
    }

    public function addrowOnClick($sender) {
        if ($this->docform->store->getValue() == 0) {
            $this->setError("noselstore");
            return;
        }
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setValue('');
        $this->editdetail->qtystock->setText('');
        $this->editdetail->editsnumber->setText('');
        $this->_rowid = 0;
         
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity);

        $this->editdetail->edititem->setKey($item->item_id);
        $this->editdetail->edititem->setValue($item->itemname);
        $this->editdetail->editsnumber->setValue($item->snumber);

        $this->editdetail->qtystock->setText(H::fqty($item->getQuantity($this->docform->store->getValue())));

        if ($item->rowid > 0) {
            ;
        }               //для совместимости
        else {
            $item->rowid = $item->item_id;
        }

        $this->_rowid = $item->rowid;
    }

    public function saverowOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $id = $this->editdetail->edititem->getKey();
        if ($id == 0) {
            $this->setError("noselitem");
            return;
        }

        $item = Item::load($id);
        $item->snumber = trim($this->editdetail->editsnumber->getText());
        $item->quantity = $this->editdetail->editquantity->getText();
        if (strlen($item->snumber) == 0 && $item->useserial == 1 && $this->_tvars["usesnumber"] == true) {
            $this->setError("needs_serial");
            return;
        }

        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {
            $slist = $item->getSerials($this->docform->store->getValue());

            if (in_array($item->snumber, $slist) == false) {
                $this->setWarn('invalid_serialno');
            }
        }


        if ($this->_rowid > 0) {
            $item->rowid = $this->_rowid;
             
            
        } else {
            $next = count($this->_itemlist) > 0 ? max(array_keys($this->_itemlist)) : 0;
            $item->rowid = $next + 1;
        }
        $this->_itemlist[$item->rowid] = $item;

        $this->_rowid = 0;

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
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }


        $this->_doc->notes = $this->docform->notes->getText();

        $this->_doc->headerdata['tostore'] = $this->docform->tostore->getValue();
        $this->_doc->headerdata['tostorename'] = $this->docform->tostore->getValueName();
        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['storename'] = $this->docform->store->getValueName();

        $this->_doc->packDetails('detaildata', $this->_itemlist);

        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        
        if ($this->checkForm() == false) {
            return;
        }        
        
        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

            $this->_doc->save();
            if ($sender->id == 'execdoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }

                // проверка на минус  в  количестве
                $allowminus = \App\System::getOption("common", "allowminus");
                if ($allowminus != 1) {

                    foreach ($this->_itemlist as $item) {
                        $qty = $item->getQuantity($this->_doc->headerdata['store']);
                        if ($qty < $item->quantity) {
                            $this->setError("nominus", H::fqty($qty), $item->itemname);
                            return;
                        }
                    }
                }
                $this->_doc->updateStatus(Document::STATE_EXECUTED);
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }


            $conn->CommitTrans();
        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);

            return;
        }
        App::Redirect("\\App\\Pages\\Register\\ItemList", $this->_doc->document_id);

    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {

        if (strlen(trim($this->docform->document_number->getText())) == 0) {
            $this->setError("enterdocnumber");
        }
        if (false == $this->_doc->checkUniqueNumber()) {
            $next = $this->_doc->nextNumber();
            $this->docform->document_number->setText($next);
            $this->_doc->document_number = $next;
            if (strlen($next) == 0) {
                $this->setError('docnumbercancreated');
            }
        }
        if (count($this->_itemlist) == 0) {
            $this->setError("noenteritem");
        }


        if (($this->docform->store->getValue() > 0) == false) {
            $this->setError("noselstore");
        }
        if (($this->docform->tostore->getValue() > 0) == false) {
            $this->setError("noselstore");
        }


        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnChangeItem($sender) {

        $item_id = $sender->getKey();
        $item = Item::load($item_id);
        $this->editdetail->qtystock->setText(H::fqty($item->getQuantity($this->docform->store->getValue())));

      
    }

    public function OnChangeStore($sender) {
        if ($sender->id == 'store') {
            //очистка  списка  товаров
            $this->_itemlist = array();
            $this->docform->detail->Reload();
        }
    }

    public function OnItemType($sender) {
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setText('');

        $this->editdetail->editquantity->setText("1");
    }

    public function OnAutocompleteItem($sender) {
        $store_id = $this->docform->store->getValue();
        $text = trim($sender->getText());
        return Item::findArrayAC($text, $store_id);
    }

    public function addcodeOnClick($sender) {
        $code = trim($this->docform->barcode->getText());
         $code0 = $code;
         $code = ltrim($code,'0');

        $this->docform->barcode->setText('');
        $store_id = $this->docform->store->getValue();
        if ($store_id == 0) {
            $this->setError('noselstore');
            return;
        }

        $code = Item::qstr($code);
        $code0 = Item::qstr($code0);

        $item = Item::getFirst(" item_id in(select item_id from store_stock where store_id={$store_id}) and     (item_code = {$code} or bar_code = {$code} or item_code = {$code0} or bar_code = {$code0}  )");
        if ($item == null) {
            $this->setError('noitem');
            return;
        }

        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {

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
        //ищем  последню цену

        $where = "store_id = {$store_id} and item_id = {$item->item_id}    ";

        $s = Stock::getFirst($where, ' stock_id  desc ');
        if ($s instanceof Stock) {
            $item->price = $s->partion;
        }
        $this->docform->detail->Reload();
    }

}

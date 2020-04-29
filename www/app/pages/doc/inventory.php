<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Item;
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
 * Инвентаризация    склада
 */
class Inventory extends \App\Pages\Base
{

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


        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editserial'))->setText("");

        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');
        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->store->setValue($this->_doc->headerdata['store']);

            $this->docform->notes->setText($this->_doc->notes);


            $this->_itemlist = $this->_doc->unpackDetails('detaildata');


        } else {
            $this->_doc = Document::create('Inventory');
            $this->docform->document_number->setText($this->_doc->nextNumber());
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();

        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('item', $item->itemname));

        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? date('Y-m-d', $item->sdate) : ''));

        //  $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('qfact', H::fqty($item->qfact)));

        //   if ($item->quantity > $item->qfact)
        //       $row->item->setAttribute('class', "text-danger");
        //   if ($item->quantity < $item->qfact)
        //       $row->item->setAttribute('class', "text-success");


        $row->add(new ClickLink('plus'))->onClick($this, 'plusOnClick');
        $row->add(new ClickLink('minus'))->onClick($this, 'minusOnClick');

        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');

        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);
    }

    public function plusOnClick($sender) {
        $item = $sender->owner->getDataItem();
        $this->_itemlist[$item->item_id]->qfact += 1;

        $this->docform->detail->Reload();
    }

    public function minusOnClick($sender) {
        $item = $sender->owner->getDataItem();
        $this->_itemlist[$item->item_id]->qfact -= 1;

        $this->docform->detail->Reload();
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $item = $sender->owner->getDataItem();
        $id = $item->item_id . $item->snumber;

        $this->_itemlist = array_diff_key($this->_itemlist, array($id => $this->_itemlist[$id]));
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
        $store = $this->docform->store->getValue();
        $sn = trim($this->editdetail->editserial->getText());

        $item->quantity = $item->getQuantity($store, $sn);
        $item->qfact = $this->editdetail->editquantity->getText();
        $item->snumber = $sn;

        $tarr = array();

        foreach ($this->_itemlist as $k => $value) {

            if ($this->_rowid > 0 && $this->_rowid == $k) {
                $tarr[$item->item_id] = $item;    // заменяем
            } else {
                $tarr[$k] = $value;    // старый
            }

        }

        if ($this->_rowid == 0) {        // в конец
            $tarr[$item->item_id] = $item;
        }
        $this->_itemlist = $tarr;
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

        if ($this->checkForm() == false) {
            return;
        }
        $this->_doc->notes = $this->docform->notes->getText();


        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['storemame'] = $this->docform->store->getValueName();


        $this->_doc->packDetails('detaildata', $this->_itemlist);


        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

            $this->_doc->save();
            if ($sender->id == 'execdoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }
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
            $this->setError("enterdocnumber");
        }
        if (false == $this->_doc->checkUniqueNumber()) {
            $this->docform->document_number->setText($this->_doc->nextNumber());
            $this->setError('nouniquedocnumber_created');
        }
        if (count($this->_itemlist) == 0) {
            $this->setError("noenteritem");
        }
        if (($this->docform->store->getValue() > 0) == false) {
            $this->setError("noselstore");
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
        $store_id = $this->docform->store->getValue();
        $text = trim($sender->getText());
        return Item::findArrayAC($text, $store_id);
    }

    public function addcodeOnClick($sender) {
        $code = trim($this->docform->barcode->getText());
        $this->docform->barcode->setText('');


        $store = $this->docform->store->getValue();
        $code_ = Item::qstr($code);

        $item = Item::getFirst("item_code={$code_} or bar_code={$code_}");
        if ($item == null) {
            $this->setError('noitemcode', $code);
            return;
        }

        if ($this->_tvars["usesnumber"] == true) {

            $this->editdetail->setVisible(true);
            $this->docform->setVisible(false);
            $this->editdetail->edititem->setKey($item->item_id);
            $this->editdetail->edititem->setText($item->itemname);
            $this->editdetail->editserial->setText('');
            $this->editdetail->editquantity->setText('1');
            return;
        }


        if (!isset($this->_itemlist[$item->item_id])) {
            $item->qfact = 0;
            $item->quantity = $item->getQuantity($store);
            $this->_itemlist[$item->item_id] = $item;
        }

        $this->_itemlist[$item->item_id]->qfact += 1;

        $this->docform->detail->Reload();
    }

}

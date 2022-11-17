<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Stock;
use App\Entity\Store;
use App\Helper as H;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;

/**
 * Страница  ввода перекомплектация товаров
 */
class TransItem extends \App\Pages\Base
{

    public  $_itemlist = array();
    private $_doc;
    private $_rowid    = 0;

    public function __construct($docid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()))->onChange($this, 'OnChangeStore');
        $this->docform->add(new DropDownChoice('tostore', Store::getList(), H::getDefStore()));
        $this->docform->add(new AutocompleteTextInput('fromitem'))->onText($this, 'OnAutocompleteItem');
        $this->docform->add(new AutocompleteTextInput('toitem'))->onText($this, 'OnAutocompleteItem');

        $this->docform->add(new TextInput('fromquantity'));
        $this->docform->add(new TextInput('toquantity'));
        $this->docform->add(new TextArea('notes'));

        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->tostore->setValue($this->_doc->headerdata['tostore']);
            $this->docform->fromitem->setKey($this->_doc->headerdata['fromitem']);
            $fi = Stock::load($this->_doc->headerdata['fromitem']);
            $this->docform->fromitem->setText($fi->itemname . ', ' . $fi->partion);
            $this->docform->toitem->setKey($this->_doc->headerdata['toitem']);
            $ti = Item::load($this->_doc->headerdata['toitem']);
            $this->docform->toitem->setText($ti->itemname);
            $this->docform->fromquantity->setText($this->_doc->headerdata['fromquantity']);
            $this->docform->toquantity->setText($this->_doc->headerdata['toquantity']);
            $this->docform->notes->setText($this->_doc->notes);
        } else {
            $this->_doc = Document::create('TransItem');
            $this->docform->document_number->setText($this->_doc->nextNumber());
        }


        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }

        $this->_doc->notes = $this->docform->notes->getText();

        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['tostore'] = $this->docform->tostore->getValue();
        $this->_doc->headerdata['fromitem'] = $this->docform->fromitem->getKey();
        $this->_doc->headerdata['toitem'] = $this->docform->toitem->getKey();
        $this->_doc->headerdata['fromitemname'] = $this->docform->fromitem->getText();
        $this->_doc->headerdata['toitemname'] = $this->docform->toitem->getText();
        $this->_doc->headerdata['fromquantity'] = $this->docform->fromquantity->getText();
        $this->_doc->headerdata['toquantity'] = $this->docform->toquantity->getText();

        if ($this->checkForm() == false) {
            return;
        }

        $fi = Stock::load($this->_doc->headerdata['fromitem']);

        $this->_doc->amount = H::fa($fi->partion * $this->_doc->headerdata['fromquantity']);
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
            App::Redirect("\\App\\Pages\\Register\\StockList");

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
        if ($this->_doc->headerdata['fromquantity'] > 0 && $this->_doc->headerdata['toquantity'] > 0) {

        } else {
            $this->setError("invalidquantity");
        }
        if ($this->_doc->headerdata['fromitem'] > 0 && $this->_doc->headerdata['toitem'] > 0) {

        } else {
            $this->setError("noenteritem");
        }
        if ($this->_doc->headerdata['fromitem'] == $this->_doc->headerdata['toitem']) {

            $this->setError("thesameitems");
        }

        return !$this->isError();
    }

    public function OnChangeStore($sender) {
        $this->docform->fromitem->setText('');
        $this->docform->fromitem->setKey(0);
        $this->docform->fromquantity->setText('');
        $this->docform->toitem->setText('');
        $this->docform->toitem->setKey(0);
        $this->docform->toquantity->setText('');
    }

    public function OnAutocompleteItem($sender) {
        $store_id = $this->docform->store->getValue();
        $text = trim($sender->getText());
        if ($sender->id == 'fromitem') {
            return Stock::findArrayAC($store_id, $text);
        } else {
            $text = Item::qstr('%' . $sender->getText() . '%');
            return Item::findArray("concat(itemname,', ',item_code)", "(itemname like {$text} or item_code like {$text})  and disabled <> 1");
        }
    }

}

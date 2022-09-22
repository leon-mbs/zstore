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

use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода перемещение партий
 */
class MovePart extends \App\Pages\Base
{

    public  $_itemlist = array();
    private $_doc;
    private $_rowid    = 0;

    public function __construct($docid = 0, $tostock = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));

        $this->docform->add(new AutocompleteTextInput('fromstock'))->onText($this, 'OnAuto');
        $this->docform->add(new AutocompleteTextInput('tostock'))->onText($this, 'OnAuto');

        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('qty'));

        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');


        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->fromstock->setKey($this->_doc->headerdata['fromstock']);
            $this->docform->fromstock->setText($this->makestr(Stock::load($this->_doc->headerdata['fromstock'])));
            $this->docform->tostock->setKey($this->_doc->headerdata['tostock']);
            $this->docform->tostock->setText($this->makestr(Stock::load($this->_doc->headerdata['tostock'])));
            $this->docform->qty->setText(H::fqty($this->_doc->headerdata['qty']));
            $this->docform->notes->setText($this->_doc->notes);


        } else {
            $this->_doc = Document::create('MovePart');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            if ($tostock > 0) {
                $this->docform->tostock->setKey($tostock);
                $this->docform->tostock->setText($this->makestr(Stock::load($tostock)));

            }


        }


        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }

        $this->_doc->notes = $this->docform->notes->getText();

        $this->_doc->headerdata['fromstock'] = $this->docform->fromstock->getkey();
        $this->_doc->headerdata['fromstockname'] = $this->docform->fromstock->getText();
        $this->_doc->headerdata['tostock'] = $this->docform->tostock->getkey();
        $this->_doc->headerdata['tostockname'] = $this->docform->tostock->getText();
        $this->_doc->headerdata['qty'] = $this->docform->qty->getText();


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


        $from = Stock::load($this->docform->fromstock->getKey());
        $to = Stock::load($this->docform->tostock->getKey());

        if ($from == null || $to == null) {
            $this->setError("noselpart");
        }
        if ($from->stock_id == $to->stock_id) {
            $this->setError("thesamestock");
        }
        if ($from->item_id != $to->item_id) {
            $this->setError("diffitem");
        }


        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    private function makestr($st) {
        if ($st == null) {
            return '';
        }
        $str = $st->storename;
        $str = $str . ', ' . $st->itemname;
        if (strlen($st->item_code) > 0) {
            $str = $str . ', ' . $st->item_code;
        }
        if (strlen($st->snumber) > 0) {
            $str = $str . ', ' . $st->snumber;
        }
        $str = $str . ', ' . H::fa($st->partion);
        $str = $str . ', ' . H::fqty($st->qty);
        return $str;
    }


    public function OnAuto($sender) {

        $text = trim($sender->getText());
        $stores = Store::find(""); //учет  филиалов

        $ret = array();
        $f = Stock::find(" store_id in (" . implode(',', array_keys($stores)) . ") and  itemdisabled <> 1 and  qty <>0 and itemname like " . Stock::qstr('%' . $text . '%') . " or item_code = " . Stock::qstr($text));
        foreach ($f as $id => $s) {
            $ret[$id] = $this->makestr($s);
        }
        return $ret;

    }


}

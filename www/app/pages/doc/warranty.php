<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода  гарантийного талона
 */
class Warranty extends \App\Pages\Base
{
    public $_itemlist = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid     = 0;

     /**
    * @param mixed $docid     редактирование
    * @param mixed $basedocid  создание на  основании
    */
    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new TextInput('customer'));

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new TextInput('notes'));

        $this->add(new Form('editdetail'))->setVisible(false);

        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editsn'));
        $this->editdetail->add(new TextInput('editwarranty'));

        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');
        $this->editdetail->edittovar->onChange($this, 'OnChangeItem', true);

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');
        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'));

        if ($docid > 0) {    //загружаем   содержимое  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            // $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->notes->setText($this->_doc->notes);

            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('Warranty');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    $this->docform->customer->setText($basedoc->headerdata['customer_name']);

                    if ($basedoc->meta_name == 'GoodsIssue') {
                        $this->_doc->customer_id= $basedoc->customer_id;
                        $this->_doc->firm_id= $basedoc->firm_id;
                        $this->_itemlist = $basedoc->unpackDetails('detaildata');

                        
                    }

                    if ($basedoc->meta_name == 'POSCheck') {
                        $this->_doc->customer_id= $basedoc->customer_id;
                        $this->_doc->firm_id= $basedoc->firm_id;
                        $this->_itemlist = $basedoc->unpackDetails('detaildata');

                        
                    }
                    if ($basedoc->meta_name == 'ServiceAct') {
                        $this->_doc->customer_id= $basedoc->customer_id;
                        $this->_doc->firm_id= $basedoc->firm_id;
                        $this->_itemlist = $basedoc->unpackDetails('detail2data');
                    }

                    if (count($basedoc->getChildren('Warranty')) > 0) {
                        $this->setWarn('Вже є документ Гарантійний талон');
                    }

                }
            }


        }

        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }

        $this->docform->detail->Reload();

    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('tovar', $item->itemname));
        $row->add(new Label('sn', $item->snumber));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('warranty', $item->warranty));
        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('price', H::fa($item->price)));
        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $item = $sender->owner->getDataItem();
        $rowid =  array_search($item, $this->_itemlist, true);

        $this->_itemlist = array_diff_key($this->_itemlist, array($rowid => $this->_itemlist[$rowid]));


        $this->docform->detail->Reload();
    }

    public function editOnClick($sender) {
        $item = $sender->owner->getDataItem();

        $this->_rowid = array_search($item, $this->_itemlist, true) ;

        $this->editdetail->edittovar->setKey($item->item_id);
        $this->editdetail->edittovar->setText($item->itemname);

        $this->editdetail->editprice->setText($item->price);

        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editwarranty->setText($item->warranty);
        $this->editdetail->editsn->setText($item->snumber);
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->_rowid = -1;
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');
        $this->editdetail->editquantity->setText('1');
        $this->editdetail->editprice->setText('0');
    }

    public function saverowOnClick($sender) {
        $id = $this->editdetail->edittovar->getKey();
        if ($id == 0) {
            $this->setError("Не обрано товар");
            return;
        }

        $item = Item::load($id);


        $item->quantity = $this->editdetail->editquantity->getText();
        $item->price = $this->editdetail->editprice->getText();
        $item->snumber = $this->editdetail->editsn->getText();
        $item->warranty = $this->editdetail->editwarranty->getText();

        if($this->_rowid == -1) {
            $this->_itemlist[] = $item;
        } else {
            $this->_itemlist[$this->_rowid] = $item;
        }



        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();

        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        $this->editdetail->editsn->setText("");
        $this->editdetail->editwarranty->setText("");
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        $this->editdetail->editsn->setText("");
        $this->editdetail->editwarranty->setText("");
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->headerdata["customer_name"] = $this->docform->customer->getText();

        $firm = H::getFirmData($this->_doc->firm_id, $this->branch_id);
        $this->_doc->headerdata["firm_name"] = $firm['firm_name'];

        $this->_doc->packDetails('detaildata', $this->_itemlist);

        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();

        if ($this->checkForm() == false) {
            return;
        }

        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            if ($this->_basedocid > 0) {
                $this->_doc->parent_id = $this->_basedocid;
                $this->_basedocid = 0;
            }
            $this->_doc->save();
            if ($sender->id == 'execdoc') {
                $this->_doc->updateStatus(Document::STATE_EXECUTED);
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }

            $conn->CommitTrans();
            App::Redirect("\\App\\Pages\\Register\\GIList");

        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $this->setError($ee->getMessage());

            $logger->error('Line '. $ee->getLine().' '.$ee->getFile().'. '.$ee->getMessage()  );
            return;
        }
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {

        if (count($this->_itemlist) == 0) {
            $this->setError("Не введено товар");
        }
        if (false == $this->_doc->checkUniqueNumber()) {
            $next = $this->_doc->nextNumber();
            $this->docform->document_number->setText($next);
            $this->_doc->document_number = $next;
            if (strlen($next) == 0) {
                $this->setError('Не створено унікальный номер документа');
            }
        }
        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutoItem($sender) {

        $text = trim($sender->getText());
        return Item::findArrayAC($text);
    }

    public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $item = Item::load($id);
        $this->editdetail->editwarranty->setText($item->warranty);


    }

}

 
 
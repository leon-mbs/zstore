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
 * Страница  ввода оприходование товаров
 */
class IncomeItem extends \App\Pages\Base
{

    public  $_itemlist  = array();
    private $_doc;
    private $_rowid     = 0;
    private $_basedocid = 0;

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));
        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('barcode'));
        $this->docform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');
        $this->docform->add(new Label('total'));
        $this->docform->add(new \Zippy\Html\Form\File('scan'));
        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new DropDownChoice('emp', \App\Entity\Employee::findArray("emp_name", "disabled<>1", "emp_name")))->onChange($this, 'OnEmp');
        $this->docform->add(new DropDownChoice('exmf', \App\Entity\MoneyFund::getList(), H::getDefMF()));
        $this->docform->add(new TextInput('examount'));
        $this->docform->add(new DropDownChoice('mtype', \App\Entity\IOState::getTypeList(3), 0));

        $this->add(new Form('editdetail'))->setVisible(false);

        $this->editdetail->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutocompleteItem');

        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editsnumber'));
        $this->editdetail->add(new Date('editsdate'));

        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');
        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitLink('addnewitem'))->onClick($this, 'addnewitemOnClick');

        
       //добавление нового товара
        $this->add(new Form('editnewitem'))->setVisible(false);
        $this->editnewitem->add(new TextInput('editnewitemname'));
        $this->editnewitem->add(new TextInput('editnewitemcode'));
        $this->editnewitem->add(new TextInput('editnewbrand'));
        $this->editnewitem->add(new TextInput('editnewmsr'));
        $this->editnewitem->add(new Button('cancelnewitem'))->onClick($this, 'cancelnewitemOnClick');
        $this->editnewitem->add(new DropDownChoice('editnewcat', \App\Entity\Category::getList(), 0));
        $this->editnewitem->add(new SubmitButton('savenewitem'))->onClick($this, 'savenewitemOnClick');
        
        
        
        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);

            $this->docform->mtype->setValue($this->_doc->headerdata['mtype']);
            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->emp->setValue($this->_doc->headerdata['emp']);
            $this->docform->exmf->setValue($this->_doc->headerdata['exmf']);
            $this->docform->examount->setText($this->_doc->headerdata['examount']);
            $this->docform->notes->setText($this->_doc->notes);

            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('IncomeItem');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'OutcomeItem') {


                        foreach ($basedoc->unpackDetails('detaildata') as $it) {

                            //последняя партия
                            $stock = \App\Entity\Stock::getFirst("item_id = {$it->item_id} and store_id={$basedoc->headerdata['store'] }", 'stock_id desc');
                            $it->price = $stock->partion;

                            $this->_itemlist[] = $it;
                        }
                    }
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }

        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->OnEmp($this->docform->emp);
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('item', $item->itemname));
        $row->add(new Label('item_code', $item->item_code));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? \App\Helper::fd($item->sdate) : ''));

        $row->add(new Label('price', H::fa($item->price)));

        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));

        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->edit->setVisible($item->old == false);
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $item = $sender->owner->getDataItem();
        $id = $item->item_id;

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

        $this->_rowid = $item->item_id;
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

        $item->quantity = $this->editdetail->editquantity->getText();
        $item->price = $this->editdetail->editprice->getText();

        if ($item->price == 0) {
            $this->setWarn("no_price");
        }

        $item->snumber = trim($this->editdetail->editsnumber->getText());
        if (strlen($item->snumber) == 0 && $item->useserial == 1 && $this->_tvars["usesnumber"] == true) {
            $this->setError("needs_serial");
            return;
        }
        $item->sdate = $this->editdetail->editsdate->getDate();
        if ($item->sdate == false) {
            $item->sdate = '';
        }
        if (strlen($item->snumber) == 0 && $item->useserial == 1 && $this->_tvars["usesnumber"] == true) {
            $this->setError("needs_serial");
            return;
        }


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
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }

        $this->_doc->notes = $this->docform->notes->getText();
        $file = $this->docform->scan->getFile();
        if ($file['size'] > 10000000) {
            $this->setError("filemore10M");
            return;
        }

        $this->_doc->headerdata['mtype'] = $this->docform->mtype->getValue();
        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['storename'] = $this->docform->store->getValueName();
        $this->_doc->headerdata['emp'] = $this->docform->emp->getValue();
        $this->_doc->headerdata['empname'] = $this->docform->emp->getValueName();
        $this->_doc->headerdata['exmf'] = $this->docform->exmf->getValue();
        $this->_doc->headerdata['examount'] = $this->docform->examount->getText();

        $this->_doc->packDetails('detaildata', $this->_itemlist);

        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->amount = $this->docform->total->getText();
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
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }
                $this->_doc->updateStatus(Document::STATE_EXECUTED);
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }


            if ($file['size'] > 0) {
                H::addFile($file, $this->_doc->document_id, 'Скан', \App\Entity\Message::TYPE_DOC);
                $id = H::addFile($file, $this->_doc->document_id, 'Скан', \App\Entity\Message::TYPE_DOC);
                $imagedata = getimagesize($file["tmp_name"]);
                if ($imagedata[0] > 0) {
                    $this->_doc->headerdata["scan"] = $id;
                    $this->_doc->save();
                }
            }

            $conn->CommitTrans();
            App::RedirectBack();
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

    private function calcTotal() {

        $total = 0;

        foreach ($this->_itemlist as $item) {
            $item->amount = $item->price * $item->quantity;
            $total = $total + $item->amount;
        }
        $this->docform->total->setText(H::fa($total));
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


        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutocompleteItem($sender) {

        $text = trim($sender->getText());
        return Item::findArrayAC($text);
    }

    public function addcodeOnClick($sender) {
        $code = trim($this->docform->barcode->getText());
        $this->docform->barcode->setText('');
         $code0 = $code;
       $code = ltrim($code,'0');

        $code = Item::qstr($code);
        $code0 = Item::qstr($code0);

        $item = Item::getFirst("    (item_code = {$code} or bar_code = {$code} or item_code = {$code0} or bar_code = {$code0}  )");
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

        $this->docform->detail->Reload();
    }

    public function OnEmp($sender) {
        if ($sender->getValue() > 0) {
            $this->docform->examount->setVisible(true);
            $this->docform->exmf->setVisible(true);
        } else {
            $this->docform->examount->setVisible(false);
            $this->docform->exmf->setVisible(false);
        }
    }
     //добавление нового товара
    public function addnewitemOnClick($sender) {
        $this->editnewitem->setVisible(true);
        $this->editdetail->setVisible(false);

        $this->editnewitem->clean();
        $this->editnewitem->editnewbrand->setDataList(Item::getManufacturers());
    }

    public function savenewitemOnClick($sender) {
        $itemname = trim($this->editnewitem->editnewitemname->getText());
        if (strlen($itemname) == 0) {
            $this->setError("entername");
            return;
        }
        $item = new Item();
        $item->itemname = $itemname;
        $item->item_code = $this->editnewitem->editnewitemcode->getText();
        $item->msr = $this->editnewitem->editnewmsr->getText();

        if (strlen($item->item_code) > 0) {
            $code = Item::qstr($item->item_code);
            $cnt = Item::findCnt("  item_code={$code} ");
            if ($cnt > 0) {
                $this->setError('itemcode_exists');
                return;
            }

        } else {
            if (\App\System::getOption("common", "autoarticle") == 1) {

                $item->item_code = Item::getNextArticle();
            }
        }


        $item->manufacturer = $this->editnewitem->editnewbrand->getText();
        $item->cat_id = $this->editnewitem->editnewcat->getValue();
        $item->save();
        $this->editdetail->edititem->setText($item->itemname);
        $this->editdetail->edititem->setKey($item->item_id);

        $this->editnewitem->setVisible(false);
        $this->editdetail->setVisible(true);
    }

    public function cancelnewitemOnClick($sender) {
        $this->editnewitem->setVisible(false);
        $this->editdetail->setVisible(true);
    }
}

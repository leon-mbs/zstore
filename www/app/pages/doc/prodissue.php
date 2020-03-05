<?php

namespace App\Pages\Doc;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\AutocompleteTextInput;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\SubmitLink;
use \App\Entity\Doc\Document;
use \App\Entity\Item;
use \App\Entity\Stock;
use \App\Entity\Store;
use \App\Helper as H;
use \App\Application as App;

/**
 * Страница  ввода   списание  на производство
 */
class ProdIssue extends \App\Pages\Base {

    public $_itemlist = array();
    private $_doc;
    private $_basedocid = 0;

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()))->onChange($this, 'OnChangeStore');
        $this->docform->add(new DropDownChoice('parea', \App\Entity\Prodarea::findArray("pa_name", ""), 0));
        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new DropDownChoice('pricetype', Item::getPriceTypeList()));

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');

        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->docform->add(new Label('total'));

        $this->docform->add(new TextInput('barcode'));
        $this->docform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');

        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editserial'));

        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');
        $this->editdetail->edittovar->onChange($this, 'OnChangeItem', true);

        $this->editdetail->add(new Label('qtystock'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->document_date->setDate($this->_doc->document_date);

            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->parea->setValue($this->_doc->headerdata['parea']);

            $this->docform->notes->setText($this->_doc->notes);

            foreach ($this->_doc->unpackDetails('detaildata') as $item) {

                $this->_itemlist[$item->item_id] = $item;
            }
        } else {
            $this->_doc = Document::create('ProdIssue');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'Task') {

                        $this->docform->notes->setText('Материалы  для наряда ' . $basedoc->document_number);
                        $this->docform->parea->setValue($basedoc->headerdata['parea']);
                    }
                    if ($basedoc->meta_name == 'ProdIssue') {
                        $this->docform->store->setValue($basedoc->headerdata['store']);
                        $this->docform->parea->setValue($basedoc->headerdata['parea']);
                        foreach ($basedoc->unpackDetails('detaildata') as $item) {

                            $this->_itemlist[$item->item_id] = $item;
                        }                        
                    }
                }
            }
        }
        $this->calcTotal();
        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc))
            return;
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('tovar', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));

        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('price', H::fa($item->price)));
        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? date('Y-m-d', $item->sdate) : ''));

        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $tovar = $sender->owner->getDataItem();


        $this->_itemlist = array_diff_key($this->_itemlist, array($tovar->item_id => $this->_itemlist[$tovar->item_id]));
        $this->calcTotal();
        $this->docform->detail->Reload();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("0");
        $this->docform->setVisible(false);
        $this->_rowid = 0;
    }

    public function editOnClick($sender) {
        $stock = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($stock->quantity);
        $this->editdetail->editprice->setText($stock->price);
        $this->editdetail->editserial->setText($item->serial);


        $this->editdetail->edittovar->setKey($stock->stock_id);
        $this->editdetail->edittovar->setText($stock->itemname);

        $st = Stock::load($stock->stock_id);  //для актуального 

        $this->editdetail->qtystock->setText(H::fqty($st->qty));
        $this->_rowid = $stock->stock_id;
    }

    public function saverowOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $id = $this->editdetail->edittovar->getKey();
        if ($id == 0) {
            $this->setError("Не выбран товар");
            return;
        }
        $store_id = $this->docform->store->getValue();

        $item = Item::load($id);
        $item->quantity = $this->editdetail->editquantity->getText();
        $qstock = $this->editdetail->qtystock->getText();
        if ($item->quantity > $qstock) {
            $this->setWarn('Недостаточное  количество на  складе');
        }

        $item->price = $this->editdetail->editprice->getText();

        if (strlen($item->snumber) == 0 && $item->useserial == 1 && $this->_tvars["usesnumber"] == true) {
            $this->setError("Товар требует ввода партии производителя");
            return;
        }

        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {
            $slist = $item->getSerials($store_id);

            if (in_array($item->snumber, $slist) == false) {
                $this->setWarn('Неверный номер серии');
            }
        }

        $this->_itemlist[$item->item_id] = $item;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();
        $this->calcTotal();
        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editserial->setText("");
        $this->editdetail->editprice->setText("");
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->notes = $this->docform->notes->getText();
        if ($this->checkForm() == false) {
            return;
        }

        $this->calcTotal();



        $this->_doc->headerdata['parea'] = $this->docform->parea->getValue();
        $this->_doc->headerdata['pareaname'] = $this->docform->parea->getValueName();
        $this->_doc->headerdata['store'] = $this->docform->store->getValue();

        $this->_doc->packDetails('detaildata', $this->_itemlist);


        $this->_doc->amount = $this->docform->total->getText();

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
     * Расчет  итого
     *
     */
    private function calcTotal() {

        $total = 0;

        foreach ($this->_itemlist as $item) {
            $item->amount = $item->price * $item->quantity;

            $total = $total + $item->amount;
        }
        $this->docform->total->setText(H::fa($total));
    }

    public function addcodeOnClick($sender) {
        $code = trim($this->docform->barcode->getText());
        $this->docform->barcode->setText('');
        if ($code == '')
            return;

        $store_id = $this->docform->store->getValue();
        if ($store_id == 0) {
            $this->setError('Не указан склад');
            return;
        }

        $code_ = Item::qstr($code);
        $item = Item::getFirst(" item_id in(select item_id from store_stock where store_id={$store_id}) and  (item_code = {$code_} or bar_code = {$code_})");



        if ($item == null) {
            $this->setError("Товар с  кодом '{$code}' не  найден");
            return;
        }





        $store_id = $this->docform->store->getValue();

        $qty = $item->getQuantity($store);
        if ($qty <= 0) {
            $this->setError("Товара {$item->itemname} нет на складе");
        }


        if ($this->_itemlist[$item->item_id] instanceof Item) {
            $this->_itemlist[$item->item_id]->quantity += 1;
        } else {


            $price = $item->getPrice($this->docform->pricetype->getValue(), $store_id);
            $item->price = $price;
            $item->quantity = 1;

            if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {

                $serial = '';
                $slist = $item->getSerials($store_id);
                if (count($slist) == 1) {
                    $serial = array_pop($slist);
                }



                if (strlen($serial) == 0) {
                    $this->setWarn('Нужно ввести  номер партии производителя');
                    $this->editdetail->setVisible(true);
                    $this->docform->setVisible(false);


                    $this->editdetail->edittovar->setKey($item->item_id);
                    $this->editdetail->edittovar->setText($item->itemname);
                    $this->editdetail->editserial->setText('');
                    $this->editdetail->editquantity->setText('1');
                    $this->editdetail->editprice->setText($item->price);



                    return;
                } else {
                    $item->snumber = $serial;
                }
            }
            $this->_itemlist[$item->item_id] = $item;
        }
        $this->docform->detail->Reload();
        $this->calcTotal();
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
        if (strlen($this->_doc->document_number) == 0) {
            $this->setError('Введите номер документа');
        }
        if (false == $this->_doc->checkUniqueNumber()) {
            $this->docform->document_number->setText($this->_doc->nextNumber());
            $this->setError('Не уникальный номер документа. Сгенерирован новый номер');
        }
        if (count($this->_itemlist) == 0) {
            $this->setError("Не веден ни один  товар");
        }
        if (($this->docform->store->getValue() > 0 ) == false) {
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

    public function OnChangeItem($sender) {


        $id = $sender->getKey();
        $item = Item::load($id);
        $store_id = $this->docform->store->getValue();

        $price = $item->getPrice($this->docform->pricetype->getValue(), $store_id);
        $qty = $item->getQuantity($store_id);

        $this->editdetail->qtystock->setText(H::fqty($qty));
        $this->editdetail->editprice->setText($price);
        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {

            $serial = '';
            $slist = $item->getSerials($store_id);
            if (count($slist) == 1) {
                $serial = array_pop($slist);
            }
            $this->editdetail->editserial->setText($serial);
        }


        $this->updateAjax(array('qtystock', 'editprice', 'editserial'));
    }

    public function OnAutoItem($sender) {
        //$store_id = $this->docform->store->getValue();
        $text = trim($sender->getText());
        return Item::findArrayAC($text);
    }

}

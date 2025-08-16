<?php

//todofirst

namespace App\Pages\Doc;

use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\File;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Helper as H;
use App\Application as App;

/**
 * Страница  ввода входящей налоговой  накладной
 */
class TaxInvoiceIncome extends \App\Pages\Base
{

    public $_tovarlist = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid = 0;

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->add(new CheckBox('ernn'));
        $this->docform->add(new AutocompleteTextInput('contract'));

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new File('import'));
        $this->docform->add(new SubmitButton('importdoc'))->onClick($this, 'importdocOnClick');

        $this->docform->add(new Label('totalnds'));
        $this->docform->add(new Label('total'));
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');

        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editpricends'));


        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');

        if ($docid > 0) {    //загружаем   содержимок  документа на страницу
            $this->_doc = Document::load($docid);
            $this->docform->document_number->setText($this->_doc->document_number);

            //      $this->docform->nds->setText($this->_doc->headerdata['nds'] / 100);
            $this->docform->document_date->setDate($this->_doc->document_date);


            $this->docform->contract->setText($this->_doc->headerdata['contractnumber']);

            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);


            foreach ($this->_doc->detaildata as $item) {
                $item = new Item($item);
                $this->_tovarlist[$item->item_id] = $item;
            }
            $this->docform->import->setVisible(false);
        } else {
            $this->_doc = Document::create('TaxInvoiceIncome');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;

                    // Создатся  на  основании  приходной  накладной
                    if ($basedoc->meta_name == 'GoodsReceipt') {
                        //  $this->docform->nds->setText($basedoc->headerdata['nds'] / 100);
                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);


                        //$this->docform->contract->setText($basedoc->headerdata['contractnumber']);

                        foreach ($basedoc->detaildata as $item) {
                            $item = new Item($item);
                            $this->_tovarlist[$item->item_id] = $item;
                        }
                    }
                    // Создать  на  основании  счета  входящего
                    if ($basedoc->meta_name == 'PurchaseInvoice') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);


                        //$this->docform->contract->setText($basedoc->headerdata['contractnumber']);

                        foreach ($basedoc->detaildata as $item) {
                            $item = new Item($item);
                            $this->_tovarlist[$item->item_id] = $item;
                        }
                    }
                    // Создать  на  основании  Акта  выполненых услуг
                    if ($basedoc->meta_name == 'ServiceIncome') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);


                        $this->docform->contract->setText($basedoc->headerdata['contractnumber']);

                        foreach ($basedoc->detaildata as $item) {
                            $item = new Item($item);
                            $this->_tovarlist[$item->item_id] = $item;
                        }
                    }
                }
            }
        }
        $this->calcTotal();
        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_tovarlist')), $this, 'detailOnRow'))->Reload();
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('tovar', $item->itemname));
        $row->add(new Label('measure', $item->measure_name));
        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('price', H::famt($item->price)));
        $row->add(new Label('pricends', H::famt($item->pricends)));
        $row->add(new Label('amount', H::famt($item->amount)));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->editdetail->edittovar->setKey($item->item_id);
        $this->editdetail->edittovar->setText($item->itemname);


        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editprice->setText(H::famt($item->price));
        $this->editdetail->editpricends->setText(H::famt($item->pricends));
        $this->_rowid = $item->item_id;
    }

    public function deleteOnClick($sender) {
        $tovar = $sender->owner->getDataItem();
        // unset($this->_tovarlist[$tovar->tovar_id]);

        $this->_tovarlist = array_diff_key($this->_tovarlist, array($tovar->item_id => $this->_tovarlist[$tovar->item_id]));
        $this->calcTotal();
        $this->docform->detail->Reload();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->_rowid = 0;
    }

    public function saverowOnClick($sender) {
        $id = $this->editdetail->edittovar->getKey();
        if ($id == 0) {
            $this->setError("Не выбрана позиция");
            return;
        }
        $item = Item::load($id);
        $item->quantity = $this->editdetail->editquantity->getText();
        // $stock->partion = $stock->price;
        $item->price = $this->editdetail->editprice->getText();
        $item->pricends = $this->editdetail->editpricends->getText();

        unset($this->_tovarlist[$this->_rowid]);
        $this->_tovarlist[$item->item_id] = $item;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->calcTotal();
        $this->docform->detail->Reload();

        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editpricends->setText("");
        $this->editdetail->editprice->setText("");
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function savedocOnClick($sender) {
        if ($this->checkForm() == false) {
            return;
        }



        $this->_doc->headerdata = array(
            'contractnumber' => $this->docform->contract->getText(),
            'ernn' => $this->docform->ernn->isChecked(),
            'totalnds' => $this->docform->totalnds->getText(),
            'total' => $this->docform->total->getText()
        );
        $this->_doc->detaildata = array();
        foreach ($this->_tovarlist as $tovar) {
            $this->_doc->detaildata[] = $tovar->getData();
        }

        $this->_doc->amount = $this->docform->total->getText();
        $this->_doc->customer_id = $this->docform->customer->getKey();
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            $this->_doc->save();
            if ($sender->id == 'execdoc') {
                $this->_doc->updateStatus(Document::STATE_EXECUTED);
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }
            if ($this->_basedocid > 0) {
                $this->_doc->AddConnectedDoc($this->_basedocid);
                $this->_basedocid = 0;
            }
            $conn->CommitTrans();
            App::RedirectBack();
        } catch (\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setError("Ошибка записи. Детализация в логе ");

            $logger->error($ee);
            return;
        }
    }

    /**
     * Расчет  итого
     *
     */
    private function calcTotal() {
        $total = 0;
        $totalnds = 0;
        foreach ($this->_tovarlist as $item) {
            $item->amount = $item->price * $item->quantity;
            if (H::usends()) {
                $item->amount = $item->pricends * $item->quantity;
            }
            $total = $total + $item->amount;
            $totalnds = $totalnds + ($item->pricends - $item->price) * $item->quantity;
        }
        $this->docform->total->setText(H::famt($total));
        $this->docform->totalnds->setText(H::famt($totalnds));
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
        if ($this->docform->customer->getKey() == 0) {
            $this->setError("Не выбран  поставщик");
        }
        if (count($this->_tovarlist) == 0) {
            $this->setError("Не введены товары");
        }
        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function importdocOnClick($sender) {

        $file = $this->docform->import->getFile();
        $data = file_get_contents($file['tmp_name']);
        $doc = \App\Entity\Doc\TaxInvoiceIncome::importGNAU($data);

        if ($doc instanceof \App\Entity\Doc\TaxInvoiceIncome) {
            $this->_doc = $doc;
        } else {
            // иначе строка  с ошибкой
            $this->setError($doc);
        }

        $this->docform->document_number->setText($this->_doc->document_number);

        //      $this->docform->nds->setText($this->_doc->headerdata['nds'] / 100);
        $this->docform->document_date->setDate($this->_doc->document_date);

        $this->docform->based->setText($this->_doc->headerdata['based']);
        $this->docform->customer->setValue($this->_doc->headerdata['customer']);

        foreach ($this->_doc->detaildata as $item) {
            //$item = new Item($item);
            $this->_tovarlist[$item->item_id] = $item;
        }

        $this->docform->detail->Reload();
    }

    public function OnAutoItem($sender) {

        $text = Item::qstr('%' . $sender->getText() . '%');
        return Item::findArray('itemname', "disabled <> 1 and (itemname like {$text} or item_code like {$text})");
    }

    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "Customer_name like " . $text);
    }

}

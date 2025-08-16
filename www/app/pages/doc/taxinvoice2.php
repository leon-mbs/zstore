<?php

//todofirst

namespace ZippyERP\ERP\Pages\Doc;

use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use ZippyERP\ERP\Entity\Customer;
use ZippyERP\ERP\Entity\Doc\Document;
use ZippyERP\ERP\Entity\Item;
use ZippyERP\ERP\Helper as H;
use Zippy\WebApplication as App;
use ZippyERP\System\System;

/**
 * Страница  ввода приложения 2 к  налоговой  накладной
 */
class TaxInvoice2 extends \ZippyERP\ERP\Pages\Base
{

    public $_tovarlist = array();
    private $_doc;
    private $_rowid = 0;
    private $_basedocid = 0;

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());

        $this->docform->add(new DropDownChoice('customer', Customer::findArray('customer_name', " cust_type=" . Customer::TYPE_FIRM, 'customer_name')));

        $this->docform->add(new AutocompleteTextInput('contract'))->onText($this, "OnAutoContract");

        $this->docform->add(new TextInput('author'));
        $this->docform->add(new TextInput('paytype'));
        $this->docform->add(new CheckBox('ernn'));

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->docform->add(new Label('totalnds'));
        $this->docform->add(new Label('total'));
        $this->add(new Form('editdetail'))->setVisible(false);

        $this->editdetail->add(new DropDownChoice('edittovar', Item::findArray('itemname', "disabled <> 1 ", 'itemname')));
        $this->editdetail->edittovar->onChange($this, 'OnChangeItem');
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editpricends'));


        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');

        if ($docid > 0) {    //загружаем   содержимок  документа настраницу
            $this->_doc = Document::load($docid);
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->totalnds->setText(H::famt($this->_doc->headerdata['totalnds']));
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->ernn->setChecked($this->_doc->headerdata['ernn']);
            $this->docform->paytype->setText($this->_doc->headerdata['paytype']);
            $this->docform->author->setText($this->_doc->headerdata['author']);
            $this->docform->contract->setKey($this->_doc->headerdata['contract']);
            $this->docform->contract->setText($this->_doc->headerdata['contractnumber']);

            $this->docform->customer->setValue($this->_doc->headerdata['customer']);


            //$basedoc = Document::load($this->_doc->headerdata['based']);

            foreach ($this->_doc->detaildata as $item) {
                $item = new Item($item);
                $this->_tovarlist[$item->item_id] = $item;
            }
        } else {
            $this->_doc = Document::create('TaxInvoice2');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            $user = System::getUser();
            $employee = \ZippyERP\ERP\Entity\Employee::find("login='{$user->userlogin}'");
            if ($employee instanceof \ZippyERP\ERP\Entity\Employee) {
                $this->docform->author->setText($employee->fullname);
            }
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {

                    $this->_basedocid = $basedocid;

                    if ($basedoc->meta_name == 'GoodsIssue') {

                        $this->docform->customer->setValue($basedoc->headerdata['customer']);

                        $this->docform->contract->setKey($basedoc->headerdata['contract']);
                        $this->docform->contract->setText($basedoc->headerdata['contractnumber']);

                        foreach ($basedoc->detaildata as $item) {
                            $item = new Item($item);
                            $this->_tovarlist[$item->item_id] = $item;
                        }
                    }
                    if ($basedoc->meta_name == 'Invoice') {

                        $this->docform->customer->setValue($basedoc->headerdata['customer']);

                        $this->docform->contract->setKey($basedoc->headerdata['contract']);
                        $this->docform->contract->setText($basedoc->headerdata['contractnumber']);

                        foreach ($basedoc->detaildata as $item) {
                            $item = new Item($item);
                            $this->_tovarlist[$item->item_id] = $item;
                        }
                    }
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_tovarlist')), $this, 'detailOnRow'))->Reload();
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('tovar', $item->itemname));
        $row->add(new Label('measure', $item->measure_name));
        $row->add(new Label('quantity', $item->quantity / 1000));
        $row->add(new Label('price', H::famt($item->price)));
        $row->add(new Label('pricends', H::famt($item->pricends)));
        $row->add(new Label('amount', H::famt(($item->quantity / 1000) * $item->pricends)));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
        $tovar = $sender->owner->getDataItem();
        // unset($this->_tovarlist[$tovar->tovar_id]);

        $this->_tovarlist = array_diff_key($this->_tovarlist, array($tovar->item_id => $this->_tovarlist[$tovar->item_id]));
        $this->docform->detail->Reload();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->_rowid = 0;
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity / 1000);
        $this->editdetail->editprice->setText(H::famt($item->price));
        $this->editdetail->editpricends->setText(H::famt($item->pricends));


        $this->editdetail->edittovar->setValue($item->item_id);


        $this->_rowid = $item->item_id;
    }

    public function saverowOnClick($sender) {
        $id = $this->editdetail->edittovar->getValue();
        if ($id == 0) {
            $this->setError("Не вибраний товар");
            return;
        }
        $item = Item::load($id);
        $item->quantity = 1000 * $this->editdetail->editquantity->getText();
        // $stock->partion = $stock->price;
        $item->price = $this->editdetail->editprice->getText() * 100;
        $item->pricends = $this->editdetail->editpricends->getText() * 100;

        unset($this->_tovarlist[$this->_rowid]);
        $this->_tovarlist[$item->item_id] = $item;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();

        //очищаем  форму
        $this->editdetail->edittovar->setValue(0);
        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        $this->editdetail->editpricends->setText("");
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function savedocOnClick($sender) {
        if ($this->checkForm() == false) {
            return;
        }

        $this->calcTotal();

        $this->_doc->headerdata = array(
            'customer' => $this->docform->customer->getValue(),
            'customername' => $this->docform->customer->getValueName(),
            'contract' => $this->docform->contract->getKey(),
            'contractnumber' => $this->docform->contract->getText(),
            'total' => $this->docform->total->getText() * 100,
            'ernn' => $this->docform->ernn->isChecked(),
            'author' => $this->docform->author->getText(),
            'paytype' => $this->docform->paytype->getText(),
            'totalnds' => $this->docform->totalnds->getText() * 100
        );
        $this->_doc->detaildata = array();
        foreach ($this->_tovarlist as $tovar) {
            $this->_doc->detaildata[] = $tovar->getData();
        }

        $this->_doc->amount = 100 * $this->docform->total->getText();
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
            if ($this->docform->contract->getKey() > 0) {
                $this->_doc->AddConnectedDoc($this->docform->contract->getKey());
            }
            $conn->CommitTrans();
            App::RedirectBack();
        } catch (\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setError("Помилка запису документу. Деталізація в лог файлі  ");

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
        foreach ($this->_tovarlist as $tovar) {
            $total = $total + $tovar->price * ($tovar->quantity / 1000);
        }

        $nds = H::nds() * $total;
        $this->docform->totalnds->setText(H::famt($nds));
        $this->docform->total->setText(H::famt($total + $nds));
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {

        if (count($this->_tovarlist) == 0) {
            $this->setError("Не введений ні один  товар");
        }
        return !$this->isError();
    }

    public function beforeRender() {
        parent::beforeRender();

        $this->calcTotal();

        App::$app->getResponse()->addJavaScript("var _nds = " . H::nds() . ";var nds_ = " . H::nds(true) . ";");
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnItem(DropDownChoice $sender) {
        $id = $sender->getValue();
        $item = Item::load($id);
        $this->editdetail->editprice->setText(H::famt($item->priceopt));

        $this->editdetail->editpricends->setText(H::famt($item->priceopt + $item->priceopt * H::nds()));

        $this->updateAjax(array('editprice', 'editpricends'));
    }

    public function OnAutoContract($sender) {
        $text = $sender->getValue();
        return Document::findArray('document_number', "document_number like '%{$text}%' and ( meta_name='Contract' or meta_name='SupplierOrder' )");
    }

    public function OnChangeItem($sender) {

        $item = Item::load($this->editdetail->edittovar->getValue());
        $this->editdetail->editprice->setText(H::famt($item->priceopt));

        $nds = H::nds();

        $this->editdetail->editpricends->setText(H::famt($item->priceopt + $item->priceopt * $nds));

        $this->updateAjax(array('editprice', 'editpricends', 'qtystock'));
    }

}

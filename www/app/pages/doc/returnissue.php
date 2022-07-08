<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\MoneyFund;
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
 *  возвратная накладная
 */
class ReturnIssue extends \App\Pages\Base
{

    public  $_tovarlist = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid     = 0;

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();
        if ($docid == 0 && $basedocid == 0) {

            $this->setWarn('return_basedon_goodsissue');
        }
        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()))->onChange($this, 'OnChangeStore');

        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');

        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), H::getDefMF()));

        $this->docform->add(new DropDownChoice('pos', \App\Entity\Pos::findArray('pos_name', "details like '%<usefisc>1</usefisc>%' "), 0));

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->docform->add(new Label('total'));
        $this->docform->add(new TextInput('editpayed', "0"));
        $this->docform->add(new SubmitButton('bpayed'))->onClick($this, 'onPayed');
        $this->docform->add(new Label('payed', 0));

        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));

        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->document_date->setDate($this->_doc->document_date);

            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);
            if ($this->_doc->payed == 0 && $this->_doc->headerdata['payed'] > 0) {
                $this->_doc->payed = $this->_doc->headerdata['payed'];
            }
            $this->docform->editpayed->setText(H::fa($this->_doc->payed));
            $this->docform->payed->setText(H::fa($this->_doc->payed));

            $this->docform->total->setText(H::fa($this->_doc->amount));

            $this->_tovarlist = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('ReturnIssue');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;

                    $d = $basedoc->getChildren('ReturnIssue');

                    if (count($d) > 0) {

                        $this->setError('return_exists');
                        App::Redirect("\\App\\Pages\\Register\\DocList");
                        return;
                    }


                    if ($basedoc->meta_name == 'GoodsIssue') {
                        $this->docform->store->setValue($basedoc->headerdata['store']);
                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $itemlist = $basedoc->unpackDetails('detaildata');

                        $this->_itemlist = array();
                        foreach ($itemlist as $item) {
                            $this->_tovarlist[$item->item_id] = $item;
                        }
                    }
                    if ($basedoc->meta_name == 'TTN') {
                        $this->docform->store->setValue($basedoc->headerdata['store']);
                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $itemlist = $basedoc->unpackDetails('detaildata');

                        $this->_itemlist = array();
                        foreach ($itemlist as $item) {
                            $this->_tovarlist[$item->item_id] = $item;
                        }
                    }
                    if ($basedoc->meta_name == 'POSCheck') {
                        $this->docform->store->setValue($basedoc->headerdata['store']);
                        $this->docform->pos->setValue($basedoc->headerdata['pos']);
                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $itemlist = $basedoc->unpackDetails('detaildata');

                        $this->_itemlist = array();
                        foreach ($itemlist as $item) {
                            $this->_tovarlist[$item->item_id] = $item;
                        }
                    }
                }
                $this->calcTotal();
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_tovarlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('tovar', $item->itemname));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->snumber > 0 ? ($item->sdate > 0 ? H::fd($item->sdate) : '') : ''));

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
        $tovar = $sender->owner->getDataItem();
        // unset($this->_tovarlist[$tovar->tovar_id]);

        $this->_tovarlist = array_diff_key($this->_tovarlist, array($tovar->item_id => $this->_tovarlist[$tovar->item_id]));
        $this->docform->detail->Reload();
        $this->calcTotal();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("0");
        $this->docform->setVisible(false);
        $this->_rowid = 0;
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editprice->setText($item->price);

        $this->editdetail->edittovar->setKey($item->item_id);
        $this->editdetail->edittovar->setText($item->itemname);

        $this->_rowid = $item->item_id;
    }

    public function saverowOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $id = $this->editdetail->edittovar->getKey();
        if ($id == 0) {
            $this->setError("noselitem");
            return;
        }

        $item = Item::load($id);
        $item->quantity = $this->editdetail->editquantity->getText();

        $item->price = $this->editdetail->editprice->getText();

        unset($this->_tovarlist[$this->_rowid]);
        $this->_tovarlist[$item->item_id] = $item;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();

        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        $this->calcTotal();
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
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->_doc->customer_id > 0) {
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        }
        if ($this->checkForm() == false) {
            return;
        }


        $firm = H::getFirmData($this->_doc->firm_id, $this->branch_id);
        $this->_doc->headerdata["firm_name"] = $firm['firm_name'];

        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();

        $this->_doc->packDetails('detaildata', $this->_tovarlist);

        $this->_doc->amount = $this->docform->total->getText();
        $this->_doc->payamount = $this->docform->total->getText();

      $this->_doc->payed = $this->docform->payed->getText();
        $this->_doc->headerdata['payed'] = $this->docform->payed->getText();

        $isEdited = $this->_doc->document_id > 0;

        $pos_id = $this->docform->pos->getValue();

        if ($pos_id > 0 && $sender->id == 'execdoc') {
            $pos = \App\Entity\Pos::load($pos_id);
            if ($pos->usefisc == 1 && $this->_tvars['ppo'] == true) {
                $this->_doc->headerdata["fiscalnumberpos"]  =  $pos->fiscalnumber;
 
                if ($this->_basedocid > 0) {
                    $basedoc = Document::load($this->_basedocid);
                    $this->_doc->headerdata["docnumberback"] = $basedoc->headerdata["fiscalnumber"];
                }

                if (strlen($this->_doc->headerdata["docnumberback"]) == 0) {
                    $this->setError("ppo_returndoc");
                    return;
                }

                $this->_doc->headerdata["pos"] = $pos->pos_id;

                $ret = \App\Modules\PPO\PPOHelper::checkback($this->_doc);
                if ($ret['success'] == false && $ret['doclocnumber'] > 0) {
                    //повторяем для  нового номера
                    $pos->fiscdocnumber = $ret['doclocnumber'];
                    $pos->save();
                    $ret = \App\Modules\PPO\PPOHelper::checkback($this->_doc);
                }
                if ($ret['success'] == false) {
                    $this->setErrorTopPage($ret['data']);
                    return;
                } else {

                    if ($ret['docnumber'] > 0) {
                        $pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                        $pos->save();
                        $this->_doc->headerdata["fiscalnumber"] = $ret['docnumber'];
                    } else {
                        $this->setError("ppo_noretnumber");
                        return;
                    }
                }
            }
        }


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
            if ($this->_doc->payamount > $this->_doc->payed) {
                $this->_doc->updateStatus(Document::STATE_WP);
            }

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

        foreach ($this->_tovarlist as $item) {
            $item->amount = $item->price * $item->quantity;

            $total = $total + $item->amount;
        }
        $this->docform->total->setText(H::fa($total));
        $this->docform->payed->setText(H::fa($total));
        $this->docform->editpayed->setText(H::fa($total));
    }

    public function onPayed($sender) {
        $this->docform->payed->setText(H::fa($this->docform->editpayed->getText()));
        $payed = $this->docform->payed->getText();
        $total = $this->docform->total->getText();
        if ($payed > $total) {
            $this->setWarn('inserted_extrasum');
        } else {
            $this->goAnkor("tankor");
        }
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
        if (strlen($this->_doc->document_number) == 0) {
            $this->setError('enterdocnumber');
        }
        if (false == $this->_doc->checkUniqueNumber()) {
            $next = $this->_doc->nextNumber();
            $this->docform->document_number->setText($next);
            $this->_doc->document_number = $next;
            if (strlen($next) == 0) {
                $this->setError('docnumbercancreated');
            }
        }
        if (count($this->_tovarlist) == 0) {
            $this->setError("noenteritem");
        }
        if (($this->docform->store->getValue() > 0) == false) {
            $this->setError("noselstore");
        }

        $c = $this->docform->customer->getKey();
        if ($this->_doc->amount > 0 && $this->_doc->payamount > $this->_doc->payed && $c == 0) {
            $this->setError("mustsel_cust");
        }


        if ($this->docform->payment->getValue() == 0 && $this->_doc->payed > 0) {
            $this->setError("noselmfp");
        }


        //проверка  что не  поменялась  цена
        $base = Document::load($this->_basedocid);
        if ($base instanceof Document) {
            $base = $base->cast();
            $bt = $base->unpackDetails('detaildata');

            if (is_array($bt)) {

                foreach ($this->_tovarlist as $t) {
                    $ok = false;
                    foreach ($bt as $b) {
                        if ($b->item_id == $t->item_id && $b->price == $t->price) {
                            $ok = true;
                            break;
                        }
                    }
                    if ($ok == false) {
                        $this->setError("thesameitempriceret");
                        break;
                    }

                }
            }
        }


        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnChangeStore($sender) {
        //очистка  списка  товаров
        $this->_tovarlist = array();
        $this->docform->detail->Reload();
        $this->calcTotal();
    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 1);
    }

    public function OnAutoItem($sender) {

        $text = trim($sender->getText());
        return Item::findArrayAC($text);
    }

}

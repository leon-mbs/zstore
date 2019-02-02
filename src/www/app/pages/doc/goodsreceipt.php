<?php

namespace App\Pages\Doc;

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
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Store;
use App\Helper as H;
use App\System;
use App\Application as App;

/**
 * Страница  ввода  приходной  накладной
 */
class GoodsReceipt extends \App\Pages\Base
{

    public $_itemlist = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid = 0;
      private $_order_id = 0;
      
    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();


        $common = System::getOptions("common");


        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));
        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new CheckBox('planned'));
        $this->docform->add(new CheckBox('payed'));

        $this->docform->add(new DropDownChoice('val', array(1 => 'Гривна', 2 => 'Доллар', 3 => 'Евро', 4 => 'Рубль')))->onChange($this, "onVal", true);
        $this->docform->add(new Label('course', 'Курс 1'));
        $this->docform->val->setVisible($common['useval'] == true);
        $this->docform->course->setVisible($common['useval'] == true);


        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new TextInput('order'));

        $this->docform->add(new Label('total'));
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutoItem');
        $this->editdetail->add(new SubmitLink('addnewitem'))->onClick($this, 'addnewitemOnClick');
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');

        //добавление нового товара
        $this->add(new Form('editnewitem'))->setVisible(false);
        $this->editnewitem->add(new TextInput('editnewitemname'));
        $this->editnewitem->add(new TextInput('editnewitemcode'));
        $this->editnewitem->add(new Button('cancelnewitem'))->onClick($this, 'cancelnewitemOnClick');
        $this->editnewitem->add(new SubmitButton('savenewitem'))->onClick($this, 'savenewitemOnClick');



        if ($docid > 0) {    //загружаем   содержимок  документа настраницу
            $this->_doc = Document::load($docid);
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->planned->setChecked($this->_doc->headerdata['planned']);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->order->setText($this->_doc->headerdata['order']);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);

            $this->docform->store->setValue($this->_doc->headerdata['store']);

            foreach ($this->_doc->detaildata as $item) {
                $item = new Item($item);
                $item->old = true;
                $this->_itemlist[$item->item_id] = $item;
            }
        } else {
            $this->_doc = Document::create('GoodsReceipt');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                   if ($basedoc->meta_name == 'OrderCust') {
                        $this->_order_id = $basedocid;
                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);
                       
                        $this->_orderid = $basedocid;
                        $this->docform->order->setText($basedoc->document_number);
                  
                        $notfound = array();
                        $order = $basedoc->cast();

                        $ttn = false;
                        //проверяем  что уже есть приход
                        $list = $order->ConnectedDocList();
                        foreach ($list as $d) {
                            if ($d->meta_name == 'GoodsReceipt') {
                                $ttn = true;
                            }
                        }

                        if ($ttn) {
                            $this->setWarn('У заказа  уже  есть приход');
                        }

                        foreach ($order->detaildata as $_item) {
                             $item = new Item($_item);
                             $this->_itemlist[$item->item_id] = $item;
                        }
                        $this->calcTotal(); 
                    }
                     
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc))
            return;

        $this->docform->payed->setChecked($this->_doc->datatag == $this->_doc->amount);
    }

    public function onVal($sender) {
        $val = $sender->getValue();
        $common = System::getOptions("common");

        if ($val == 1)
            $this->docform->course->setText('Курс 1');
        if ($val == 2)
            $this->docform->course->setText('Курс  ' . $common['cdoll']);
        if ($val == 3)
            $this->docform->course->setText('Курс  ' . $common['ceuro']);
        if ($val == 4)
            $this->docform->course->setText('Курс  ' . $common['crub']);

        $this->updateAjax(array('course'));
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();


        $row->add(new Label('item', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('price', $item->price));
        $row->add(new Label('msr', $item->msr));

        $row->add(new Label('amount', round($item->quantity * $item->price)));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->edit->setVisible($item->old != true);

        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editprice->setText($item->price);


        $this->editdetail->edititem->setKey($item->item_id);
        $this->editdetail->edititem->setText($item->itemname);


        $this->_rowid = $item->item_id;
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $item = $sender->owner->getDataItem();
        // unset($this->_itemlist[$item->item_id]);

        $this->_itemlist = array_diff_key($this->_itemlist, array($item->item_id => $this->_itemlist[$item->item_id]));
        $this->calcTotal();
        $this->docform->detail->Reload();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->_rowid = 0;
    }

    public function saverowOnClick($sender) {


        $id = $this->editdetail->edititem->getKey();
        $name = trim($this->editdetail->edititem->getText());
        if ($id == 0 && strlen($name) < 2) {
            $this->setError("Не выбран товар");
            return;
        }
        if ($id == 0) {
            $item = new Item();  // создаем новый
            $item->itemname = $name;
            //todo наценка по дефолту
            $item->save();
            $id = $item->item_id;
        }

        $item = Item::load($id);


        $item->quantity = $this->editdetail->editquantity->getText();
        $item->price = $this->editdetail->editprice->getText();



        unset($this->_itemlist[$this->_rowid]);
        $this->_itemlist[$item->item_id] = $item;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();
        $this->calcTotal();
        //очищаем  форму
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->checkForm() == false) {
            return;
        }
        $old = $this->_doc->cast();
        $this->calcTotal();

        $common = System::getOptions("common");
        foreach ($this->_itemlist as $item) {
            if ($item->old == true)
                continue;
            if ($common['useval'] != true)
                continue;

            if ($this->docform->val->getValue() == 2) {
                $item->price = round($item->price * $common['cdoll']);
                $item->curname = 'cdoll';
                $item->currate = $common['cdoll'];
            }
            if ($this->docform->val->getValue() == 3) {
                $item->price = round($item->price * $common['ceuro']);
                $item->curname = 'ceuro';
                $item->currate = $common['ceuro'];
            }
            if ($this->docform->val->getValue() == 4) {
                $item->price = round($item->price * $common['crub']);
                $item->curname = 'crub';
                $item->currate = $common['crub'];
            }
        }


        $this->_doc->headerdata = array(
            'order' => $this->docform->order->getText(),
            'store' => $this->docform->store->getValue(),
            'planned' => $this->docform->planned->isChecked() ? 1 : 0,
            'total' => $this->docform->total->getText(),
            'order_id' => $this->_order_id
        );
        $this->_doc->detaildata = array();
        foreach ($this->_itemlist as $item) {
            $this->_doc->detaildata[] = $item->getData();
        }

        $this->_doc->amount = $this->docform->total->getText();
        $isEdited = $this->_doc->document_id > 0;



        if ($this->docform->payed->isChecked() == true && $this->_doc->datatag < $this->_doc->amount) {

            $this->_doc->addPayment(System::getUser()->user_id, $this->_doc->amount == $this->_doc->datatag);
            $this->_doc->datatag = $this->_doc->amount;
        }


        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            $this->_doc->save();
            $order = Document::load($this->_doc->headerdata['order_id']);
    
            if ($sender->id == 'execdoc') {
                if (!$isEdited)
                    $this->_doc->updateStatus(Document::STATE_NEW);

                $this->_doc->updateStatus(Document::STATE_EXECUTED);
                if ($order instanceof Document) {
                    $order->updateStatus(Document::STATE_CLOSED);
                }                
            } else {
                
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }
            
              



            if ($this->_basedocid > 0) {
                $this->_doc->AddConnectedDoc($this->_basedocid);
                $this->_basedocid = 0;
            }

            $conn->CommitTrans();
        } catch (\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setError($ee->getMessage());
            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);

            return;
        }
        App::RedirectBack();
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
        $this->docform->total->setText($total);
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
        if (strlen($this->_doc->document_number) == 0) {
            $this->setError('Введите номер документа');
        }
        if (count($this->_itemlist) == 0) {
            $this->setError("Не введен ни один  товар");
        }
        if ($this->docform->store->getValue() == 0) {
            $this->setError("Не выбран  склад");
        }
        if ($this->docform->customer->getKey() == 0) {
            $this->setError("Не выбран  поставщик");
        }

        return !$this->isError();
    }

 

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutoItem($sender) {

        $text = Item::qstr('%' . $sender->getText() . '%');
        return Item::findArray('itemname', "(itemname like {$text} or item_code like {$text}) and disabled <> 1");
    }

    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "Customer_name like " . $text);
    }

    //добавление нового товара
    public function addnewitemOnClick($sender) {
        $this->editnewitem->setVisible(true);
        $this->editdetail->setVisible(false);

        $this->editnewitem->editnewitemname->setText('');
        $this->editnewitem->editnewitemcode->setText('');
    }

    public function savenewitemOnClick($sender) {
        $itemname = trim($this->editnewitem->editnewitemname->getText());
        if (strlen($itemname) == 0) {
            $this->setError("Не введено имя");
            return;
        }
        $item = new Item();
        $item->itemname = $itemname;
        $item->item_code = $this->editnewitem->editnewitemcode->getText();
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

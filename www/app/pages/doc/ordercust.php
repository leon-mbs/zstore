<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\CustItem;
use App\Helper as H;
use App\System;
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
 * Страница  ввода  заявки  поставщику
 */
class OrderCust extends \App\Pages\Base
{

    public  $_itemlist  = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid     = 0;

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $common = System::getOptions("common");

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->add(new TextInput('notes'));
        $cp = \App\Session::getSession()->clipboard;
        $this->docform->add(new ClickLink('paste', $this, 'onPaste'))->setVisible(is_array($cp) && count($cp) > 0);

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('apprdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Label('total'));
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutoItem');
        $this->editdetail->edititem->onChange($this, 'OnChangeItem', true);
        $this->editdetail->add(new SubmitLink('addnewitem'))->onClick($this, 'addnewitemOnClick');
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editcustcode'));
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editdesc'));
        $this->editdetail->add(new Label('qtystock'));
          $this->editdetail->add(new ClickLink('openitemsel', $this, 'onOpenItemSel'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');

        $this->add(new \App\Widgets\ItemSel('wselitem', $this, 'onSelectItem'))->setVisible(false);
        
        
        //добавление нового товара
        $this->add(new Form('editnewitem'))->setVisible(false);
        $this->editnewitem->add(new TextInput('editnewitemname'));
        $this->editnewitem->add(new TextInput('editnewitemcode'));
        $this->editnewitem->add(new TextInput('editnewitembrand'));
        $this->editnewitem->add(new TextInput('editnewitemmsr'));
        $this->editnewitem->add(new Button('cancelnewitem'))->onClick($this, 'cancelnewitemOnClick');
        $this->editnewitem->add(new DropDownChoice('editnewcat', \App\Entity\Category::getList(), 0));
        $this->editnewitem->add(new SubmitButton('savenewitem'))->onClick($this, 'savenewitemOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);

            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('OrderCust');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'Order') {

                        $order = $basedoc->cast();

                        $this->docform->total->setText($order->amount);

                        $this->_itemlist = $basedoc->unpackDetails('detaildata');
                        $this->calcTotal();
                    }
                }
            }
        }
        $this->calcTotal();
        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('item', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('custcode', $item->custcode));
        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('price', H::fa($item->price)));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('desc', $item->desc));

        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));
        $row->add(new SubmitLink('edit'))->onClick($this, 'editOnClick');
        $row->edit->setVisible($item->old != true);

        $row->add(new SubmitLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editcustcode->setText($item->custcode);
        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editprice->setText($item->price);
        $this->editdetail->editdesc->setText($item->desc);

        $this->editdetail->edititem->setKey($item->item_id);
        $this->editdetail->edititem->setText($item->itemname);

        if ($item->rowid > 0) {
            ;
        }               //для совместимости
        else {
            $item->rowid = $item->item_id;
        }
        $qty = $item->getQuantity();

        $this->editdetail->qtystock->setText(H::fqty($qty));

        $this->_rowid = $item->rowid;
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $item = $sender->owner->getDataItem();
        if ($item->rowid > 0) {
            ;
        }               //для совместимости
        else {
            $item->rowid = $item->item_id;
        }

        $this->_itemlist = array_diff_key($this->_itemlist, array($item->rowid => $this->_itemlist[$item->rowid]));
        $this->docform->detail->Reload();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->editdetail->editdesc->setText("");
        $this->editdetail->qtystock->setText("");
        $this->_rowid = 0;
        $this->editdetail->editprice->setText("0");
    }

    public function saverowOnClick($sender) {


        $id = $this->editdetail->edititem->getKey();
        $name = trim($this->editdetail->edititem->getText());
        if ($id == 0) {
            $this->setError("Не обрано товар");
            return;
        }


        $item = Item::load($id);

        $item->custcode = $this->editdetail->editcustcode->getText();
        $item->quantity = $this->editdetail->editquantity->getText();
        $item->price = $this->editdetail->editprice->getText();
        if ($item->price == 0) {
            $this->setWarn("Не вказана ціна");
        }
        $item->desc = $this->editdetail->editdesc->getText();

        if ($this->_rowid > 0) {
            $item->rowid = $this->_rowid;
        } else {
            $next = count($this->_itemlist) > 0 ? max(array_keys($this->_itemlist)) : 0;
            $item->rowid = $next + 1;
        }
        $this->_itemlist[$item->rowid] = $item;

        $this->_rowid = 0;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();

        //очищаем  форму
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        $this->editdetail->editcustcode->setText("");
        $this->wselitem->setVisible(false);        
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->_doc->customer_id > 0) {
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        }
        if ($this->checkForm() == false) {
            return;
        }

        $this->calcTotal();

        $this->_doc->packDetails('detaildata', $this->_itemlist);

        $this->_doc->amount = $this->docform->total->getText();
        $this->_doc->payed = 0;
        $this->_doc->payamount = 0;
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

                $this->_doc->updateStatus(Document::STATE_INPROCESS);
            } else {
                if ($sender->id == 'apprdoc') {
                    if (!$isEdited) {
                        $this->_doc->updateStatus(Document::STATE_NEW);
                    }

                    $this->_doc->updateStatus(Document::STATE_WA);
                } else {
                    $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
                }
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
        App::Redirect("\\App\\Pages\\Register\\GRList");

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

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
        if (strlen($this->_doc->document_number) == 0) {
            $this->setError('Введіть номер документа');
        }
        if (false == $this->_doc->checkUniqueNumber()) {
            $next = $this->_doc->nextNumber();
            $this->docform->document_number->setText($next);
            $this->_doc->document_number = $next;
            if (strlen($next) == 0) {
                $this->setError('Не створено унікальный номер документа');
            }
        }
        if (count($this->_itemlist) == 0) {
            $this->setError("Не введено товар");
        }

        if ($this->docform->customer->getKey() == 0) {
            $this->setError("Не обрано постачальника");
        }

        return !$this->isError();
    }

    public function beforeRender() {
        parent::beforeRender();

        $this->calcTotal();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutoItem($sender) {

        $text = Item::qstr('%' . $sender->getText() . '%');
        return Item::findArray('itemname', "(itemname like {$text} or item_code like {$text})  and disabled <> 1");
    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 2);
    }

    //добавление нового товара
    public function addnewitemOnClick($sender) {
        $this->editnewitem->setVisible(true);
        $this->editdetail->setVisible(false);

        $this->editnewitem->editnewitemname->setText('');
        $this->editnewitem->editnewitemcode->setText('');
        $this->editnewitem->editnewitemmsr->setText('');
    }

    public function savenewitemOnClick($sender) {
        $itemname = trim($this->editnewitem->editnewitemname->getText());
        if (strlen($itemname) == 0) {
            $this->setError("Не введено назву");
            return;
        }
        $item = new Item();
        $item->itemname = $itemname;
        $item->cat_id = $this->editnewitem->editnewcat->getValue();
        $item->item_code = $this->editnewitem->editnewitemcode->getText();
        $item->manufacturer = $this->editnewitem->editnewitembrand->getText();
        $item->msr = $this->editnewitem->editnewitemmsr->getText();


        if (strlen($item->item_code) > 0) {
            $code = Item::qstr($item->item_code);
            $cnt = Item::findCnt("  item_code={$code} ");
            if ($cnt > 0) {
                $this->setError('Такий артикул вже існує');
                return;
            }

        } else {
            if (System::getOption("common", "autoarticle") == 1) {

                $item->item_code = Item::getNextArticle();
            }
        }


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

    public function onPaste($sender) {

        $cp = \App\Session::getSession()->clipboard;

        foreach ($cp as $it) {
            $item = Item::load($it->item_id);
            if ($item == null) {
                continue;
            }
            $item->quantity = 1;
            $item->price = 0;

            $this->_itemlist[$item->item_id] = $item;
        }

        $this->docform->detail->Reload();

        $this->calcTotal();
    }
   
    public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $item = Item::load($id);

        $cust = $this->docform->customer->getKey();
        $qty = $item->getQuantity();

        $this->editdetail->qtystock->setText(H::fqty($qty));
        //ищем  в товарах поставщиков
        
        $custitem = CustItem::getFirst("customer_id={$cust} and item_id=".$item->item_id,"updatedon desc")   ;
        if($custitem==null){
        
        }   else {
          $this->editdetail->editcustcode->setText($custitem->cust_code);
          $this->editdetail->editprice->setText(H::fa($custitem->price));

        
        }
        
        


        
    }
    
    public function onSelectItem($item_id, $itemname) {
        $this->editdetail->edititem->setKey($item_id);
        $this->editdetail->edititem->setText($itemname);
        $this->OnChangeItem($this->editdetail->edititem);
    }    
    public function onOpenItemSel($sender) {
        $this->wselitem->setVisible(true);
        $this->wselitem->Reload();
    }    
}

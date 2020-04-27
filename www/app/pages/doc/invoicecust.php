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
use \App\Entity\Customer;
use \App\Entity\Doc\Document;
use \App\Entity\Item;
use \App\Entity\Store;
use \App\Helper as H;
use \App\System;
use \App\Application as App;

/**
 * Страница  ввода  счета  от  поставщика
 */
class InvoiceCust extends \App\Pages\Base {

    public $_itemlist = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid = 0;

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $common = System::getOptions("common");

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->add(new TextInput('notes'));

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new DropDownChoice('payment', \App\Entity\MoneyFund::getList(  true), H::getDefMF()));


        $this->docform->add(new TextInput('editpayamount', "0"));
        $this->docform->add(new SubmitButton('bpayamount'))->onClick($this, 'onPayAmount');
        $this->docform->add(new TextInput('editpayed', "0"));
        $this->docform->add(new SubmitButton('bpayed'))->onClick($this, 'onPayed');

        $this->docform->add(new TextInput('editnds', "0"));
        $this->docform->add(new SubmitButton('bnds'))->onClick($this, 'onNds');
        $this->docform->add(new TextInput('editrate', "1"));
        $this->docform->add(new SubmitButton('brate'))->onClick($this, 'onRate');
        $this->docform->add(new TextInput('editdisc', "0"));
        $this->docform->add(new SubmitButton('bdisc'))->onClick($this, 'onDisc');
        
        $this->docform->add(new Label('nds', 0));
        $this->docform->add(new Label('rate', 1));
        $this->docform->add(new Label('disc', 0));
       
        
        $this->docform->add(new Label('payed', 0));
        $this->docform->add(new Label('payamount', 0));
        $this->docform->add(new Label('total'));
        $this->docform->add(new \Zippy\Html\Form\File('scan'));

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
        $this->editnewitem->add(new DropDownChoice('editnewcat', \App\Entity\Category::findArray("cat_name", "", "cat_name"), 0));
        $this->editnewitem->add(new SubmitButton('savenewitem'))->onClick($this, 'savenewitemOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->payamount->setText($this->_doc->payamount);
            $this->docform->editpayamount->setText($this->_doc->payamount);
            $this->docform->payed->setText($this->_doc->payed);
            $this->docform->editpayed->setText($this->_doc->payed);
            $this->docform->nds->setText($this->_doc->headerdata['nds']);
            $this->docform->editnds->setText($this->_doc->headerdata['nds']);
            $this->docform->rate->setText($this->_doc->headerdata['rate']);
            $this->docform->editrate->setText($this->_doc->headerdata['rate']);
            $this->docform->disc->setText($this->_doc->headerdata['disc']);
            $this->docform->editdisc->setText($this->_doc->headerdata['disc']);

            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);
            $this->docform->total->setText($this->_doc->amount);
  
            $this->_itemlist = $this->_doc->unpackDetails('detaildata');

 
        } else {
            $this->_doc = Document::create('InvoiceCust');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'OrderCust') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $order = $basedoc->cast();

                        $this->_itemlist = $basedoc->unpackDetails('detaildata');

                        $this->CalcTotal();
                        $this->CalcPay();
                    }
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc))
            return;
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('item', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('price', H::fa($item->price)));
        $row->add(new Label('msr', $item->msr));

        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
 

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
        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->_rowid = 0;
        $this->editdetail->editprice->setText("0");
    }

    public function saverowOnClick($sender) {

        $id = $this->editdetail->edititem->getKey();
        $name = trim($this->editdetail->edititem->getText());
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

        $tarr = array();
 
        foreach($this->_itemlist as $k=>$value){
               
           if( $this->_rowid > 0 &&  $this->_rowid == $k)  {
              $tarr[$item->item_id] = $item;    // заменяем
           }   else {
              $tarr[$k] = $value;    // старый
           }
                
        }
     
        if($this->_rowid == 0) {        // в конец
            $tarr[$item->item_id] = $item;
        }
        $this->_itemlist = $tarr;
        $this->_rowid = 0;

        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();


        $this->calcTotal();
        $this->calcPay();
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
        $this->_doc->payamount = $this->docform->payamount->getText();
        $this->_doc->payed = $this->docform->payed->getText();
     
        $this->_doc->headerdata['rate'] = $this->docform->rate->getText();
        $this->_doc->headerdata['nds'] = $this->docform->nds->getText();
        $this->_doc->headerdata['disc'] = $this->docform->disc->getText();
     
     
        if ($this->_doc->headerdata['payment'] == \App\Entity\MoneyFund::CREDIT) {
            $this->_doc->payed = 0;
        }
        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->_doc->customer_id > 0) {
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText() . ' ' . $customer->phone;
        }
        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();

        if ($this->checkForm() == false) {
            return;
        }

        $file = $this->docform->scan->getFile();
        if ($file['size'] > 10000000) {
            $this->setError("filemore10M");
            return;
        }

        $common = System::getOptions("common");
 

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


            if ($file['size'] > 0) {
                H::addFile($file, $this->_doc->document_id, 'Скан', \App\Entity\Message::TYPE_DOC);
            }

            $conn->CommitTrans();


            if ($isEdited)
                App::RedirectBack();
            else
                App::Redirect("\\App\\Pages\\Register\\GRList");
        } catch (\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setError($ee->getMessage());
            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);

            return;
        }
        App::RedirectBack();
    }

    public function onPayAmount($sender) {
        $this->docform->payamount->setText(H::fa($this->docform->editpayamount->getText()));
     
    }

    public function onPayed($sender) {
        $this->docform->payed->setText(H::fa($this->docform->editpayed->getText()));
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

    private function CalcPay() {
         $total = $this->docform->total->getText();
         $rate  = $this->docform->rate->getText();
  
        $nds   = $this->docform->nds->getText();
        $disc  = $this->docform->disc->getText();
        $total = $total + $nds - $disc;  
        $rate  = $this->docform->rate->getText();
        if($rate !=1 && $rate >0)   $total = $total * $rate;
      
        $this->docform->editpayamount->setText(H::fa($total));
        $this->docform->payamount->setText(H::fa($total));
        $this->docform->editpayed->setText(H::fa($total));
        $this->docform->payed->setText(H::fa($total));
    }

    
    public function onDisc($sender) {
        $this->docform->disc->setText(H::fa($this->docform->editdisc->getText()));
        $this->CalcPay() ;
        $this->goAnkor("tankor");
    }
    public function onNds($sender) {
        $this->docform->nds->setText(H::fa($this->docform->editnds->getText()));
        $this->CalcPay() ;
        $this->goAnkor("tankor");
    }
    public function onRate($sender) {
        $this->docform->rate->setText(H::fa($this->docform->editrate->getText()));
        $this->CalcPay() ;
        $this->goAnkor("tankor");
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
            $this->docform->document_number->setText($this->_doc->nextNumber());
            $this->setError('nouniquedocnumber_created');
        }
        if (count($this->_itemlist) == 0) {
            $this->setError("noenteritem");
        }

        if ($this->docform->customer->getKey() == 0) {
            $this->setError("noselsender");
        }
        if ($this->docform->payment->getValue() == 0) {
            $this->setError("noselpaytype");
        }
        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutoItem($sender) {

        $text = Item::qstr('%' . $sender->getText() . '%');
        return Item::findArray('itemname', "(itemname like {$text} or item_code like {$text})  and disabled <> 1");
    }

    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "status=0 and (customer_name like {$text}  or phone like {$text} ) and   (detail like '%<type>2</type>%'  or detail like '%<type>0</type>%' ) ");
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
            $this->setError("entername");
            return;
        }
        $item = new Item();
        $item->itemname = $itemname;
        $item->item_code = $this->editnewitem->editnewitemcode->getText();
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

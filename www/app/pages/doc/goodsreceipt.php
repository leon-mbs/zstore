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
use \App\Entity\MoneyFund;
use \App\Helper as H;
use \App\System;
use \App\Application as App;

/**
 * Страница  ввода  приходной  накладной
 */
class GoodsReceipt extends \App\Pages\Base {

    public $_itemlist = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid = 0;

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $common = System::getOptions("common");

        $this->_tvars["colspan"] = $common['usesnumber'] == 1 ? 7:5;
        
        
        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));
        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('basedoc'));

        $this->docform->add(new TextInput('barcode'));
        $this->docform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');

        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList( true, true), H::getDefMF()))->onChange($this, 'OnPayment');

        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new TextInput('editpayamount'));
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
        $this->editdetail->add(new TextInput('editsnumber'));
        $this->editdetail->add(new Date('editsdate'));
        $this->editdetail->add(new ClickLink('openitemsel',$this,'onOpenItemSel'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');

        
        $this->add(new \App\Widgets\ItemSel('wselitem',$this,'onSelectItem'))->setVisible(false);
        
        
        //добавление нового товара
        $this->add(new Form('editnewitem'))->setVisible(false);
        $this->editnewitem->add(new TextInput('editnewitemname'));
        $this->editnewitem->add(new TextInput('editnewitemcode'));
        $this->editnewitem->add(new TextInput('editnewitembarcode'));
        $this->editnewitem->add(new TextInput('editnewitemsnumber'));
        $this->editnewitem->add(new TextInput('editnewitemsdate'));
        $this->editnewitem->add(new DropDownChoice('editnewcat', \App\Entity\Category::findArray("cat_name", "", "cat_name"), 0));
        $this->editnewitem->add(new Button('cancelnewitem'))->onClick($this, 'cancelnewitemOnClick');
        $this->editnewitem->add(new SubmitButton('savenewitem'))->onClick($this, 'savenewitemOnClick');

        
        //добавление нового контрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new TextInput('editaddress'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');
        
        
        if ($docid > 0) {    //загружаем   содержимок  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->basedoc->setText($this->_doc->basedoc);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);
            $this->docform->payamount->setText($this->_doc->payamount);
            $this->docform->editpayamount->setText($this->_doc->payamount);
            $this->docform->nds->setText($this->_doc->headerdata['nds']);
            $this->docform->editnds->setText($this->_doc->headerdata['nds']);
            $this->docform->rate->setText($this->_doc->headerdata['rate']);
            $this->docform->editrate->setText($this->_doc->headerdata['rate']);
            $this->docform->disc->setText($this->_doc->headerdata['disc']);
            $this->docform->editdisc->setText($this->_doc->headerdata['disc']);
            $this->docform->payed->setText($this->_doc->payed);
            $this->docform->editpayed->setText($this->_doc->payed);
            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);


            $this->OnPayment($this->docform->payment);

            $this->docform->total->setText($this->_doc->amount);

            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
            
        } else {
            $this->_doc = Document::create('GoodsReceipt');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'OrderCust') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $order = $basedoc->cast();
                        $this->docform->basedoc->setText('Заказ ' . $order->document_number);
                        $this->_itemlist = $basedoc->unpackDetails('detaildata');
                        $this->CalcTotal();
                        $this->CalcPay();
                    }
                    if ($basedoc->meta_name == 'InvoiceCust') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $invoice = $basedoc->cast();
                        $this->docform->basedoc->setText('Счет ' . $invoice->document_number);
                        $this->docform->payment->setValue(\App\Entity\MoneyFund::PREPAID);
                        $this->docform->nds->setText($invoice->headerdata['nds']);
                        $this->docform->editnds->setText($invoice->headerdata['nds']);
                        $this->docform->rate->setText($invoice->headerdata['rate']);
                        $this->docform->editrate->setText($invoice->headerdata['rate']);
                        $this->docform->disc->setText($invoice->headerdata['disc']);
                        $this->docform->editdisc->setText($invoice->headerdata['disc']);


                        $this->_itemlist = $basedoc->unpackDetails('detaildata');
                         $this->CalcTotal();
                        $this->CalcPay();
                    }
                    $this->calcTotal();
                    if ($basedoc->meta_name == 'GoodsReceipt') {

                        $this->docform->store->setValue($this->docform->store->getValue());
                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $basedoc = $basedoc->cast();
                       
                        $this->docform->payment->setValue(\App\Entity\MoneyFund::PREPAID);

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
        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? date('Y-m-d', $item->sdate) : ''));

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
        $this->editdetail->editsnumber->setText($item->snumber);
        $this->editdetail->editsdate->setDate($item->sdate);


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
        $this->calcPay();

        $this->docform->detail->Reload();
    }

    public function addcodeOnClick($sender) {
        $code = trim($this->docform->barcode->getText());
        $this->docform->barcode->setText('');
        if ($code == '')
            return;

        foreach ($this->_itemlist as $_item) {
            if ($_item->bar_code == $code) {
                $this->_itemlist[$_item->item_id]->quantity += 1;
                $this->docform->detail->Reload();
                $this->calcTotal();
                $this->CalcPay();
                return;
            }
        }


        $code = Item::qstr($code);
        $item = Item::getFirst("  (item_code = {$code} or bar_code = {$code})");

        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->_rowid = 0;

        if ($item == null) {
         
            $this->setWarn('item_notfound');
        } else {
            $this->editdetail->edititem->setKey($item->item_id);
            $this->editdetail->edititem->setText($item->itemname);
            $this->editdetail->editprice->setText('');
        }
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->_rowid = 0;
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
        $item->snumber = $this->editdetail->editsnumber->getText();

        if (strlen($item->snumber) == 0 && $item->useserial == 1 && $this->_tvars["usesnumber"] == true) {
            $this->setError("needs_serial");
            return;
        }


        $item->sdate = $this->editdetail->editsdate->getDate();
        if ($item->sdate == false)
            $item->sdate = '';
 
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

        $this->wselitem->setVisible(false);
        //очищаем  форму
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        $this->editdetail->editsnumber->setText("");
        $this->editdetail->editsdate->setText("");
        $this->goAnkor("lankor");
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->wselitem->setVisible(false);
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $this->goAnkor("");

        $firm = H::getFirmData($this->_doc->branch_id);
        $this->_doc->headerdata["firmname"] = $firm['firmname'];

        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->_doc->customer_id > 0) {
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText() . ' ' . $customer->phone;
        }
        $this->_doc->payamount = $this->docform->payamount->getText();
        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();
        $this->_doc->headerdata['rate'] = $this->docform->rate->getText();
        $this->_doc->headerdata['nds'] = $this->docform->nds->getText();
        $this->_doc->headerdata['disc'] = $this->docform->disc->getText();
        $this->_doc->headerdata['basedoc'] = $this->docform->basedoc->getText();

        $this->_doc->payed = $this->docform->payed->getText();

        if ($this->_doc->headerdata['payment'] == \App\Entity\MoneyFund::PREPAID) {
            $this->_doc->payed = 0;
            $this->_doc->payamount = 0;
        }
        if ($this->_doc->headerdata['payment'] == \App\Entity\MoneyFund::CREDIT) {
            $this->_doc->payed = 0;
        }

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

                if ($this->_doc->parent_id > 0) {   //закрываем заказ
                    if ($this->_doc->payamount > 0 && $this->_doc->payamount > $this->_doc->payed) {
                        
                    } else {
                        $order = Document::load($this->_doc->parent_id);
                        if ($order->state == Document::STATE_INPROCESS) {
                            $order->updateStatus(Document::STATE_CLOSED);
                          
                            $this->setSuccess("order_closed", $order->document_number );
                        }
                    }
                }
            } else {

                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }


            if ($file['size'] > 0) {
                H::addFile($file, $this->_doc->document_id, 'Скан', \App\Entity\Message::TYPE_DOC);
            }

            //если  выполнен и оплачен
            if ($this->_doc->state == Document::STATE_EXECUTED && $this->_doc->payment > 0 && $this->_doc->payed == $this->_doc->payment) {
                $orders = $this->_doc->getChildren('OrderCust');
                foreach ($orders as $order) {
                    if ($order->state == Document::STATE_INPROCESS) {
                        //закрываем заявку
                        $order->updateStatus(Document::STATE_CLOSED);
                    }
                }
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

    public function onPayAmount($sender) {

        $this->docform->payamount->setText($this->docform->editpayamount->getText());
        $this->docform->payed->setText($this->docform->editpayamount->getText());
        $this->docform->editpayed->setText($this->docform->editpayamount->getText());
        $this->goAnkor("tankor");
    }

    public function onPayed($sender) {
        $this->docform->payed->setText(H::fa($this->docform->editpayed->getText()));
        $this->goAnkor("tankor");
    }

    public function OnPayment($sender) {
        $this->docform->payed->setVisible(true);
        $this->docform->payamount->setVisible(true);
        $this->docform->nds->setVisible(true);
        $this->docform->rate->setVisible(true);
        $this->docform->disc->setVisible(true);


        $b = $sender->getValue();


        if ($b == \App\Entity\MoneyFund::PREPAID) {
            $this->docform->payed->setVisible(false);
            $this->docform->payamount->setVisible(false);
            $this->docform->nds->setVisible(false);
            $this->docform->rate->setVisible(false);
            $this->docform->disc->setVisible(false);
            
        }
        if ($b == \App\Entity\MoneyFund::CREDIT) {
            $this->docform->payed->setVisible(false);
        }
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
        $disc  = $this->docform->disc->getText();
        $nds   = $this->docform->nds->getText();
        $rate  = $this->docform->rate->getText();

        $total = $total + $nds - $disc;  
        if($rate !=1 && $rate >0)   $total = $total * $rate;
        
        $this->docform->editpayamount->setText(round($total));
        $this->docform->payamount->setText(round($total));
        $this->docform->editpayed->setText(round($total));
        $this->docform->payed->setText(round($total));
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
        if (($this->docform->store->getValue() > 0 ) == false) {
            $this->setError("noselstore");
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

        $text = trim($sender->getText());
        return Item::findArrayAC($text);
    }

    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "status=0 and   (customer_name like {$text}  or phone like {$text} ) and   (detail like '%<type>2</type>%'  or detail like '%<type>0</type>%' )");
    }

    //добавление нового товара
    public function addnewitemOnClick($sender) {
        $this->editnewitem->setVisible(true);
        $this->editdetail->setVisible(false);

        $this->editnewitem->clean();


        if (System::getOption("common", "autoarticle") == 1) {
            $this->editnewitem->editnewitemcode->setText(Item::getNextArticle());
        }
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

        $itemname = Item::qstr($item->itemname);
        $code = Item::qstr($item->item_code);
        $cnt = Item::findCnt("item_id <> {$item->item_id} and itemname={$itemname} and item_code={$code} ");
        if ($cnt > 0) {
           
            $this->setError('itemnamecode_exists');
            return;
        }


        $item->bar_code = $this->editnewitem->editnewitembarcode->getText();
        $item->snumber = $this->editnewitem->editnewitemsnumber->getText();
        if(strlen($item->snumber) >0) $item->useserial = 1;
        
        $item->sdate = $this->editnewitem->editnewitemsdate->getDate();
        if ($item->sdate == false)
            $item->sdate = '';
  

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

    
    //добавление нового контрагента
    public function addcustOnClick($sender) {
        $this->editcust->setVisible(true);
        $this->docform->setVisible(false);

        $this->editcust->editcustname->setText('');
        $this->editcust->editaddress->setText('');
        $this->editcust->editphone->setText('');
    }

    public function savecustOnClick($sender) {
        $custname = trim($this->editcust->editcustname->getText());
        if (strlen($custname) == 0) {
            $this->setError("entername");
            return;
        }
        $cust = new Customer();
        $cust->customer_name = $custname;
        $cust->address = $this->editcust->editaddress->getText();
        $cust->phone = $this->editcust->editphone->getText();

        if (strlen($cust->phone) > 0 && strlen($cust->phone) != 10) {
            $this->setError("");
            $this->setError("tel10");
            return;
        }

        $c = Customer::getByPhone($cust->phone);
        if ($c != null) {
            if ($c->customer_id != $cust->customer_id) {
         
                $this->setError("existcustphone");
                return;
            }
        }
        $cust->type = Customer::TYPE_SELLER ;
        $cust->save();
        $this->docform->customer->setText($cust->customer_name);
        $this->docform->customer->setKey($cust->customer_id);

        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }    
    
    public function onOpenItemSel($sender){
        $this->wselitem->setVisible(true);
        $this->wselitem->Reload();
    }
    public function onSelectItem($item_id,$itemname){
        $this->editdetail->edititem->setKey($item_id);
        $this->editdetail->edititem->setText($itemname);
        
    }
}

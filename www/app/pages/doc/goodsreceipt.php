<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\MoneyFund;
use App\Entity\Store;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода  приходной  накладной
 */
class GoodsReceipt extends \App\Pages\Base
{

    public  $_itemlist  = [];
    private $_doc;
    private $_basedocid = 0;
    private $_rowid     = 0;
    public $_sllist    = [];
  
    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $common = System::getOptions("common");

        $this->_tvars["colspan"] = $common['usesnumber'] == 1 ? 9 : 7;
   
        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnCustomerFirm');
        $this->docform->add(new DropDownChoice('firm', \App\Entity\Firm::getList(), H::getDefFirm()))->onChange($this, 'OnCustomerFirm');
        $this->docform->add(new DropDownChoice('contract', array(), 0))->setVisible(false);;

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));
        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('outnumber'));
        $this->docform->add(new TextInput('basedoc'));

        $this->docform->add(new TextInput('barcode'));
        $this->docform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');

        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), H::getDefMF()));
        $this->docform->add(new TextInput('rate','1'))->setVisible(false);

        $this->docform->add(new DropDownChoice('val', H::getValList(), '0'))->onChange($this, 'OnVal');

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
        $this->docform->add(new TextInput('editdisc', "0"));
        $this->docform->add(new SubmitButton('bdisc'))->onClick($this, 'onDisc');
        $this->docform->add(new TextInput('editdelivery', "0"));
        $this->docform->add(new SubmitButton('bdelivery'))->onClick($this, 'onDelivery');

      
        $this->docform->add(new Label('nds', 0));
        $this->docform->add(new Label('disc', 0));
        $this->docform->add(new Label('delivery', 0));

        $this->docform->add(new Label('payed', 0));
        $this->docform->add(new Label('payamount', 0));
        $this->docform->add(new Label('total'));
        $this->docform->add(new \Zippy\Html\Form\File('scan'));

        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutoItem');
        $this->editdetail->edititem->onChange($this, 'OnChangeItem', true);
        $this->editdetail->add(new SubmitLink('addnewitem'))->onClick($this, 'addnewitemOnClick');
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editsellprice'));
        $this->editdetail->add(new TextInput('editsnumber'));
        $this->editdetail->add(new Date('editsdate'));
        $this->editdetail->add(new ClickLink('openitemsel', $this, 'onOpenItemSel'));
        $this->editdetail->add(new ClickLink('openlast', $this, 'onOpenLast'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');

        $this->add(new \App\Widgets\ItemSel('wselitem', $this, 'onSelectItem'))->setVisible(false);
        $this->add(new Panel('sellastitem'))->setVisible(false);
        $this->sellastitem->add( new  DataView('sllist',new ArrayDataSource($this,'_sllist') , $this, 'slOnRow'))   ;

        //добавление нового товара
        $this->add(new Form('editnewitem'))->setVisible(false);
        $this->editnewitem->add(new TextInput('editnewitemname'));
        $this->editnewitem->add(new TextInput('editnewitemcode'));
        $this->editnewitem->add(new TextInput('editnewitembarcode'));
        $this->editnewitem->add(new TextInput('editnewitemsnumber'));
        $this->editnewitem->add(new Date('editnewitemsdate'));
        $this->editnewitem->add(new TextInput('editnewmanufacturer'));
        $this->editnewitem->add(new TextInput('editnewmsr'));
        $this->editnewitem->add(new DropDownChoice('editnewcat', \App\Entity\Category::getList(), 0));
        $this->editnewitem->add(new Button('cancelnewitem'))->onClick($this, 'cancelnewitemOnClick');
        $this->editnewitem->add(new SubmitButton('savenewitem'))->onClick($this, 'savenewitemOnClick');

        //добавление нового контрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new TextInput('editaddress'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->basedoc->setText($this->_doc->basedoc);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);
            $this->docform->nds->setText(H::fa($this->_doc->headerdata['nds']));
            $this->docform->editnds->setText(H::fa($this->_doc->headerdata['nds']));
            $this->docform->val->setValue($this->_doc->headerdata['val']);
            $this->docform->rate->setText($this->_doc->headerdata['rate']);
            $this->docform->outnumber->setText($this->_doc->headerdata['outnumber']);
            $this->docform->disc->setText(H::fa($this->_doc->headerdata['disc']));
            $this->docform->editdisc->setText(H::fa($this->_doc->headerdata['disc']));
            $this->docform->delivery->setText(H::fa($this->_doc->headerdata['delivery']));
            $this->docform->editdelivery->setText(H::fa($this->_doc->headerdata['delivery']));

            if (  $this->_doc->headerdata['payed'] > 0) {
                $this->_doc->payed = $this->_doc->headerdata['payed'];
            }
            if ( $this->_doc->headerdata['payamount'] > 0) {
                $this->_doc->payamount = $this->_doc->headerdata['payamount'];
            }
            $this->docform->editpayed->setText(H::fa($this->_doc->payed));
            $this->docform->payed->setText(H::fa($this->_doc->payed));
            $this->docform->payamount->setText(H::fa($this->_doc->payamount));
            $this->docform->editpayamount->setText(H::fa($this->_doc->payamount));

            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);
            $this->docform->firm->setValue($this->_doc->firm_id);
            $this->OnCustomerFirm($this->docform->customer);

            $this->docform->contract->setValue($this->_doc->headerdata['contract_id']);


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
                        $this->docform->val->setValue(0);
                        $this->docform->rate->setText(1);

                        $order = $basedoc->cast();
                        $this->docform->basedoc->setText(  $order->document_number);
                        $this->_itemlist = $basedoc->unpackDetails('detaildata');
                        $this->CalcTotal();
                        $this->CalcPay();
                    }
                    if ($basedoc->meta_name == 'InvoiceCust') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $invoice = $basedoc->cast();
                        $this->docform->basedoc->setText(  $invoice->document_number);
                        
                        $this->docform->nds->setText($invoice->headerdata['nds']);
                        $this->docform->editnds->setText($invoice->headerdata['nds']);
                        $this->docform->val->setValue($invoice->headerdata['val']);
                        $this->docform->rate->setText($invoice->headerdata['rate']);
                        $this->docform->firm->setValue($invoice->firm_id);
                        $this->OnCustomerFirm($this->docform->customer);
                        
                        $this->docform->contract->setValue($invoice->headerdata['contract_id']);
                        
                        $this->_doc->headerdata['prepaid']  = $invoice->payamount ;
                        if($this->_doc->headerdata['prepaid'] ==0) {
                           $this->docform->disc->setText($invoice->headerdata['disc']);
                           $this->docform->editdisc->setText($invoice->headerdata['disc']);
                            
                           $this->OnChangeCustomer($this->docform->customer);
                        }
                    
                    
                    
                        if(strlen($invoice->headerdata['val'])>1) {
                                $this->_doc->headerdata['prepaid']  =  $invoice->headerdata['payed'];
                        }       
                        $this->_itemlist = $basedoc->unpackDetails('detaildata');
                        $this->CalcTotal();
                        $this->CalcPay();
 
                    }
                  //  $this->calcTotal();
                    if ($basedoc->meta_name == 'GoodsReceipt') {

                        $this->docform->store->setValue($this->docform->store->getValue());
                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $basedoc = $basedoc->cast();
                        $this->docform->firm->setValue($basedoc->firm_id);
                        $this->OnCustomerFirm($this->docform->customer);
                        $this->docform->val->setValue($basedoc->headerdata['val']);
                        $this->docform->rate->setText($basedoc->headerdata['rate']);

                        $this->docform->contract->setValue($basedoc->headerdata['contract_id']);

                        $this->docform->payment->setValue(0);

                        $this->_itemlist = $basedoc->unpackDetails('detaildata');

                        $this->CalcTotal();
                        $this->CalcPay();
                    }
                }
            }
        }

        $this->_tvars["prepaid"] = (doubleval($this->_doc->headerdata['prepaid'])>0) ?  H::fa($this->_doc->headerdata['prepaid']) : false;
        $common = System::getOptions("common");
        $this->_tvars['price1name'] = $common['price1'];
 
        
        $this->docform->add(new DataView('detail', new ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        
        $this->OnVal($this->docform->val);
        
        
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }


    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label('num', $row->getNumber()));

        $row->add(new Label('item', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('bar_code', $item->bar_code));
        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('price', H::fa($item->price)));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? \App\Helper::fd($item->sdate) : ''));

        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');

        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
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

        $this->calcTotal();
        $this->calcPay();

        $this->docform->detail->Reload();
    }

    public function addcodeOnClick($sender) {
        $code = trim($this->docform->barcode->getText());
         
        $code = ltrim($code,'0');

        $this->docform->barcode->setText('');
        if ($code == '') {
            return;
        }

        foreach ($this->_itemlist as $ri => $_item) {
            if ($_item->bar_code == $code || $_item->item_code == $code || $_item->bar_code == $code0 || $_item->item_code == $code0 ) {
                $this->_itemlist[$ri]->quantity += 1;
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
            $this->addnewitemOnClick(null);
        } else {
            $this->editdetail->edititem->setKey($item->item_id);
            $this->editdetail->edititem->setText($item->itemname);
            $this->editdetail->editprice->setText('');
            $this->editdetail->editsellprice->setText('');
        }
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

        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editprice->setText($item->price);

        $olditem = Item::load($item->item_id);
        if ($olditem != null) {
            $this->editdetail->editsellprice->setText($olditem->price1);
        }


        $this->editdetail->editsellprice->setText($item->price1);
        $this->editdetail->editsnumber->setText($item->snumber);
        $this->editdetail->editsdate->setDate($item->sdate);

        $this->editdetail->edititem->setKey($item->item_id);
        $this->editdetail->edititem->setText($item->itemname);

        if ($item->rowid > 0) {
            ;
        }               //для совместимости
        else {
            $item->rowid = $item->item_id;
        }

        $this->_rowid = $item->rowid;
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
        $sellprice = $this->editdetail->editsellprice->getText();
        if (strlen($sellprice) > 0) {
            $olditem = Item::load($item->item_id);
            if ($olditem != null) {
                $olditem->price1 = $sellprice;
                $olditem->save();
            }
        }


        if ($item->price == 0) {

            $this->setWarn("no_price");
        }
        $item->snumber = $this->editdetail->editsnumber->getText();

        if (strlen($item->snumber) == 0 && $item->useserial == 1 && $this->_tvars["usesnumber"] == true) {
            $this->setError("needs_serial");
            return;
        }


        $item->sdate = $this->editdetail->editsdate->getDate();
        if ($item->sdate == false) {
            $item->sdate = '';
        }


        if ($this->_rowid > 0) {
            $item->rowid = $this->_rowid;
            
            $this->editdetail->setVisible(false);
            $this->docform->setVisible(true);            
            $this->wselitem->setVisible(false);           
            $this->sellastitem->setVisible(false);           
        } else {
            $next = count($this->_itemlist) > 0 ? max(array_keys($this->_itemlist)) : 0;
            $item->rowid = $next + 1;
        }
        $this->_itemlist[$item->rowid] = $item;

        $this->_rowid = 0;


        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();

        
        //очищаем  форму
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        $this->editdetail->editsnumber->setText("");
        $this->editdetail->editsdate->setText("");
        $this->editdetail->editsellprice->setText("");
        
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->wselitem->setVisible(false);
        $this->sellastitem->setVisible(false);
        $this->goAnkor("lankor");        
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->goAnkor("");

        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->_doc->customer_id > 0) {
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        }
        $this->_doc->headerdata['contract_id'] = $this->docform->contract->getValue();
        $this->_doc->firm_id = $this->docform->firm->getValue();
        if ($this->_doc->firm_id > 0) {
            $this->_doc->headerdata['firm_name'] = $this->docform->firm->getValueName();
        }

        
        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['storename'] = $this->docform->store->getValueName();
        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();
        $this->_doc->headerdata['val'] = $this->docform->val->getValue();
        $this->_doc->headerdata['valname'] = $this->docform->val->getValueName();
        $this->_doc->headerdata['rate'] = $this->docform->rate->getText();
        $this->_doc->headerdata['nds'] = $this->docform->nds->getText();
        $this->_doc->headerdata['disc'] = $this->docform->disc->getText();
        $this->_doc->headerdata['delivery'] = $this->docform->delivery->getText();
        $this->_doc->headerdata['outnumber'] = $this->docform->outnumber->getText();
        $this->_doc->headerdata['basedoc'] = $this->docform->basedoc->getText();
        
        
        $this->_doc->payamount = $this->docform->payamount->getText();
        $this->_doc->headerdata['payamount'] = $this->docform->payamount->getText();

        $this->_doc->payed = $this->docform->payed->getText();
        $this->_doc->headerdata['payed'] = $this->docform->payed->getText();

        
        

        if ($this->checkForm() == false) {
            return;
        }

        $file = $this->docform->scan->getFile();
        if ($file['size'] > 10000000) {
            $this->setError("filemore10M");
            return;
        }

        if ($this->_doc->payed == 0) {
            $this->_doc->headerdata['payment'] = 0;
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
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }

 
                $this->_doc->updateStatus(Document::STATE_EXECUTED);
                if($this->_doc->headerdata['payamount'] > $this->_doc->headerdata['payed'] && $this->_doc->headerdata['payamount'] > doubleval($this->_doc->headerdata['prepaid'] ) ) {
                      $this->_doc->updateStatus(Document::STATE_WP);                    
                }

                if ($this->_doc->parent_id > 0) {   //закрываем заказ
                    if ($this->_doc->payamount > 0 && $this->_doc->payamount > $this->_doc->payed) {

                    } else {
                        $order = Document::load($this->_doc->parent_id);
                        if ($order->meta_name =="OrderCust" && $order->state == Document::STATE_INPROCESS) {
                            $order->updateStatus(Document::STATE_CLOSED);

                            $this->setSuccess("order_closed", $order->document_number);
                        }
                    }
                }

                //обновляем  курс
                if (strlen($this->_doc->headerdata['val']) > 1) {
                    $optval = \App\System::getOptions("val");
                    if (strlen($optval[$this->_doc->headerdata['val']]) > 0) {
                        $optval[$this->_doc->headerdata['val']] = $this->_doc->headerdata['rate'];
                        \App\System::setOptions("val", $optval);
                    }
                }
            } else {

                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }


            if ($file['size'] > 0) {
                $id = H::addFile($file, $this->_doc->document_id, 'Скан', \App\Entity\Message::TYPE_DOC);
                $imagedata = getimagesize($file["tmp_name"]);
                if ($imagedata[0] > 0) {
                    $this->_doc->headerdata["scan"] = $id;
                    $this->_doc->save();
                }
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

        if (false == \App\ACL::checkShowReg('GRList',false)) {
            App::RedirectHome() ;
        }
        else {
          App::Redirect("\\App\\Pages\\Register\\GRList");
        }
       
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


    public function onDisc($sender) {
        $this->docform->disc->setText(H::fa($this->docform->editdisc->getText()));
        $this->CalcPay();
        $this->goAnkor("tankor");
    }
    public function onDelivery($sender) {
        $this->docform->delivery->setText(H::fa($this->docform->editdelivery->getText()));
        $this->CalcPay();
        $this->goAnkor("tankor");
    }

    public function onNds($sender) {
        $this->docform->nds->setText($this->docform->editnds->getText());
        $this->CalcPay();
        $this->goAnkor("tankor");
    }

    public function onRate($sender) {
        $this->docform->rate->setText($this->docform->editrate->getText());
        $this->CalcPay();
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
        $disc = doubleval($this->docform->disc->getText());
        $delivery = doubleval($this->docform->delivery->getText());
        $nds = doubleval($this->docform->nds->getText()) ;
        
        $total = $total + $nds - $disc  ;  
        $total +=  $delivery;
  
        $this->docform->editpayamount->setText(H::fa($total));
        $this->docform->payamount->setText(H::fa($total));
        if(doubleval( $this->_doc->headerdata['prepaid'])>0) {
           $total = $total - $this->_doc->headerdata['prepaid'];  
        }  
        
        
        $this->docform->editpayed->setText(H::fa($total));
        $this->docform->payed->setText(H::fa($total));
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
        if (count($this->_itemlist) == 0) {
            $this->setError("noenteritem");
        }
        if (($this->docform->store->getValue() > 0) == false) {
            $this->setError("noselstore");
        }
        if ($this->docform->customer->getKey() == 0) {
            $this->setError("noselsender");
        }
        if ($this->docform->payment->getValue() == 0 && $this->_doc->payed > 0) {
            $this->setError("noselmfp");
        }
        $val = $this->docform->val->getValue();
        if (strlen($val) > 1) {
            if($this->_doc->payamount  > $this->_doc->payed )  {
                $this->setError("nocreditval");
             
                
                return;
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

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 2);
    }

    public function OnVal($sender) {
        $val = $sender->getValue();
        $this->docform->rate->setVisible(false);        
        $rate = 1;
        if (strlen($val) > 1) {
            $optval = \App\System::getOptions("val");
            foreach($optval['vallist'] as $v){
                 if($v->code == $val) $rate=$v->rate;   
            }
            $this->docform->rate->setVisible(true);            
        } 
        $this->docform->rate->setText($rate);
        
    }

    //добавление нового товара
    public function addnewitemOnClick($sender) {
        $this->editnewitem->setVisible(true);
        $this->editdetail->setVisible(false);
        $this->wselitem->setVisible(false);
        $this->sellastitem->setVisible(false);

        $this->editnewitem->clean();

        if (System::getOption("common", "autoarticle") == 1) {
            $this->editnewitem->editnewitemcode->setText(Item::getNextArticle());
        }

        $this->editnewitem->editnewmanufacturer->setDataList(Item::getManufacturers());

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
        $item->bar_code = $this->editnewitem->editnewitembarcode->getText();

        if (strlen($item->item_code) > 0 && System::getOption("common", "nocheckarticle") != 1) {
            $code = Item::qstr($item->item_code);
            $cnt = Item::findCnt("  item_code={$code} ");
            if ($cnt > 0) {
                $this->setError('itemcode_exists');
                return;
            }

        } 
        if (strlen($item->bar_code) > 0 ) {
            $code = Item::qstr($item->bar_code);
            $cnt = Item::findCnt("  bar_code={$code} ");
            if ($cnt > 0) {
                $this->setError('barcode_exists');
                return;
            }

        } 
        if (strlen($item->item_code) == 0 &&  System::getOption("common", "autoarticle") == 1) {

            $item->item_code = Item::getNextArticle();
        }
 


        $item->manufacturer = $this->editnewitem->editnewmanufacturer->getText();
        $item->snumber = $this->editnewitem->editnewitemsnumber->getText();
        if (strlen($item->snumber) > 0) {
            $item->useserial = 1;
        }

        $item->sdate = $this->editnewitem->editnewitemsdate->getDate();
        if ($item->sdate == false) {
            $item->sdate = '';
        }
        $this->editdetail->editsnumber->setText($item->snumber);
        $this->editdetail->editsdate->setText($item->sdate);


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
        $cust->phone = \App\Util::handlePhone($cust->phone);

        if (strlen($cust->phone) > 0 && strlen($cust->phone) != H::PhoneL()) {
            $this->setError("");
            $this->setError("tel10", H::PhoneL());
            return;
        }

        $c = Customer::getByPhone($cust->phone);
        if ($c != null) {
            if ($c->customer_id != $cust->customer_id) {

                $this->setError("existcustphone");
                return;
            }
        }
        $cust->type = Customer::TYPE_SELLER;
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

    public function onOpenItemSel($sender) {
        $this->sellastitem->setVisible(false);
        $this->wselitem->setVisible(true);
        $this->wselitem->Reload();
    }

    public function onSelectItem($item_id, $itemname,$price=null) {
        $this->editdetail->edititem->setKey($item_id);
        $this->editdetail->edititem->setText($itemname);
        $item = Item::load($item_id);

        if($price==null){
          $price = $item->getLastPartion($this->docform->store->getValue()   , null, false);
            
        }
     
        $this->editdetail->editprice->setText(H::fa($price));
        $this->editdetail->editsellprice->setText(H::fa($item->price1));
    }

    public function OnCustomerFirm($sender) {
        $c = $this->docform->customer->getKey();
        $f = $this->docform->firm->getValue();

        $ar = \App\Entity\Contract::getList($c, $f);

        $this->docform->contract->setOptionList($ar);
        if (count($ar) > 0) {
            $this->docform->contract->setVisible(true);
        } else {
            $this->docform->contract->setVisible(false);
            $this->docform->contract->setValue(0);
        }
    }

    public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $item = Item::load($id);

        $this->editdetail->editsellprice->setText($item->price1);
  
    }
        
    public function slOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label("sldate", H::fd($item->date)))  ;
        $row->add(new Label("slitem_code", $item->item_code))  ;
        $row->add(new Label("slbar_code", $item->bar_code))  ;
        $row->add(new Label("slprice", $item->price))  ;
        $row->add(new ClickLink("slitem",$this,"onSLItem" ))->setValue($item->itemname)  ;
    }
    
    public function onSLItem($sender) {
        $item = $sender->getOwner()->getDataItem();
        
 
        $this->onSelectItem($item->item_id,$item->itemname,$item->price);
    }
    
    public function onOpenLast($sender) {
       $cid = $this->docform->customer->getKey();
       if($cid == 0){
           $this->setError("noselsender");
           return;
       } 
       $ptype=0;
       $p = $this->docform->payment->getValue();
       if($p > 0){
        //  $mf = \App\Entity\MoneyFund::load($p) ;   
       //   $p = $mf->beznal == 1 ? 2:1;
       }
       $this->sellastitem->setVisible(true);
       $this->wselitem->setVisible(false);
       $this->_sllist = [];
       $conn = \ZDB\DB::getConnect()  ;
       $dt = $conn->DBDate( strtotime("-1 month",time() ) );

       $docs=  Document::find("customer_id={$cid} and  meta_name='GoodsReceipt' and  document_date >= {$dt} ","document_id desc") ;
       foreach($docs as $doc){
           
          if($p > 0 && $p != $doc->headerdata['payment']){ 
              continue;
          }
           
          foreach($doc->unpackDetails('detaildata') as $item){
           
               $r = new \App\DataItem() ;
               $r->date= $doc->document_date ;
               $r->item_id= $item->item_id ;
               $r->item_code= $item->item_code ;
               $r->bar_code= $item->bar_code ;
               $r->itemname= $item->itemname ;
               $r->price = $item->price ;
               if($this->_sllist[$r->item_id] != null) {
                  continue;  
               }
               
               $this->_sllist[$r->item_id]=$r;
          }
       }
       
       $this->sellastitem->sllist->Reload();
    }

}

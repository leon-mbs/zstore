<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\MoneyFund;
use App\Entity\Stock;
use App\Entity\Store;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода  расходной накладной
 */
class GoodsIssue extends \App\Pages\Base
{
    public $_itemlist  = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid     = -1;
    private $_rownumber = 1;
    private $_orderid   = 0;
 
    private $_changedpos  = false;

    /**
    * @param mixed $docid     редактирование
    * @param mixed $basedocid  создание на  основании
    */
    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $common = System::getOptions("common");

        $firm = H::getFirmData(  $this->branch_id);


        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());

        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), H::getDefMF()));
        $this->docform->add(new DropDownChoice('salesource', H::getSaleSources(), H::getDefSaleSource()));

        $this->docform->add(new Label('custinfo'));

        $this->docform->add(new TextInput('editpayed', "0"));
        $this->docform->add(new SubmitButton('bpayed'))->onClick($this, 'onPayed');
        $this->docform->add(new Label('payed', 0));
        $this->docform->add(new Label('payamount', 0));
        $this->docform->add(new TextInput('edittotaldisc'));
        $this->docform->add(new SubmitButton('btotaldisc'))->onClick($this, 'onTotaldisc');
        $this->docform->add(new Label('totaldisc'));
        $this->docform->add(new Label('totalnds'));

        $this->docform->add(new TextInput('barcode'));
        $this->docform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');
        
        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));
        $this->docform->add(new DropDownChoice('storeemp', \App\Entity\Employee::findArray("emp_name", "disabled<>1", "emp_name"))) ;
       
        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');
        $this->docform->addcust->setVisible(       \App\ACL::checkEditRef('CustomerList',false));

        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnChangeCustomer');

        $this->docform->add(new DropDownChoice('contract', array(), 0))->setVisible(false);

        $this->docform->add(new DropDownChoice('pricetype', Item::getPriceTypeList(), H::getDefPriceType()));

        $this->docform->add(new TextInput('order'));

        $this->docform->add(new TextInput('notes'));
        $fops=[];
        foreach(($firm['fops']??[]) as $fop) {
          $fops[$fop->id]=$fop->name ; 
        }
        $this->docform->add(new DropDownChoice('fop', $fops,0))->setVisible(count($fops)>0) ;

        $cp = \App\Session::getSession()->clipboard;
        $this->docform->add(new ClickLink('paste', $this, 'onPaste'))->setVisible(is_array($cp) && count($cp) > 0);

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->docform->add(new Label('total'));

        $this->add(new \App\Widgets\ItemSel('wselitem', $this, 'onSelectItem'))->setVisible(false);

        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editserial'));

        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');
        $this->editdetail->edittovar->onChange($this, 'OnChangeItem', true);

        $this->editdetail->add(new Label('qtystock'));
        $this->editdetail->add(new Label('qtystockex'));
        $this->editdetail->add(new Label('pricestock'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');

        $this->editdetail->add(new ClickLink('openitemsel', $this, 'onOpenItemSel'));

        //добавление нового контрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new TextInput('editemail'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->pricetype->setValue($this->_doc->headerdata['pricetype']);
            $this->docform->totaldisc->setText($this->_doc->headerdata['totaldisc']);
            $this->docform->edittotaldisc->setText($this->_doc->headerdata['totaldisc']);
            $this->docform->total->setText(H::fa($this->_doc->amount));
            $this->docform->payamount->setText($this->_doc->payamount);

            $this->docform->document_date->setDate($this->_doc->document_date);

            $this->docform->payment->setValue($this->_doc->headerdata['payment']);
            $this->docform->salesource->setValue($this->_doc->headerdata['salesource']);
            $this->docform->storeemp->setValue($this->_doc->headerdata['storeemp']);
            $this->docform->fop->setValue($this->_doc->headerdata['fop']);
         
      
            $this->docform->editpayed->setText(H::fa($this->_doc->headerdata['payed']));
            $this->docform->payed->setText(H::fa($this->_doc->headerdata['payed']));


            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->customer->setKey($this->_doc->customer_id);

            if ($this->_doc->customer_id) {
                $this->docform->customer->setText($this->_doc->customer_name);
            } else {
                $this->docform->customer->setText($this->_doc->headerdata['customer_name']);
            }

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->order->setText($this->_doc->headerdata['order']);
            $this->_orderid = $this->_doc->headerdata['order_id'];


             $this->OnChangeCustomer($this->docform->customer);

            $this->docform->contract->setValue($this->_doc->headerdata['contract_id']);

            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('GoodsIssue');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {

                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'Order') {
                        if($basedoc->getHD('paytype',0) != 3){
                            $this->_doc->headerdata['prepaid']  = $basedoc->payamount ;
                        }         

                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $this->docform->salesource->setValue($basedoc->headerdata['salesource']);
                        $this->docform->pricetype->setValue($basedoc->headerdata['pricetype']);
                        $this->docform->totaldisc->setText($basedoc->headerdata['totaldisc']);
                        $this->docform->edittotaldisc->setText($basedoc->headerdata['totaldisc']);
                        // $this->docform->store->setValue($basedoc->headerdata['store']);
                        $this->_orderid = $basedocid;
                        $this->docform->order->setText($basedoc->document_number);

                        $notfound = array();
                        $order = $basedoc->cast();

                        if($order->getNotSendedItem() == 0){
                           $this->setWarn('Позиції по  цьому замовленню вже відправлені') ;
                        }

                        $this->docform->total->setText(H::fa($order->amount));

                        $this->_itemlist = $basedoc->unpackDetails('detaildata');
                        if($basedoc->state == Document::STATE_WP || $basedoc->hasPayments()) {
                            $this->_doc->headerdata['prepaid']  = abs($basedoc->payamount);
                        }

                    
                        
                        if($order->headerdata['store']>0) {
                            $this->docform->store->setValue($order->headerdata['store']);
                        }
                        if($order->headerdata['payment']>0) {
                            $this->docform->payment->setValue($order->headerdata['payment']);
                        }



                        // $this->calcTotal();
                        $this->docform->total->setText($basedoc->amount);

                        $this->calcPay();



                        //  $this->docform->editpayed->setText($this->docform->editpayamount->getText());
                        //   $this->docform->payed->setText($this->docform->payamount->getText());




                    }
                    if ($basedoc->meta_name == 'Invoice') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $this->docform->pricetype->setValue($basedoc->headerdata['pricetype']);
                        $this->docform->store->setValue($basedoc->headerdata['store']);
                        $this->docform->edittotaldisc->setText($basedoc->headerdata['totaldisc']);
                        $this->docform->totaldisc->setText($basedoc->headerdata['totaldisc']);
                        $this->docform->fop->setValue($basedoc->headerdata['fop']);
     
                        $notfound = array();
                        $invoice = $basedoc->cast();


                        
                        $this->docform->contract->setValue($basedoc->headerdata['contract_id']);
                    

                        
                        $this->_itemlist = [];
                        foreach($basedoc->unpackDetails('detaildata') as $k=>$v) {
                            
                           if($v instanceof \App\Entity\Service) {
                               $this->setError('Послуги не  можуть додаватись до накладної') ;
                               return;
                           }
                           $this->_itemlist[$k] =$v;
                        }
                 
                        
                        $this->_doc->headerdata['prepaid']  = $basedoc->payamount ;
                        $this->docform->total->setText($invoice->amount);


                        $this->docform->total->setText($basedoc->amount);

                        $this->calcTotal();
                        $this->calcPay();


                    }


                    if ($basedoc->meta_name == 'GoodsIssue') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        if ($basedoc->customer_id > 0) {
                            $this->docform->customer->setText($basedoc->customer_name);
                        } else {
                            $this->docform->customer->setText($basedoc->headerdata['customer_name']);
                        }


                        $this->docform->pricetype->setValue($basedoc->headerdata['pricetype']);
                        $this->docform->store->setValue($basedoc->headerdata['store']);
                        $this->docform->totaldisc->setText($basedoc->headerdata['totaldisc']);
                        $this->docform->edittotaldisc->setText($basedoc->headerdata['totaldisc']);
                        $this->docform->salesource->setValue($basedoc->headerdata['salesource']);

                        $this->OnCustomerFirm(null);

                        $this->docform->contract->setValue($basedoc->headerdata['contract_id']);
                        $this->docform->fop->setValue($basedoc->headerdata['fop']);
     

                        foreach ($basedoc->unpackDetails('detaildata') as $item) {
                            $item->price = $item->getPrice($basedoc->headerdata['pricetype']); //последние  цены
                            $this->_itemlist[ ] = $item;
                        }
                        // $this->OnChangeCustomer($this->docform->customer);
                        //$this->calcTotal();
                        //$this->calcPay();
                        $this->docform->total->setText($basedoc->amount);
                        $this->calcTotal();
                        $this->calcPay();

                    }
                    if ($basedoc->meta_name == 'ServiceAct') {

                        $this->docform->notes->setText('Підстава ' . $basedoc->document_number);
                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);
                    }
                    if ($basedoc->meta_name == 'IncomeMoney') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);
                        $this->_doc->headerdata['prepaid']  = $basedoc->payed ;

                        // $this->docform->order->setText(  $basedoc->document_number);

                    }


                    if ($basedoc->meta_name == 'GoodsReceipt') {


                        foreach ($basedoc->unpackDetails('detaildata') as $item) {
                            $item->price = $item->getPrice(); //последние  цены
                            $this->_itemlist[ ] = $item;
                        }
                        $this->calcTotal();
                        $this->calcPay();
                    }
                    
                    
                    $ch = $basedoc->getChildren('GoodsIssue');
                    
                    if(count($ch)>0) {
                        $this->setWarn('Вже створено накладну на  підставі '.$basedoc->document_number) ;
                    }       
                    
                    
                }
            } else {
                if(intval($common['paytypeout']) == 1) {
                    $this->setWarn('Накладну слід створювати на  підставі   рахунку-фактури або замовлення') ;
                }
            }
        }

        $this->_tvars["prepaid"] = (doubleval($this->_doc->headerdata['prepaid']??0)>0) ? H::fa($this->_doc->headerdata['prepaid']) : false;

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }


    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('num', $this->_rownumber++));
        $row->add(new Label('tovar', $item->itemname));

        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? date('Y-m-d', $item->sdate) : ''));

        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('disc', $item->disc));
        $row->add(new Label('price', H::fa($item->price)));
     
        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }

        $item = $sender->owner->getDataItem();
        $rowid =  array_search($item, $this->_itemlist, true);

        $this->_itemlist = array_diff_key($this->_itemlist, array($rowid => $this->_itemlist[$rowid]));
        $this->_rownumber  = 1;

        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();
        $this->_changedpos = true;
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("0");
        $this->editdetail->editserial->setText("");
        $this->editdetail->qtystock->setText("");
        $this->editdetail->qtystockex->setText("");
        $this->editdetail->pricestock->setText("");
        $this->docform->setVisible(false);
        $this->_rowid = -1;
    }

    //вставка  сканером
    public function addcodeOnClick($sender) {
     //   $common = \App\System::getOptions("common");
        
        $code = trim($this->docform->barcode->getText());

        if ($code == '') {
            return;
        }
        
        $this->docform->barcode->setText('');
        
        $store_id = $this->docform->store->getValue();
        if ($store_id == 0) {
            $this->setError('Не обрано склад');
            return;
        }

        $code_ = Item::qstr($code);
        $item = Item::findBarCode($code,$store_id);
 
     
        if ($item != null) { 
            foreach ($this->_itemlist as $ri => $_item) {
                if ($_item->item_id == $item->item_id ) {
                    $this->_itemlist[$ri]->quantity += 1;
                    $this->_rownumber  = 1;

                    $this->docform->detail->Reload();
                    $this->calcTotal();
                    $this->CalcPay();
                    return;
                }
            }
        }

 
        if ($item == null) {      //ищем по серийному

            $st = Stock::find("store_id={$store_id} and  snumber=" . $code_);
            if(count($st)==1) {
                $st = array_pop($st) ;
                $item = Item::load($st->item_id);

            }    
            if ($item != null) {  
                $item->snumber =   $code;
            }
        }
      // проверка  на  стикер
        if ($item == null) {
       
            $item = Item::unpackStBC($code);
            if($item instanceof Item) {
                $item->pureprice = $item->getPurePrice();
                $this->_itemlist[ ] = $item;
                $this->_rownumber  = 1;

                $this->docform->detail->Reload();
                $this->calcTotal();
                $this->calcPay();
                return; 
            }          
        } 
        if ($item == null) {  
                $this->setWarn("Товар з кодом `{$code}` не знайдено");
                return;
        }  
        
        $qty = $item->getQuantity($store_id);
        if ($qty <= 0) {

            $this->setWarn("Товару {$item->itemname} немає на складі");
        }


        $customer_id = $this->docform->customer->getKey()  ;
        $pt=     $this->docform->pricetype->getValue() ;
        $price = $item->getPriceEx(array(
           'pricetype'=>$pt,
           'store'=>$store_id,
           'customer'=>$customer_id
         ));

        $item->price = $price;

        $item->disc = '';
        $item->pureprice = $item->getPurePrice();
        if($item->pureprice > $item->price) {
            $item->disc = number_format((1 - ($item->price/($item->pureprice)))*100, 1, '.', '') ;
        }


        $item->quantity = 1;

        if (strlen($item->snumber) == 0  &&  $this->_tvars["usesnumber"] == true && $item->useserial == 1) {

            $serial = '';
            $slist = $item->getSerials($store_id);
            if (count($slist) == 1) {
                $serial = array_pop($slist);
            }


            if (strlen($serial) == 0) {
                $this->setWarn('Потрібна партія виробника');
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



        $this->_itemlist[ ] = $item;
        $this->_rownumber  = 1;

        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();

        $this->_rowid = -1;
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->edittovar->setKey($item->item_id);
        $this->editdetail->edittovar->setText($item->itemname);

        $this->OnChangeItem($this->editdetail->edittovar);

        $this->editdetail->editprice->setText($item->price);
        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editserial->setText($item->snumber);

        $this->_rowid =  array_search($item, $this->_itemlist, true);

    }

    public function saverowOnClick($sender) {
        $common = System::getOptions("common");

        $id = $this->editdetail->edittovar->getKey();
        if ($id == 0) {
            $this->setError("Не обрано товар");
            return;
        }
        $item = Item::load($id);
        $store_id = $this->docform->store->getValue();

        $item->quantity = $this->editdetail->editquantity->getDouble();
        $item->snumber = trim($this->editdetail->editserial->getText());
        $qstock = $this->editdetail->qtystock->getText();


        $item->price = $this->editdetail->editprice->getDouble();
        $item->disc = '';
        $item->pureprice = $item->getPurePrice();
        if($item->pureprice > $item->price) {
            $item->disc = number_format((1 - ($item->price/($item->pureprice)))*100, 1, '.', '') ;
        }
        if ($item->quantity > $qstock) {

            $this->setWarn('Введено більше товару, чим мається в наявності');
        }
        $item->pricenonds= $item->price - $item->price * $item->nds(true);
 
        
        if($common['usesnumber'] > 0 && $item->useserial == 1 ) {
            
            if (strlen($item->snumber) == 0  ) {

                $this->setError("Потрібен серійний номер");
                return;
            }
            

            $slist = $item->getSerials($store_id);
            
            if (in_array($item->snumber, $slist) == false) {

                $this->setError('Невірний серійний номер  ');
                return;
            }  

            
            if($common['usesnumber'] == 2  ) {           
                $st = Stock::getFirst("store_id={$store_id} and item_id={$item->item_id} and snumber=" . Stock::qstr($item->snumber));
                if ($st instanceof Stock) {
                     $item->sdate = $st->sdate;
                }           
            }
            if($common['usesnumber'] == 3  ) {           

                foreach(  $this->_itemlist as $i){
                    if($this->_rowid == -1 && strlen($item->snumber) > 0 &&  $item->snumber==$i->snumber )  {
                        $this->setError('Вже є ТМЦ  з таким серійним номером');
                        return;
                        
                    }
                }
                
            }
        }
  
        if($this->_rowid == -1) {
            $found=false;
  
            foreach ($this->_itemlist as $ri => $_item) {
                if ($_item->item_id == $item->item_id && $_item->snumber == $item->snumber) {
                    $this->_itemlist[$ri]->quantity += $item->quantity;
                    $found = true;
                }
            }        
            if(!$found) {
               $this->_itemlist[] = $item;    
            }
            
            
            $this->addrowOnClick(null);
            $this->setInfo("Позиція додана") ;
            //очищаем  форму
            $this->editdetail->edittovar->setKey(0);
            $this->editdetail->edittovar->setText('');
            $this->editdetail->editserial->setText("");
            $this->editdetail->editquantity->setText("1");

            $this->editdetail->editprice->setText("");
        } else {
            $this->_itemlist[$this->_rowid] = $item;
            $this->cancelrowOnClick(null);

        }



        $this->_rownumber  = 1;

        $this->docform->detail->Reload();


        $this->calcTotal();
        $this->calcPay();
        $this->_changedpos = true;

    }

    public function cancelrowOnClick($sender) {
        $this->wselitem->setVisible(false);
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
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $this->_doc->notes = $this->docform->notes->getText();
        //   $this->_doc->order = $this->docform->order->getText();
        $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->_doc->customer_id > 0) {
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        }
        $this->_doc->headerdata['totaldisc'] = $this->docform->totaldisc->getText();
        $this->_doc->headerdata['salesource'] = $this->docform->salesource->getValue();
        $this->_doc->headerdata['contract_id'] = $this->docform->contract->getValue();
       
        $this->_doc->payamount =  doubleval($this->docform->payamount->getText());
        $this->_doc->payed = doubleval($this->docform->payed->getText());
        $this->_doc->headerdata['payed'] = $this->_doc->payed;
        $this->_doc->headerdata['fop'] = $this->docform->fop->getValue();
        $this->_doc->headerdata['nds'] = $this->docform->totalnds->getText();
    

        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();


        if ($this->checkForm() == false) {
            return;
        }

        $this->_doc->headerdata['order_id'] = $this->_orderid;
        $this->_doc->headerdata['order'] = $this->docform->order->getText();

        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['store_name'] = $this->docform->store->getValueName();
        $this->_doc->headerdata['storeemp'] = $this->docform->storeemp->getValue();
        $this->_doc->headerdata['storeempname'] = $this->docform->storeemp->getValueName();
        $this->_doc->headerdata['pricetype'] = $this->docform->pricetype->getValue();
        $this->_doc->headerdata['pricetypename'] = $this->docform->pricetype->getValueName();

        $this->_doc->headerdata['order_id'] = $this->_orderid;

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
       
               if ($this->_doc->parent_id > 0) {   //закрываем заказ
                    $order = Document::load($this->_doc->parent_id)->cast();

                    if($this->_changedpos) {
                        $msg= "В документі {$this->_doc->document_number}, створеному на підставі {$order->document_number}, користувачем ".\App\System::getUser()->username." змінено перелік ТМЦ " ;
                        \App\Entity\Notify::toSystemLog($msg) ;
                    }
                    if($order->meta_name == 'Order') {
                        $order->unreserve();     
                    }  
                }
       
       
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }


                $this->_doc->updateStatus(Document::STATE_EXECUTED);
                if($this->_doc->payamount > $this->_doc->payed && $this->_doc->payamount > doubleval($this->_doc->headerdata['prepaid'])) {
                    $this->_doc->updateStatus(Document::STATE_WP);
                }
                
             } else {

                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }

 
            $conn->CommitTrans();



            if (false == \App\ACL::checkShowReg('GIList', false)) {
                App::RedirectHome() ;
            } else {
                App::Redirect("\\App\\Pages\\Register\\GIList", $this->_doc->document_id);
            }


        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $this->setError($ee->getMessage());

            $logger->error('Line '. $ee->getLine().' '.$ee->getFile().'. '.$ee->getMessage()  );
            return;
        }
    }

    public function onTotaldisc($sender) {
        $this->docform->totaldisc->setText($this->docform->edittotaldisc->getDouble());
        $this->calcPay() ;
        $this->goAnkor("tankor");
    }

    public function onPayed($sender) {
        $this->docform->payed->setText(H::fa($this->docform->editpayed->getDouble()));
        $payed = $this->docform->payed->getText();
        $payamount = $this->docform->payamount->getText();
        if ($payed > $payamount) {

            $this->setWarn('Внесена сума більше необхідної');
        } else {
            $this->goAnkor("tankor");
        }
    }


    /**
     * Расчет  итого
     *
     */
    private function calcTotal() {

        $total = 0;
        $nds = 0;

        foreach ($this->_itemlist as $item) {
            $item->amount = H::fa($item->price * $item->quantity);
 
            if($item->pricenonds < $item->price) {
                $nds = $nds + doubleval($item->price - $item->pricenonds) * $item->quantity;                
            }
     
            $total = $total + $item->amount;
        }
        $this->docform->total->setText(H::fa($total));
      
        if($this->_tvars['usends'] != true) {
           $nds=0; 
        }
      
        if($nds>0) {
            $this->docform->totalnds->setText(H::fa($nds));            
        }
      

    }

    private function calcPay() {

        $common = System::getOptions("common");

        $total = doubleval($this->docform->total->getText());
        $totaldisc = doubleval($this->docform->totaldisc->getText());
        $totalnds = doubleval($this->docform->totalnds->getText());

        if($totaldisc > 0) {
            $total = $total - $totaldisc;
        }
        if($totalnds>0) {
            $total = $total + $totalnds;
        }

        $this->docform->payamount->setText(H::fa($total));
        $prepaid = doubleval($this->_doc->headerdata['prepaid']??0) ;
        if ($prepaid > 0) {
            //  $disc =0;

            $total -= $prepaid;
        }
        //внесена  оплата
        if(intval($common['paytypeout']) == 2) {
            $total = 0;
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

        if (($this->docform->store->getValue() > 0) == false) {
            $this->setError("Не обрано склад");
        }
        $c = $this->docform->customer->getKey();

        $noallowfiz = System::getOption("common", "noallowfiz");
        if ($noallowfiz == 1 && $c == 0) {
            $this->setError("Не задано контрагента");
        }
        if ($this->docform->payment->getValue() == 0 && $this->_doc->payed > 0) {
            $this->setError("Якщо внесена сума більше нуля, повинна бути обрана каса або рахунок");
        }

        if ($this->_doc->amount > 0 && $this->_doc->payamount > $this->_doc->payed && $c == 0) {
            $this->setError("Якщо у борг або передоплата або списання бонусів має бути обраний контрагент");
        }
        if ($this->docform->payment->getValue() == 0 && $this->_doc->payed > 0) {
            $this->setError("Якщо внесена сума більше нуля, повинна бути обрана каса або рахунок");
        }


        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $item = Item::load($id);
        $store_id = $this->docform->store->getValue();


        $customer_id = $this->docform->customer->getKey()  ;
        $pt=     $this->docform->pricetype->getValue() ;
        $price = $item->getPriceEx(array(
           'pricetype'=>$pt,
           'store'=>$store_id,
           'customer'=>$customer_id
         ));
        $qty = $item->getQuantity($store_id,"",0,$this->docform->storeemp->getValue());
        $qtyex = $item->getQuantity() - $qty;

        $this->editdetail->qtystock->setText(H::fqty($qty));
        $this->editdetail->qtystockex->setText(H::fqty($qtyex));
        
        $this->editdetail->editprice->setText($price);
        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {
            $serial = $item->getNearestSerie($store_id);
            $this->editdetail->editserial->setText($serial);
        }

        $price = $item->getPartion($store_id);
        $this->editdetail->pricestock->setText(H::fa($price));

       

    }

    public function OnAutoItem($sender) {
        $store_id = $this->docform->store->getValue();
        $text = trim($sender->getText());
        return Item::findArrayAC($text, $store_id);
    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 1, true);
    }

    public function OnChangeCustomer($sender) {
        $this->docform->custinfo->setVisible(false);

        $customer_id = $this->docform->customer->getKey();
        if ($customer_id > 0) {
            $cust = Customer::load($customer_id);

            if (strlen($cust->pricetype) > 4) {
                $this->docform->pricetype->setValue($cust->pricetype);
            }


            $disctext = "";
            $d = $cust->getDiscount() ;
            if (doubleval($d) > 0) {
                $disctext = "Постійна знижка {$d}%";
            } else {
             //   $bonus = $cust->getBonus();
             //   if ($bonus > 0) {
              //      $disctext = "Нараховано бонусів {$bonus} ";
              //  }
            }
            $this->docform->custinfo->setText($disctext);
            $this->docform->custinfo->setVisible(strlen($disctext) >0);

        }

        $this->OnCustomerFirm(null);
       

    }

    //добавление нового контрагента
    public function addcustOnClick($sender) {
        $this->editcust->setVisible(true);
        $this->docform->setVisible(false);

        $this->editcust->editcustname->setText('');
        $this->editcust->editemail->setText('');
        $this->editcust->editphone->setText('');
    }

    public function savecustOnClick($sender) {
        $custname = trim($this->editcust->editcustname->getText());
        if (strlen($custname) == 0) {
            $this->setError("Не введено назву");
            return;
        }
        $cust = new Customer();
        $cust->customer_name = $custname;
        $cust->email = $this->editcust->editemail->getText();
        $cust->phone = $this->editcust->editphone->getText();
        $cust->phone = \App\Util::handlePhone($cust->phone);

        if (strlen($cust->phone) > 0 && strlen($cust->phone) != H::PhoneL()) {
            $this->setError("");
            $this->setError("Довжина номера телефона повинна бути ".\App\Helper::PhoneL()." цифр");
            return;
        }

        $c = Customer::getByPhone($cust->phone);
        if ($c != null) {
            if ($c->customer_id != $cust->customer_id) {

                $this->setError("Вже існує контрагент з таким телефоном");
                return;
            }
        }
        $cust->type = Customer::TYPE_BAYER;
        $cust->save();
        $this->docform->customer->setText($cust->customer_name);
        $this->docform->customer->setKey($cust->customer_id);

        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->custinfo->setVisible(false);
    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function onOpenItemSel($sender) {
        $this->wselitem->setVisible(true);
        $this->wselitem->setPriceType($this->docform->pricetype->getValue());
        $this->_rownumber  = 1;

        $this->wselitem->Reload();
    }

    public function onSelectItem($item_id, $itemname) {
        $this->editdetail->edittovar->setKey($item_id);
        $this->editdetail->edittovar->setText($itemname);
        $this->OnChangeItem($this->editdetail->edittovar);
    }

    public function OnCustomerFirm($sender) {
        $c = $this->docform->customer->getKey();
      
        $ar = \App\Entity\Contract::getList($c );
        $this->docform->contract->setOptionList($ar);
        if (count($ar) > 0) {
            $this->docform->contract->setVisible(true);
        } else {
            $this->docform->contract->setVisible(false);
            $this->docform->contract->setValue(0);
        }


    }

    public function onPaste($sender) {
        $store_id = $this->docform->store->getValue();

        $cp = \App\Session::getSession()->clipboard;

        foreach ($cp as $it) {
            $item = Item::load($it->item_id);
            if ($item == null) {
                continue;
            }
            $item->quantity = 1;
            $item->price = $item->getPrice($this->docform->pricetype->getValue(), $store_id);

            $this->_itemlist[$item->item_id] = $item;
        }
        $this->_rownumber  = 1;

        $this->docform->detail->Reload();

        $this->calcTotal();
        $this->calcPay();
    }


   
         
    
}

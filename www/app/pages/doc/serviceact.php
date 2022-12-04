<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\MoneyFund;
use App\Entity\Service;
use App\Entity\Item;
use App\Entity\Store;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода  акта выполненных работ
 */
class ServiceAct extends \App\Pages\Base
{

    public  $_servicelist = array();
    public  $_itemlist = array();
    private $_doc;
    private $_rowid       = 0;
    private $_rowid2       = 0;
    private $_basedocid   = 0;

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnCustomerFirm');

        $this->docform->add(new DropDownChoice('firm', \App\Entity\Firm::getList(), H::getDefFirm()))->onChange($this, 'OnCustomerFirm');
        $this->docform->add(new DropDownChoice('contract', array(), 0))->setVisible(false);;
        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));

        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('gar'));
        $this->docform->add(new TextInput('device'));
        $this->docform->add(new TextInput('devsn'));

        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), 0));

        $this->docform->add(new TextInput('editpayamount'));
        $this->docform->add(new SubmitButton('bpayamount'))->onClick($this, 'onPayAmount');


        $this->docform->add(new TextInput('payed', 0));
        $this->docform->add(new Label('payamount', 0));

        $this->docform->add(new Label('discount'));
        $this->docform->add(new TextInput('editpaydisc'));
        $this->docform->add(new SubmitButton('bpaydisc'))->onClick($this, 'onPayDisc');
        $this->docform->add(new Label('paydisc', 0));

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitLink('additemrow'))->onClick($this, 'addItemrowOnClick');
        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('paydoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Label('total'));
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new DropDownChoice('editservice', Service::findArray("service_name", "disabled<>1", "service_name")))->onChange($this, 'OnChangeServive', true);

        $this->editdetail->add(new TextInput('editqty'));
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextArea('editdesc'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');

        
        $this->add(new Form('edititemdetail'))->setVisible(false);
        $this->edititemdetail->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutoItem');
        $this->edititemdetail->edititem->onChange($this, 'OnChangeItem', true);

        $this->edititemdetail->add(new TextInput('edititemqty'));
        $this->edititemdetail->add(new TextInput('edititemprice'));
        $this->edititemdetail->add(new Label('qtystock'));

        $this->edititemdetail->add(new Button('cancelrowitem'))->onClick($this, 'cancelrowOnClick');
        $this->edititemdetail->add(new SubmitButton('saverowitem'))->onClick($this, 'saveitemrowOnClick');
           
        
        //добавление нового кантрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new TextInput('editemail'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        if ($docid > 0) { //загружаем   содержимое   документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->gar->setText($this->_doc->headerdata['gar']);
            $this->docform->store->setValue($this->_doc->headerdata['store']);
  
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);
            $this->docform->payamount->setText($this->_doc->payamount);
            $this->docform->editpayamount->setText($this->_doc->payamount);
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);

            if ($this->_doc->payed == 0 && $this->_doc->headerdata['payed'] > 0) {
                $this->_doc->payed = $this->_doc->headerdata['payed'];
            }

            $this->docform->payed->setText(H::fa($this->_doc->payed));

            $this->docform->device->setText($this->_doc->device);
            $this->docform->devsn->setText($this->_doc->devsn);
            $this->docform->paydisc->setText($this->_doc->headerdata['paydisc']);
            $this->docform->editpaydisc->setText($this->_doc->headerdata['paydisc']);

            $this->docform->total->setText($this->_doc->amount);

            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);
            $this->docform->firm->setValue($this->_doc->firm_id);
            $this->OnCustomerFirm(null);
            $this->docform->contract->setValue($this->_doc->headerdata['contract_id']);

            $this->_servicelist = $this->_doc->unpackDetails('detaildata');
            $this->_itemlist = $this->_doc->unpackDetails('detail2data');
        } else {
            $this->_doc = Document::create('ServiceAct');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);

                if ($basedoc->meta_name == 'Task') {
                    $this->docform->customer->setKey($basedoc->customer_id);
                    $this->docform->customer->setText($basedoc->customer_name);
                    $this->_servicelist = array();
                    foreach($basedoc->unpackDetails('detaildata') as $v ) {
                       $this->_servicelist[$v->service_id]= $v ;    
                    }
                    
                }
                if ($basedoc->meta_name == 'Invoice') {
                    $this->docform->customer->setKey($basedoc->customer_id);
                    $this->docform->customer->setText($basedoc->customer_name);

                    $this->_servicelist = array();
                    foreach($basedoc->unpackDetails('detaildata') as $v ) {
                       if($v->service_id>0) {
                           $this->_servicelist[$v->service_id]= $v ;                               
                       }

                    }
                    foreach($basedoc->unpackDetails('detaildata') as $v ) {
                       if($v->item_id>0) {
                           $this->_itemlist[$v->item_id]= $v ;                               
                       }

                    }
                }
                if ($basedoc->meta_name == 'ServiceAct') {
                    $this->docform->customer->setKey($basedoc->customer_id);
                    $this->docform->customer->setText($basedoc->customer_name);

                    $this->_servicelist = array();
                    foreach($basedoc->unpackDetails('detaildata') as $v ) {
                       $this->_servicelist[$v->service_id]= $v ;    
                    }
                    $this->_itemlist = array();
                    foreach($basedoc->unpackDetails('detail2data') as $v ) {
                       $this->_itemlist[$v->item_id]= $v ;    
                    }
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_servicelist')), $this, 'detailOnRow'))->Reload();
        $this->docform->add(new DataView('detail2', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detail2OnRow'))->Reload();
        $this->calcTotal();
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }
  
    public function detailOnRow($row) {
        $service = $row->getDataItem();

        $row->add(new Label('service', $service->service_name));
        $row->add(new Label('desc', $service->desc));

        $row->add(new Label('qty', H::fqty($service->quantity)));
        $row->add(new Label('price', H::fa($service->price)));
        $row->add(new Label('amount', H::fa($service->price * $service->quantity)));

        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }
    public function detail2OnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('itemname', $item->itemname));
        $row->add(new Label('item_code', $item->item_code));

        $row->add(new Label('qtyitem', H::fqty($item->quantity)));
        $row->add(new Label('priceitem', H::fa($item->price)));
        $row->add(new Label('amountitem', H::fa($item->price * $item->quantity)));

        $row->add(new ClickLink('edititem'))->onClick($this, 'edititemOnClick');
        $row->add(new ClickLink('deleteitem'))->onClick($this, 'deleteitemOnClick');
    }

    public function editOnClick($sender) {
        $service = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editdesc->setText(($service->desc));

        $this->editdetail->editprice->setText($service->price);
        $this->editdetail->editqty->setText($service->quantity);

        $this->editdetail->editservice->setValue($service->service_id);

        if ($service->rowid > 0) {
            ;
        }               //для совместимости
        else {
            $service->rowid = $service->service_id;
        }

        $this->_rowid = $service->rowid;
    }
   
    public function editItemOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->edititemdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->edititemdetail->edititem->setKey(($item->item_id));
        $this->edititemdetail->edititem->setText(($item->itemname));

        $this->edititemdetail->edititemprice->setText($item->price);
        $this->edititemdetail->edititemqty->setText($item->quantity);
       
        $this->OnChangeItem( $this->edititemdetail->edititem);

        if ($item->rowid > 0) {
            ;
        }               //для совместимости
        else {
            $item->rowid = $item->item_id;
        }

        $this->_rowid2 = $item->rowid;
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }

        $service = $sender->owner->getDataItem();

        if ($service->rowid > 0) {
            ;
        }               //для совместимости
        else {
            $service->rowid = $service->service_id;
        }

        $this->_servicelist = array_diff_key($this->_servicelist, array($service->rowid => $this->_servicelist[$service->rowid]));

        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();
    }
   
    public function deleteitemOnClick($sender) {
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

        $this->docform->detail2->Reload();
        $this->calcTotal();
        $this->calcPay();
    }


    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->_rowid = 0;
        $this->editdetail->editdesc->setText('');

        $this->editdetail->editprice->setText(0);
        $this->editdetail->editqty->setText("1");
    }
 
    public function additemrowOnClick($sender) {
        $this->edititemdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->_rowid2 = 0;
        $this->edititemdetail->edititem->setKey(0);
        $this->edititemdetail->edititem->setText('');
        $this->edititemdetail->qtystock->setText('');

        $this->edititemdetail->edititemprice->setText(0);
        $this->edititemdetail->edititemqty->setText("1");
    }
 
    public function saverowOnClick($sender) {
        $id = $this->editdetail->editservice->getValue();
        if ($id == 0) {
            $this->setError("noselservice");
            return;
        }
        $service = Service::load($id);

        $service->price = $this->editdetail->editprice->getText();
        $service->quantity = $this->editdetail->editqty->getText();
        $service->desc = $this->editdetail->editdesc->getText();

        if ($this->_rowid > 0) {
            $service->rowid = $this->_rowid;
        } else {
            $next = count($this->_servicelist) > 0 ? max(array_keys($this->_servicelist)) : 0;
            $service->rowid = $next + 1;
        }
        
        $kk = array_keys($this->_servicelist) ;
        $this->_servicelist[$service->rowid] = $service;

        $this->_rowid = 0;

        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();
        //очищаем  форму
        $this->editdetail->editservice->setValue(0);
        $this->editdetail->editdesc->setText('');

        $this->editdetail->editprice->setText("0");
    }

    public function saveitemrowOnClick($sender) {
        $id = $this->edititemdetail->edititem->getKey();
        if ($id == 0) {
            $this->setError("noselservice");
            return;
        }
        $item = Item::load($id);

        $item->price = $this->edititemdetail->edititemprice->getText();
        $item->quantity = $this->edititemdetail->edititemqty->getText();
      
        if ($this->_rowid2 > 0) {
            $item->rowid = $this->_rowid2;
        } else {
            $next = count($this->_itemlist) > 0 ? max(array_keys($this->_itemlist)) : 0;
            $item->rowid = $next + 1;
        }
        
        $kk = array_keys($this->_itemlist) ;
        $this->_itemlist[$item->rowid] = $item;

        $this->_rowid = 0;

        $this->edititemdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail2->Reload();
        $this->calcTotal();
        $this->calcPay();

    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->edititemdetail->setVisible(false);
        $this->docform->setVisible(true);
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
          //  $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        }
        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['device'] = $this->docform->device->getText();
        $this->_doc->headerdata['devsn'] = $this->docform->devsn->getText();
        $this->_doc->headerdata['contract_id'] = $this->docform->contract->getValue();
        $this->_doc->firm_id = $this->docform->firm->getValue();
        if ($this->_doc->firm_id > 0) {
            $this->_doc->headerdata['firm_name'] = $this->docform->firm->getValueName();
        }

        $this->calcTotal();

        $this->_doc->headerdata['gar'] = $this->docform->gar->getText();
        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();
        $this->_doc->headerdata['paydisc'] = $this->docform->paydisc->getText();

        $this->_doc->payamount = $this->docform->payamount->getText();

        $this->_doc->payed = $this->docform->payed->getText();

        $this->_doc->headerdata['payed'] = $this->docform->payed->getText();
        if ($this->checkForm() == false) {
            return;
        }

        $this->_doc->packDetails('detaildata', $this->_servicelist);
        $this->_doc->packDetails('detail2data', $this->_itemlist);

        $isEdited = $this->_doc->document_id > 0;
        $this->_doc->amount = $this->docform->total->getText();

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            if ($this->_basedocid > 0) {
                $this->_doc->parent_id = $this->_basedocid;
                $this->_basedocid = 0;
            }
            $this->_doc->payed = 0;
            $this->_doc->headerdata['payed'] = 0;
            $this->_doc->headerdata['payment'] = 0;
            
            $payamount = $this->docform->payamount->getText();

   
            if ($sender->id == 'execdoc') {
                if ( $payamount > 0 && $this->_doc->customer_id == 0) {
                    $this->setError('noselcustifnopay');
                    return;
                }
            }               
   
                
             
            if ($sender->id == 'paydoc') {
               $this->_doc->payed = $this->docform->payed->getText();
               $this->_doc->headerdata['payed'] = $this->docform->payed->getText();
               $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();
               
    
               if ($this->_doc->payed > $payamount) {
                    $this->setError('inserted_extrasum');
                    return;
               }               
               if ($this->_doc->payed == 0) {
                    return;
               }               
               if ($this->docform->payment->getValue() == 0 && $this->_doc->payed > 0) {
                    $this->setError("noselmfp");
                    return;
               }             

              
            }
            $this->_doc->save();

            if ($sender->id != 'savedoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }


                if ($sender->id == 'execdoc' || $sender->id == 'paydoc') {
                    $this->_doc->updateStatus(Document::STATE_INPROCESS);
                     
                }
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }

            $conn->CommitTrans();
            App::Redirect("\\App\\Pages\\Register\\SerList");

        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $this->setError($ee->getMessage());

            return;
        }
    }

    /**
     * Расчет  итого
     *
     */
    private function calcTotal() {

        $total = 0;

        foreach ($this->_servicelist as $item) {
            $item->amount = $item->price * $item->quantity;

            $total = $total + $item->amount;
        }
        foreach ($this->_itemlist as $item) {
            $item->amount = $item->price * $item->quantity;

            $total = $total + $item->amount;
        }
        $this->docform->total->setText(H::fa($total));

        $disc = 0;

        $customer_id = $this->docform->customer->getKey();
        if ($customer_id > 0) {
            $customer = Customer::load($customer_id);
            $d = $customer->getDiscount();
            if ($d > 0) {
                $disc = round($total * ($d / 100));
            } else {
                $bonus = $customer->getBonus();
                if ($bonus > 0) {
                    if ($total >= $bonus) {
                        $disc = $bonus;
                    } else {
                        $disc = $total;
                    }
                }
            }
        }

        $this->docform->paydisc->setText(H::fa($disc));
        $this->docform->editpaydisc->setText(H::fa($disc));
    }


    public function onPayAmount($sender) {
        $this->docform->payamount->setText($this->docform->editpayamount->getText());
        $this->docform->payed->setText($this->docform->editpayamount->getText());

    }

   
    private function CalcPay() {
        $total = $this->docform->total->getText();
        $disc = $this->docform->paydisc->getText();

        $this->docform->editpayamount->setText(H::fa($total - $disc));
        $this->docform->payamount->setText(H::fa($total - $disc));

        $this->docform->payed->setText(H::fa($total - $disc));
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
        if (count($this->_servicelist) == 0) {
            //  $this->setError("noenterpos");
        }

        

        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 1);
    }

    public function OnChangeServive($sender) {
        $id = $sender->getValue();

        $item = Service::load($id);
        $price = $item->getPrice();

        $this->editdetail->editprice->setText($price);

      
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
        if($c>0) {
            $cust = Customer::load($c);

            $disctext = "";
            $d= $cust->getDiscount()   ;
            if (doubleval($d) > 0) {
                $disctext = H::l("custdisc") . " {$d}%";
            } else {
                $bonus = $cust->getBonus();
                if ($bonus > 0) {
                    $disctext = H::l("custbonus") . " {$bonus} ";
                }
            }
            $this->docform->discount->setText($disctext);
        }
    }

    //добавление нового контрагента
    public function addcustOnClick($sender) {
        $this->editcust->setVisible(true);
        $this->docform->setVisible(false);

        $this->editcust->editcustname->setText('');
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
        $cust->phone = $this->editcust->editphone->getText();
        $cust->phone = \App\Util::handlePhone($cust->phone);
        $cust->email = $this->editcust->editemail->getText();

        if (strlen($cust->phone) > 0 && strlen($cust->phone) != H::PhoneL()) {
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

        $cust->save();
        $this->docform->customer->setText($cust->customer_name);
        $this->docform->customer->setKey($cust->customer_id);
        $this->OnCustomerFirm(null);
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }
     public function onPayDisc() {
        $this->docform->paydisc->setText($this->docform->editpaydisc->getText());
        $this->calcPay();
        $this->goAnkor("tankor");
    }
  
  
    public function OnAutoItem($sender) {
        $store_id = $this->docform->store->getValue();
        $text = trim($sender->getText());
        return Item::findArrayAC($text);
    }  
    
   public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $item = Item::load($id);
        $store_id = $this->docform->store->getValue();
        $price = $item->getPrice("price1", $store_id);
 
        $qty = $item->getQuantity($store_id);

        $this->edititemdetail->qtystock->setText(H::fqty($qty));
        $this->edititemdetail->edititemprice->setText($price);
    

    }    
}

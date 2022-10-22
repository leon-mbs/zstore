<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Customer;
use App\Entity\MoneyFund;
use App\Entity\Doc\Document;
use App\Entity\Item;

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
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода  заказа
 */
class Order extends \App\Pages\Base
{

    public  $_tovarlist = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid     = 0;

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());

        $this->docform->add(new CheckBox('production'));

        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnChangeCustomer');

        $this->docform->add(new TextArea('notes'));
        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), 0));
        $this->docform->add(new DropDownChoice('salesource', H::getSaleSources(), H::getDefSaleSource()));


        $this->docform->add(new TextInput('editpaydisc'));
        $this->docform->add(new SubmitButton('bpaydisc'))->onClick($this, 'onPayDisc');
        $this->docform->add(new Label('paydisc', 0));
       
        $this->docform->add(new TextInput('editbonus'));
        $this->docform->add(new SubmitButton('bbonus'))->onClick($this, 'onBonus');
        $this->docform->add(new Label('bonus', 0));

        $this->docform->add(new TextInput('editpayamount'));
        $this->docform->add(new SubmitButton('bpayamount'))->onClick($this, 'onPayAmount');

        $this->docform->add(new TextInput('payed', 0));
        $this->docform->add(new Label('payamount', 0));

        $this->docform->add(new Label('discount'))->setVisible(false);
        $this->docform->add(new DropDownChoice('pricetype', Item::getPriceTypeList()))->onChange($this, 'OnChangePriceType');

        $this->docform->add(new DropDownChoice('delivery', Document::getDeliveryTypes($this->_tvars['np'] == 1)))->onChange($this, 'OnDelivery');
        $this->docform->add(new TextInput('email'));
        $this->docform->add(new TextInput('phone'));
        $this->docform->add(new TextArea('address'))->setVisible(false);

        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('topaydoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('paydoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->docform->add(new Label('total'));

        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editdesc'));

        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');
        $this->editdetail->edittovar->onChange($this, 'OnChangeItem', true);
        $this->editdetail->add(new ClickLink('openitemsel', $this, 'onOpenItemSel'));
        $this->editdetail->add(new ClickLink('opencatpan', $this, 'onOpenCatPan'));
  
        $this->editdetail->add(new Label('qtystock'));
        $this->editdetail->add(new Label('pricestock'));
        $this->editdetail->add(new SubmitLink('addnewitem'))->onClick($this, 'addnewitemOnClick');

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');
        $this->add(new \App\Widgets\ItemSel('wselitem', $this, 'onSelectItem'))->setVisible(false);

        //добавление нового кантрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editcustphone'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        //добавление нового товара
        $this->add(new Form('editnewitem'))->setVisible(false);
        $this->editnewitem->add(new TextInput('editnewitemname'));
        $this->editnewitem->add(new TextInput('editnewitemcode'));
        $this->editnewitem->add(new TextInput('editnewbrand'));
        $this->editnewitem->add(new TextInput('editnewmsr'));
        $this->editnewitem->add(new Button('cancelnewitem'))->onClick($this, 'cancelnewitemOnClick');
        $this->editnewitem->add(new DropDownChoice('editnewcat', \App\Entity\Category::getList(), 0));
        $this->editnewitem->add(new SubmitButton('savenewitem'))->onClick($this, 'savenewitemOnClick');


        if ($docid > 0) {    //загружаем   содержимое  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->pricetype->setValue($this->_doc->headerdata['pricetype']);


            $this->docform->delivery->setValue($this->_doc->headerdata['delivery']);
            $this->OnDelivery($this->docform->delivery);
            $this->docform->production->setChecked($this->_doc->headerdata['production']);
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);
            $this->docform->salesource->setValue($this->_doc->headerdata['salesource']);
            $this->docform->total->setText($this->_doc->amount);

            $this->docform->payamount->setText($this->_doc->payamount);
            $this->docform->editpayamount->setText($this->_doc->payamount);
            $this->docform->paydisc->setText($this->_doc->headerdata['paydisc']);
            $this->docform->editpaydisc->setText($this->_doc->headerdata['paydisc']);
            $this->docform->bonus->setText($this->_doc->headerdata['bonus']);
            $this->docform->editbonus->setText($this->_doc->headerdata['bonus']);
            
            if ($this->_doc->payed == 0 && $this->_doc->headerdata['payed'] > 0) {
                $this->_doc->payed = $this->_doc->headerdata['payed'];
            }
         

            $this->docform->payed->setText(H::fa($this->_doc->payed));

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->email->setText($this->_doc->headerdata['email']);
            $this->docform->phone->setText($this->_doc->headerdata['phone']);
            $this->docform->address->setText($this->_doc->headerdata['ship_address']);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);

            $this->_tovarlist = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('Order');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            $this->_doc->headerdata['paydisc'] = 0;
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                }
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

        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('desc', $item->desc));

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
        $item = $sender->owner->getDataItem();
        if ($item->rowid > 0) {
            ;
        }               //для совместимости
        else {
            $item->rowid = $item->item_id;
        }

        $this->_tovarlist = array_diff_key($this->_tovarlist, array($item->rowid => $this->_tovarlist[$item->rowid]));

        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("0");
        $this->editdetail->editdesc->setText("");
        $this->docform->setVisible(false);
        $this->_rowid = 0;
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editprice->setText($item->price);
        $this->editdetail->editdesc->setText($item->desc);

        $this->editdetail->edittovar->setKey($item->item_id);
        $this->editdetail->edittovar->setText($item->itemname);

        if ($item->rowid > 0) {
            ;
        }               //для совместимости
        else {
            $item->rowid = $item->item_id;
        }

        $this->_rowid = $item->rowid;
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
        $item->desc = $this->editdetail->editdesc->getText();

        if ($this->_rowid > 0) {
            $item->rowid = $this->_rowid;
        } else {
            $next = count($this->_tovarlist) > 0 ? max(array_keys($this->_tovarlist)) : 0;
            $item->rowid = $next + 1;
        }
        $this->_tovarlist[$item->rowid] = $item;

        $this->_rowid = 0;

      //  $this->editdetail->setVisible(false);
     //   $this->docform->setVisible(true);

        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        $this->wselitem->setVisible(false);
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        $this->wselitem->setVisible(false);
        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();        
        
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



        $this->_doc->headerdata['delivery'] = $this->docform->delivery->getValue();
        $this->_doc->headerdata['delivery_name'] = $this->docform->delivery->getValueName();
        $this->_doc->headerdata['ship_address'] = $this->docform->address->getText();
        $this->_doc->headerdata['phone'] = $this->docform->phone->getText();
        $this->_doc->headerdata['email'] = $this->docform->email->getText();
        $this->_doc->headerdata['pricetype'] = $this->docform->pricetype->getValue();
        $this->_doc->headerdata['production'] = $this->docform->production->isChecked() ? 1 : 0;

        $this->_doc->packDetails('detaildata', $this->_tovarlist);

        $this->_doc->amount = $this->docform->total->getText();

        $this->_doc->payamount = $this->docform->payamount->getText();
  
        $this->_doc->headerdata['paydisc'] = $this->docform->paydisc->getText();
        $this->_doc->headerdata['bonus'] = $this->docform->bonus->getText();

        $this->_doc->headerdata['salesource'] = $this->docform->salesource->getValue();


        if ($this->checkForm() == false) {
            return;
        }
        $isEdited = $this->_doc->document_id > 0;

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
                  
            if ($sender->id == 'paydoc') {
               $this->_doc->payed = $this->docform->payed->getText();
               $this->_doc->headerdata['payed'] = $this->docform->payed->getText();
               $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();
               
               if ($this->_doc->payed > $this->_doc->payamount) {
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

            if ($sender->id == 'savedoc') {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }

                                       
            if ($sender->id == 'execdoc' || $sender->id == 'paydoc' || $sender->id == 'topaydoc') {
                $this->_doc->updateStatus(Document::STATE_INPROCESS);
            }
            if ($sender->id == 'topaydoc') {
                $this->_doc->updateStatus(Document::STATE_WP);
            }



            $conn->CommitTrans();
            if ($sender->id == 'execdoc') {
                // App::Redirect("\\App\\Pages\\Doc\\TTN", 0, $this->_doc->document_id);
            }
            
            
            if (false == \App\ACL::checkShowReg('OrderList',false)) {
                App::RedirectHome() ;
            }
            else {
              App::Redirect("\\App\\Pages\\Register\\OrderList");
            }
            
            

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
        $disc = 0;
        $bonus = 0;
 
        foreach ($this->_tovarlist as $item) {
            $item->amount = $item->price * $item->quantity;

            $total = $total + $item->amount;
        }
        $this->docform->total->setText(H::fa($total));

        $customer_id = $this->docform->customer->getKey();
        if ($customer_id > 0) {
            $customer = Customer::load($customer_id);
            $d = $customer->getDiscount();
            if ($d > 0) {
                $disc = round($total * ($d / 100));
                $this->docform->bonus->setText(0);
                $this->docform->editbonus->setText(0);

            }  else {
                $bonus = $customer->getBonus();
                if ($bonus > 0) {
                    
     

                    if ($total < $bonus) {
                        $bonus = $bonus - $total; 
                    }
                }
          
            }
            
        }


        $this->docform->paydisc->setText($disc);
        $this->docform->editpaydisc->setText($disc);
        $this->docform->bonus->setText($bonus);
        $this->docform->editbonus->setText($bonus);
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

        $c = $this->docform->customer->getKey();
        if ($c == 0) {
            $this->setError("noselcust");
        }
   


        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $item = Item::load($id);
        $price = $item->getPrice($this->docform->pricetype->getValue());

        $this->editdetail->qtystock->setText(H::fqty($item->getQuantity()));
        $this->editdetail->editprice->setText($price);
        $price = $item->getLastPartion();
        $this->editdetail->pricestock->setText( H::fa($price));

      
    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 1);
    }

    public function OnChangeCustomer($sender) {
        $disc = 0;
        $bonus = 0;
  
        $customer_id = $this->docform->customer->getKey();
        if ($customer_id > 0) {
            $customer = Customer::load($customer_id);

            $this->docform->phone->setText($customer->phone);
            $this->docform->email->setText($customer->email);
            $this->docform->address->setText($customer->address);
            $d= $customer->getDiscount();
            if ($d > 0) {
                $this->docform->discount->setText(H::l("custdisc") ." ". $d . '%');
                $this->docform->discount->setVisible(true);
                  
            } else {
                
            }
        }


        $this->calcTotal();

        $this->calcPay();
    }

    public function OnAutoItem($sender) {
        return Item::findArrayAC($sender->getText());
    }

    //добавление нового контрагента
    public function addcustOnClick($sender) {
        $this->editcust->setVisible(true);
        $this->docform->setVisible(false);

        $this->editcust->editcustname->setText('');
        $this->editcust->editcustphone->setText('');
    }

    public function savecustOnClick($sender) {
        $custname = trim($this->editcust->editcustname->getText());
        if (strlen($custname) == 0) {
            $this->setError("entername");
            return;
        }
        $cust = new Customer();
        $cust->customer_name = $custname;
        $cust->phone = $this->editcust->editcustphone->getText();
        $cust->phone = \App\Util::handlePhone($cust->phone);

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

        $cust->type = 1;
        $cust->save();
        $this->docform->customer->setText($cust->customer_name);
        $this->docform->customer->setKey($cust->customer_id);
        $this->docform->phone->setText($cust->phone);

        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->discount->setVisible(false);

        $this->docform->phone->setText($cust->phone);
    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function OnDelivery($sender) {
        $dt = $sender->getValue() ;
        if ($dt == Document::DEL_NP || $dt== Document::DEL_BOY || $dt == Document::DEL_SERVICE) {
            $this->docform->address->setVisible(true);
        } else {
            $this->docform->address->setVisible(false);
        }
    }

    public function OnChangePriceType($sender) {
        foreach ($this->_tovarlist as $item) {
            //$item = Item::load($item->item_id);
            $price = $item->getPrice($this->docform->pricetype->getValue());
            $item->price = $price;
        }
        $this->calcTotal();
        $this->docform->detail->Reload();
        $this->calcTotal();
    }

    public function onPayAmount($sender) {
        $this->docform->payamount->setText($this->docform->editpayamount->getText());

        $this->docform->payed->setText($this->docform->editpayamount->getText());

        $this->goAnkor("tankor");
    }

 

    public function onPayDisc() {
        $this->docform->paydisc->setText($this->docform->editpaydisc->getText());
        $this->docform->bonus->setText(0);
        $this->docform->editbonus->setText(0);
        
        $this->calcPay();
        $this->goAnkor("tankor");
    }
    public function onBonus() {
        $this->docform->bonus->setText($this->docform->editbonus->getText());
        $this->calcPay();
        $this->goAnkor("tankor");
    }

    private function calcPay() {
        $total = $this->docform->total->getText();
        $disc = $this->docform->paydisc->getText();
        $bonus = $this->docform->bonus->getText();

        if ($disc > 0) {
            $total -= $disc;
        }
        if ($bonus > 0) {
            $total -= $bonus;
        }

        

        $this->docform->editpayamount->setText(H::fa($total));
        $this->docform->payamount->setText(H::fa($total));

        $this->docform->payed->setText(H::fa($total));


    }


    public function onSelectItem($item_id, $itemname) {
        $this->editdetail->edittovar->setKey($item_id);
        $this->editdetail->edittovar->setText($itemname);
        $this->OnChangeItem($this->editdetail->edittovar);
    }

    public function onOpenItemSel($sender) {
        $this->wselitem->setVisible(true);
        $this->wselitem->setPriceType($this->docform->pricetype->getValue());
        $this->wselitem->Reload();
    }
    public function onOpenCatPan($sender) {
        $this->wselitem->setVisible(true);
        $this->wselitem->setPriceType($this->docform->pricetype->getValue());
        $this->wselitem->Reload(true);
    }

    //добавление нового товара
    public function addnewitemOnClick($sender) {
        $this->editnewitem->setVisible(true);
        $this->editdetail->setVisible(false);

        $this->editnewitem->clean();
        $this->editnewitem->editnewbrand->setDataList(Item::getManufacturers());
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

        if (strlen($item->item_code) > 0) {
            $code = Item::qstr($item->item_code);
            $cnt = Item::findCnt("  item_code={$code} ");
            if ($cnt > 0) {
                $this->setError('itemcode_exists');
                return;
            }

        } else {
            if (\App\System::getOption("common", "autoarticle") == 1) {

                $item->item_code = Item::getNextArticle();
            }
        }


        $item->manufacturer = $this->editnewitem->editnewbrand->getText();
        $item->cat_id = $this->editnewitem->editnewcat->getValue();
        $item->save();
        $this->editdetail->edittovar->setText($item->itemname);
        $this->editdetail->edittovar->setKey($item->item_id);

        $this->editnewitem->setVisible(false);
        $this->editdetail->setVisible(true);
    }

    public function cancelnewitemOnClick($sender) {
        $this->editnewitem->setVisible(false);
        $this->editdetail->setVisible(true);
    }
    
    public function getPriceByQty($args,$post=null)  {
        $item = Item::load($args[0]) ;
        $args[1] = str_replace(',','.',$args[1]) ;
        $price = $item->getPrice($this->docform->pricetype->getValue(), 0,0,$args[1]);
        
        return  $price;
        
    }    

     
    
}

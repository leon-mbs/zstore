<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\MoneyFund;
use App\Entity\Service;
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
use Zippy\Html\Panel;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use Zippy\Html\DataList\ArrayDataSource;

/**
 * Страница  ввода  оказанных услуг
 */
class IncomeService extends \App\Pages\Base
{

    public  $_servicelist = array();
    private $_doc;
    private $_rowid       = 0;
    private $_basedocid   = 0;
     
    public $_itemlist     = array();
    public $_itemset     = array();

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnCustomerFirm');

        $this->docform->add(new DropDownChoice('firm', \App\Entity\Firm::getList(), H::getDefFirm()))->onChange($this, 'OnCustomerFirm');
        $this->docform->add(new DropDownChoice('contract', array(), 0))->setVisible(false);;
        $this->docform->add(new DropDownChoice('mtype', \App\Entity\IOState::getTypeList(5)));

        $this->docform->add(new TextInput('notes'));
 

        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), 0));

        $this->docform->add(new TextInput('editpayamount'));
        $this->docform->add(new SubmitButton('bpayamount'))->onClick($this, 'onPayAmount');
        $this->docform->add(new TextInput('editpayed', "0"));
        $this->docform->add(new SubmitButton('bpayed'))->onClick($this, 'onPayed');

        $this->docform->add(new Label('payed', 0));
        $this->docform->add(new Label('payamount', 0));

        
        $this->docform->add(new TextInput('editpaydisc'));
        $this->docform->add(new SubmitButton('bpaydisc'))->onClick($this, 'onPayDisc');
        $this->docform->add(new Label('paydisc', 0));

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
     
        $this->docform->add(new Label('total'));
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new DropDownChoice('editservice', Service::findArray("service_name", "disabled<>1", "service_name")))->onChange($this, 'OnChangeServive', true);

        $this->editdetail->add(new TextInput('editqty'));
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextArea('editdesc'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');

        //добавление нового кантрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new TextInput('editemail'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        
        $this->add(new Panel('setpanel'))->setVisible(false);
        $this->setpanel->add(new DataView('setlist', new ArrayDataSource($this, '_itemlist'), $this, 'itemlistOnRow'));
        $this->setpanel->add(new Form('setform'))->onSubmit($this, 'OnAddSet');
        $this->setpanel->setform->add(new AutocompleteTextInput('editsname'))->onText($this, 'OnAutoSet');
        $this->setpanel->setform->add(new TextInput('editsqty', 1));
        $this->setpanel->setform->add(new TextInput('editsprice', 0));
        $this->setpanel->add(new Label('stitle'));
        $this->setpanel->add(new ClickLink('backtolist', $this, "onback"));
          
        
        
        if ($docid > 0) { //загружаем   содержимое   документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->notes->setText($this->_doc->notes);
         
            $this->docform->mtype->setValue($this->_doc->headerdata['mtype']);
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);
            $this->docform->payamount->setText($this->_doc->payamount);
            $this->docform->editpayamount->setText($this->_doc->payamount);
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);

            if ($this->_doc->payed == 0 && $this->_doc->headerdata['payed'] > 0) {
                $this->_doc->payed = $this->_doc->headerdata['payed'];
            }
         
           $this->docform->editpayed->setText(H::fa($this->_doc->payed));
            $this->docform->payed->setText(H::fa($this->_doc->payed));

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
            $this->_itemset = $this->_doc->unpackDetails('setdata');
        } else {
            $this->_doc = Document::create('IncomeService');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);

 
                if ($basedoc->meta_name == 'IncomeService') {
                    $this->docform->customer->setKey($basedoc->customer_id);
                    $this->docform->customer->setText($basedoc->customer_name);

                    $this->_servicelist = array();
                    foreach($basedoc->unpackDetails('detaildata') as $v ) {
                       $this->_servicelist[$v->service_id]= $v ;    
                    }
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_servicelist')), $this, 'detailOnRow'))->Reload();
        $this->calcTotal();
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function detailOnRow($row) {
        $service = $row->getDataItem();

        $row->add(new Label('item', $service->service_name));
        $row->add(new Label('desc', $service->desc));

        $row->add(new Label('qty', H::fqty($service->quantity)));
        $row->add(new Label('price', H::fa($service->price)));
        $row->add(new Label('amount', H::fa($service->price * $service->quantity)));

        $row->add(new ClickLink('iset'))->onClick($this, 'isetOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
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

        $this->_servicelist = array_diff_key($this->_servicelist, array($item->rowid => $this->_servicelist[$item->rowid]));
        $this->_itemset = array_diff_key($this->_itemset, array($item->rowid => $this->_itemset[$item->rowid]));

        $this->docform->detail->Reload();
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

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
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
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        }
      
        $this->_doc->headerdata['mtype'] = $this->docform->mtype->getValue();
        $this->_doc->headerdata['contract_id'] = $this->docform->contract->getValue();
        $this->_doc->firm_id = $this->docform->firm->getValue();
        if ($this->_doc->firm_id > 0) {
            $this->_doc->headerdata['firm_name'] = $this->docform->firm->getValueName();
        }

        $this->calcTotal();

        
        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();
        $this->_doc->headerdata['paydisc'] = $this->docform->paydisc->getText();

        $this->_doc->payamount = $this->docform->payamount->getText();
      
        $this->_doc->payed = $this->docform->payed->getText();

        $this->_doc->headerdata['payed'] = $this->docform->payed->getText();
        if ($this->checkForm() == false) {
            return;
        }

        $this->_doc->packDetails('detaildata', $this->_servicelist);
        $this->_doc->packDetails('setdata', $this->_itemset);

        $isEdited = $this->_doc->document_id > 0;
        $this->_doc->amount = $this->docform->total->getText();

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            if ($this->_basedocid > 0) {
                $this->_doc->parent_id = $this->_basedocid;
                $this->_basedocid = 0;
            }

            $this->_doc->save();

            if ($sender->id != 'savedoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }

                if ($sender->id == 'execdoc') {
                    $this->_doc->updateStatus(Document::STATE_EXECUTED);
                    $this->_doc->updateStatus(Document::STATE_CLOSED);
                     
                }

               
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }

            $conn->CommitTrans();
            App::Redirect("\\App\\Pages\\Register\\DocList");

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
        $this->docform->total->setText(H::fa($total));

 
    }


    public function onPayAmount($sender) {
        $this->docform->payamount->setText(H::fa($this->docform->editpayamount->getText()));
        $this->docform->payed->setText(H::fa($this->docform->editpayamount->getText()));
        $this->docform->editpayed->setText(H::fa($this->docform->editpayamount->getText()));
    }

    public function onPayed($sender) {
        $this->docform->payed->setText(H::fa($this->docform->editpayed->getText()));
    }

    private function CalcPay() {
        $total = $this->docform->total->getText();
        $disc = $this->docform->paydisc->getText();

        $this->docform->editpayamount->setText(H::fa($total - $disc));
        $this->docform->payamount->setText(H::fa($total - $disc));
        $this->docform->editpayed->setText(H::fa($total - $disc));
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

        if ($this->docform->payment->getValue() == 0 && $this->_doc->payed > 0) {
            $this->setError("noselmfp");
        }

        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 2);
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
    
    public function isetOnClick($sender) {
        $ser = $sender->owner->getDataItem();
        //$item = Item::load($item->item_id);
        $this->_rowid = $ser->rowid;
 
         
        if( is_array($this->_itemset)==false  ) $this->_itemset  = array() ;
        if( is_array($this->_itemset[$this->_rowid])==false  ) $this->_itemset[$this->_rowid]  = array() ;
        $this->_itemlist = $this->_itemset[$this->_rowid] ;
        $this->setpanel->setVisible(true);
        $this->docform->setVisible(false);

        $this->setpanel->stitle->setText($ser->service_name);

        $this->setupdate() ;    
    }    
    private function setupdate(){
       // $this->_itemset = ItemSet::find("item_id > 0  and pitem_id=" . $this->_pitem_id, "itemname");
    
        $this->setpanel->setlist->Reload();
        $this->_itemset[$this->_rowid] = $this->_itemlist   ;
 
          
    }    
    public function onback($sender) {
        $this->setpanel->setVisible(false);
        $this->docform->setVisible(true);
        
        $ser = $this->_servicelist[$this->_rowid];
        $a = 0;
        foreach($this->_itemlist   as $it) {
            $a  += doubleval($it->qty*$it->price) ;
        }
        if($ser->quantity*$ser->price  != $a  ) {
            $this->setWarn("seritemdiff") ;
        }
    }
  
    public function itemlistOnRow(\Zippy\Html\DataList\DataRow $row) {
        $item = $row->getDataItem();
        $row->add(new Label('sname', $item->itemname));
        $row->add(new Label('scode', $item->item_code));
        $row->add(new Label('sqty', H::fqty($item->qty)));
        $row->add(new Label('sprice', H::fa($item->price)));
        $row->add(new ClickLink('sdel'))->onClick($this, 'ondelset');
    }

 

    public function OnAddSet($sender) {
        $id = $sender->editsname->getKey();
        if ($id == 0) {
            $this->setError("noselitem");
            return;
        }
        $it = Item::load($id);
        $qty = $sender->editsqty->getText();
        $price = $sender->editsprice->getText();

        $set = new \App\DataItem();
     
        $set->item_id = $id;
        $set->qty = $qty;
        $set->price = $price;
        $set->itemname = $it->itemname;
        $set->item_code = $it->item_code;

        $this->_itemlist[$id]  =  $set;
        $this->setupdate() ;
        $sender->clean();
        
         
    }

    public function ondelset($sender) {
        $item = $sender->owner->getDataItem();

      $this->_itemlist = array_diff_key($this->_itemlist, array($item->item_id => $this->_itemlist[$item->item_id]));
       

        $this->setupdate() ;
    }
     
    public function OnAutoSet($sender) {
        $text = Item::qstr('%' . $sender->getText() . '%');
        $in = "(0"    ;
        foreach ($this->_itemlist as $is) {
            $in .= "," . $is->item_id;
        }

        $in .= ")";
        return Item::findArray('itemname', " item_type    in (4,5) and  item_id not in {$in} and (itemname like {$text} or item_code like {$text}) and disabled <> 1", 'itemname');
    }    
    
}

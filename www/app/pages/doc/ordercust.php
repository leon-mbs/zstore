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
use Zippy\Html\Form\CheckBox;
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
    public $_itemlist  = array();
    private $_doc;
    private $_basedocid = 0;


    /**
    * @param mixed $docid     редактирование
    * @param mixed $basedocid  создание на  основании
    */
    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $common = System::getOptions("common");

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new Date('delivery_date'));
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');
        $this->docform->addcust->setVisible(       \App\ACL::checkEditRef('CustomerList',false));

        $this->docform->add(new TextInput('notes'));

        $this->docform->add(new Label('total'));
         
        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('apprdoc'))->onClick($this, 'savedocOnClick');
        
        
           //добавление нового контрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

     

        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutoItem');
        $this->editdetail->edititem->onChange($this, 'OnChangeItem', true);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editcustcode'));
        $this->editdetail->add(new TextInput('editdesc'));
        $this->editdetail->add(new Label('qtystock'));
        $this->editdetail->add(new Button('canceledit'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('saveedit'))->onClick($this, 'saverowOnClick');
        $this->editdetail->add(new ClickLink('openitemsel', $this, 'onOpenItemSel'));
        $this->editdetail->add(new ClickLink('addnewitem', $this, 'onNewItem'));
    
        //добавление нового товара
        $this->add(new Form('editnewitem'))->setVisible(false);
        $this->editnewitem->add(new TextInput('editnewitemname'));
        $this->editnewitem->add(new TextInput('editnewitemcode'));

        $this->editnewitem->add(new TextInput('editnewitembrand'));
        $this->editnewitem->add(new TextInput('editnewitemmsr'));
        $this->editnewitem->add(new DropDownChoice('editnewcat', \App\Entity\Category::getList(), 0));
        $this->editnewitem->add(new Button('cancelnewitem'))->onClick($this, 'cancelnewitemOnClick');
        $this->editnewitem->add(new SubmitButton('savenewitem'))->onClick($this, 'savenewitemOnClick');
      
     
        $this->add(new \App\Widgets\ItemSel('wselitem', $this, 'onSelectItem'))->setVisible(false);     
      
        if ($docid > 0) {    //загружаем   содержимое  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->document_date->setDate($this->_doc->document_date);
            if($this->_doc->getHD('delivery_date',0) >0) {
              $this->docform->delivery_date->setDate($this->_doc->getHD('delivery_date'));
            }
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

        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');

        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
  }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 2);
    }    
    
    
    //добавление новый товар 
    public function onNewItem($sender) {
        $this->editnewitem->setVisible(true);
        $this->editdetail->setVisible(false);

        $this->editnewitem->clean();
        $this->editnewitem->editnewitembrand->setDataList(Item::getManufacturers());
        $this->editnewitem->editnewitemcode->setText( Item::getNextArticle());
      
    }
    
   public function cancelnewitemOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->editnewitem->setVisible(false);

      
        
    }
    
   public function savenewitemOnClick($sender) {
        $itemname = trim($this->editnewitem->editnewitemname->getText());
        if (strlen($itemname) == 0) {
            $this->setError("Не введено назву");
            return;
        }
        $item = new Item();
        $item->itemname = $itemname;

        $item->item_code = $this->editnewitem->editnewitemcode->getText();
        $item->manufacturer = $this->editnewitem->editnewitembrand->getText();
        $item->msr = $this->editnewitem->editnewitemmsr->getText();
        $item->cat_id = $this->editnewitem->editnewcat->getValue();
  
        if ($item->checkUniqueArticle()==false) {
              $this->setError('Такий артикул вже існує');
              return;
        }  

          
 
        $item->save();
        $this->editdetail->edititem->setText($item->itemname);
        $this->editdetail->edititem->setKey($item->item_id);

        $this->editnewitem->setVisible(false);
        $this->editdetail->setVisible(true);
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
            $this->setError("Не введено назву");
            return;
        }
        $cust = new Customer();
        $cust->customer_name = $custname;

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
    
    
    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->_rowid = -1;

        //очищаем  форму
        $this->editdetail->clean();
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        $this->editdetail->qtystock->setText("");
        $this->addJavaScript("$(\"#edititem\").focus()",true)  ;
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editprice->setText($item->price);


        $this->editdetail->editcustcode->setText($item->custcode);



        $this->editdetail->edititem->setKey($item->item_id);
        $this->editdetail->edititem->setText($item->itemname);

        $this->_rowid =  array_search($item, $this->_itemlist, true);

    }

 
    public function saverowOnClick($sender) {
        $common = System::getOptions("common");


        $id = $this->editdetail->edititem->getKey();
        $name = trim($this->editdetail->edititem->getText());
        if ($id == 0) {
            $this->setError("Не обрано товар");
            return;
        }


        $item = Item::load($id);

        $item->quantity = $this->editdetail->editquantity->getText();
        $item->price = $this->editdetail->editprice->getText();


        if ($item->price == 0) {

            $this->setWarn("Не вказана ціна");
        }
 

        $item->custcode = $this->editdetail->editcustcode->getText();



        if($this->_rowid == -1) {
            $this->_itemlist[] = $item;
            $this->addrowOnClick(null);
            $this->setInfo("Позиція додана") ;
        } else {
            $this->_itemlist[$this->_rowid] = $item;
            $this->cancelrowOnClick(null);
        }
  
 
        $this->docform->detail->Reload();
        $this->calcTotal();
 

    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->wselitem->setVisible(false);


    }
    
    public function deleteOnClick($sender) {
   
        $item = $sender->owner->getDataItem();

        $rowid =  array_search($item, $this->_itemlist, true);

        $this->_itemlist = array_diff_key($this->_itemlist, array($rowid=> $this->_itemlist[$rowid]));

 
        $this->docform->detail->Reload();        
        $this->calcTotal();

    }
   
    
    public function onOpenItemSel($sender) {
        $this->wselitem->setVisible(true);
        $this->wselitem->Reload();
    }  
    
    
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

  public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $item = Item::load($id);
     
        $qty = $item->getQuantity();
    
        $this->editdetail->qtystock->setText(H::fqty($qty));

        $price = $item->getLastPartion(0, "", true);
        $this->editdetail->editprice->setText(H::fa($price));


    }

    public function OnAutoItem($sender) {

        $text = trim($sender->getText());
        return Item::findArrayAC($text);
    }
    
   public function onSelectItem($item_id, $itemname, $price=null) {
        $this->editdetail->edititem->setKey($item_id);
        $this->editdetail->edititem->setText($itemname);
        $this->OnChangeItem($this->editdetail->edititem);
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
        $this->_doc->headerdata['delivery_date'] = $this->docform->delivery_date->getDate();

 
        if ($this->checkForm() == false) {
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
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }
                $this->_doc->updateStatus(Document::STATE_INPROCESS);
           
            } else {

               if ($sender->id ==  'apprdoc') {
                    $this->_doc->headerdata['_state_before_approve_'] = ''.Document::STATE_APPROVED; 
                    $this->_doc->save();

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
            $logger->error('Line '. $ee->getLine().' '.$ee->getFile().'. '.$ee->getMessage()  );

            return;
        }

        if (false == \App\ACL::checkShowReg('GRList', false)) {
            App::RedirectHome() ;
        } else {
            App::Redirect("\\App\\Pages\\Register\\OrderCustList");
        }

    }
    
    


     public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

}

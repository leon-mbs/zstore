<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
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
 * Страница  ввода  счета  от  поставщика
 */
class InvoiceCust extends \App\Pages\Base
{
    public $_itemlist  = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid     = -1;

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
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnCustomerFirm');
     
        $this->docform->add(new DropDownChoice('contract', array(), 0))->setVisible(false);

        $this->docform->add(new TextInput('notes'));

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new DropDownChoice('payment', \App\Entity\MoneyFund::getList(), H::getDefMF()));

        $this->docform->add(new TextInput('editpayamount', "0"));
        $this->docform->add(new SubmitButton('bpayamount'))->onClick($this, 'onPayAmount');
        $this->docform->add(new TextInput('editpayed', "0"));
        $this->docform->add(new SubmitButton('bpayed'))->onClick($this, 'onPayed');

        $this->docform->add(new TextInput('editnds', "0"));
        $this->docform->add(new SubmitButton('bnds'))->onClick($this, 'onNds');


        $this->docform->add(new TextInput('editdisc', "0"));
        $this->docform->add(new SubmitButton('bdisc'))->onClick($this, 'onDisc');

        $this->docform->add(new Label('nds', 0));
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
        $this->editdetail->add(new TextInput('editcustcode'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');
 
        //добавление нового товара
        $this->add(new Form('editnewitem'))->setVisible(false);
        $this->editnewitem->add(new TextInput('editnewitemname'));
        $this->editnewitem->add(new TextInput('editnewitemcode'));
        $this->editnewitem->add(new TextInput('editnewitemmsr'));
        $this->editnewitem->add(new Button('cancelnewitem'))->onClick($this, 'cancelnewitemOnClick');
        $this->editnewitem->add(new DropDownChoice('editnewcat', \App\Entity\Category::getList(), 0));
        $this->editnewitem->add(new SubmitButton('savenewitem'))->onClick($this, 'savenewitemOnClick');
        $this->editdetail->add(new ClickLink('openitemsel', $this, 'onOpenItemSel'));
 
        $this->add(new \App\Widgets\ItemSel('wselitem', $this, 'onSelectItem'))->setVisible(false);
  
 
        if ($docid > 0) {    //загружаем   содержимое  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->payamount->setText($this->_doc->payamount);
            $this->docform->editpayamount->setText($this->_doc->payamount);
       
            $this->docform->editpayed->setText($this->_doc->headerdata['payed']);
            $this->docform->payed->setText($this->_doc->headerdata['payed']);

            $this->docform->nds->setText($this->_doc->headerdata['nds']);
            $this->docform->editnds->setText($this->_doc->headerdata['nds']);
            $this->docform->disc->setText($this->_doc->headerdata['disc']);
            $this->docform->editdisc->setText($this->_doc->headerdata['disc']);

            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);

            $this->OnCustomerFirm($this->docform->customer);

            $this->docform->contract->setValue($this->_doc->headerdata['contract_id']);

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
        $row->add(new Label('pricends', H::fa($item->pricends)));

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
        $this->editdetail->editcustcode->setText($item->custcode);

        $this->editdetail->edititem->setKey($item->item_id);
        $this->editdetail->edititem->setText($item->itemname);

        $this->_rowid =  array_search($item, $this->_itemlist, true);

    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $item = $sender->owner->getDataItem();


        $rowid =  array_search($item, $this->_itemlist, true);

        $this->_itemlist = array_diff_key($this->_itemlist, array($rowid => $this->_itemlist[$rowid]));

        $this->docform->detail->Reload();

        $this->calcTotal();
        $this->calcPay();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->_rowid = -1;
        $this->editdetail->editprice->setText("0");
        $this->editdetail->editcustcode->setText("");
        $this->wselitem->setVisible(false);
        
    }

    public function saverowOnClick($sender) {

        $id = $this->editdetail->edititem->getKey();
        $name = trim($this->editdetail->edititem->getText());
        if ($id == 0) {
            $this->setError("Не обрано товар");
            return;
        }

        $item = Item::load($id);

        $item->quantity = $this->editdetail->editquantity->getDouble();
        $item->price = $this->editdetail->editprice->getDouble();
        $item->custcode = $this->editdetail->editcustcode->getText();
        if ($item->price == 0) {
            $this->setWarn("Не вказана ціна");
        }
        $item->pricends= $item->price + $item->price * $item->nds();
 
        if($this->_rowid == -1) {
            $this->_itemlist[] = $item;
        } else {
            $this->_itemlist[$this->_rowid] = $item;
        }


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
        $this->editdetail->editcustcode->setText("");
        $this->wselitem->setVisible(false);
        
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->wselitem->setVisible(false);        
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }


        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->payamount = $this->docform->payamount->getText();

        $this->_doc->amount = doubleval($this->docform->total->getText());
        $this->_doc->payed = doubleval($this->docform->payed->getText());
        $this->_doc->headerdata['payed'] = $this->_doc->payed;

        $this->_doc->headerdata['nds'] = $this->docform->nds->getText();
        $this->_doc->headerdata['disc'] = $this->docform->disc->getText();


        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->_doc->customer_id > 0) {
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        }
        $this->_doc->headerdata['contract_id'] = $this->docform->contract->getValue();
     

        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();

        if ($this->checkForm() == false) {
            return;
        }
        if ($this->_doc->payed == 0) {
            $this->_doc->headerdata['payment'] = 0;
        }

        $file = $this->docform->scan->getFile();
        if ($file['size'] > 10000000) {
            $this->setError("Файл більше 10 МБ!");
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

                $this->_doc->updateStatus(Document::STATE_EXECUTED);
                if ($this->_doc->payamount > $this->_doc->payed) {
                    $this->_doc->updateStatus(Document::STATE_WP);
                }
            
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }


            if ($file['size'] > 0) {
                H::addFile($file, $this->_doc->document_id, 'Скан', \App\Entity\Message::TYPE_DOC);
                $id = H::addFile($file, $this->_doc->document_id, 'Скан', \App\Entity\Message::TYPE_DOC);
                $imagedata = getimagesize($file["tmp_name"]);
                if ($imagedata[0] > 0) {
                    $this->_doc->headerdata["scan"] = $id;
                    $this->_doc->save();
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
        App::Redirect("\\App\\Pages\\Register\\GRList");

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
        $nds = 0;

        foreach ($this->_itemlist as $item) {
            $item->amount = $item->price * $item->quantity;
            $total = $total + $item->amount;
            if($item->pricends > $item->price) {
                $nds = $nds + doubleval($item->pricends-$item->price) * $item->quantity;                
            }
        }
        $this->docform->total->setText(H::fa($total));
        if($this->_tvars['usends'] != true) {
           $nds=0; 
        }
   
        if($nds>0) {
            $this->docform->nds->setText(H::fa($nds));            
        }
    }

    private function CalcPay() {
        $total = $this->docform->total->getText();


        $nds = $this->docform->nds->getText();
        $disc = $this->docform->disc->getText();
        $total = $total + $nds - $disc;


        $this->docform->editpayamount->setText(H::fa($total));
        $this->docform->payamount->setText(H::fa($total));
        $this->docform->editpayed->setText(H::fa($total));
        $this->docform->payed->setText(H::fa($total));
    }

    public function onDisc($sender) {
        $this->docform->disc->setText(H::fa($this->docform->editdisc->getDouble()));
        $this->CalcPay();
        $this->goAnkor("tankor");
    }

    public function onNds($sender) {
        $this->docform->nds->setText(H::fa($this->docform->editnds->getDouble()));
        $this->CalcPay();
        $this->goAnkor("tankor");
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
        if ($this->docform->payment->getValue() == 0 && $this->_doc->payed > 0) {
            $this->setError("Якщо внесена сума більше нуля, повинна бути обрана каса або рахунок");
        }
 

        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutoItem($sender) {

        $text = Item::qstr('%' . $sender->getText() . '%');
        return  Item::findArray('itemname', "(itemname like {$text} or item_code like {$text} or bar_code like {$text})  and disabled <> 1");

    }

    public function OnAutoCustomer($sender) {

        return Customer::getList($sender->getText(), 2);
    }

    //добавление нового товара
    public function addnewitemOnClick($sender) {
        $this->editnewitem->setVisible(true);
        $this->editdetail->setVisible(false);
        $this->editnewitem->editnewitemmsr->setText('');

        $this->editnewitem->editnewitemname->setText('');
        $this->editnewitem->editnewitemcode->setText( Item::getNextArticle());
        
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
        $item->msr = $this->editnewitem->editnewitemmsr->getText();

        if ($item->checkUniqueArticle()==false) {
              $this->setError('Такий артикул вже існує');
              return;
        }  

     


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
 
    public function onOpenItemSel($sender) {
  
        $this->wselitem->setVisible(true);
        $this->wselitem->Reload();
    }

    public function onSelectItem($item_id, $itemname) {
        $this->editdetail->edititem->setKey($item_id);
        $this->editdetail->edititem->setText($itemname);
        $item = Item::load($item_id);

        $price = $item->getLastPartion(0, "", true,'GoodsReceipt');
     
        $this->editdetail->editprice->setText(H::fa($price));
        
    }

}

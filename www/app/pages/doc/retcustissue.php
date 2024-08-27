<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\MoneyFund;
use App\Entity\Store;
use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода  возврат  поставщику
 */
class RetCustIssue extends \App\Pages\Base
{
    public $_itemlist  = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid     = 0;

    /**
    * @param mixed $docid     редактирование
    * @param mixed $basedocid  создание на  основании
    */
    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();
        if ($docid == 0 && $basedocid == 0) {

            $this->setWarn('Повернення слід створювати на основі прибуткової накладної');
        }

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));

        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), H::getDefMF()));

        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new CheckBox('comission', 0));

        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->docform->add(new Label('total'));

        $this->docform->add(new TextInput('editpayed', "0"));
        $this->docform->add(new SubmitButton('bpayed'))->onClick($this, 'onPayed');
        $this->docform->add(new Label('payed', 0));

        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));

        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');
        $this->editdetail->edittovar->onChange($this, 'OnChangeItem', true);

        $this->editdetail->add(new Label('qtystock'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->document_date->setDate($this->_doc->document_date);

            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);
            $this->docform->total->setText(H::fa($this->_doc->amount));
            if ($this->_doc->payed == 0 && $this->_doc->headerdata['payed'] > 0) {
                $this->_doc->payed = $this->_doc->headerdata['payed'];
            }
            $this->docform->comission->setChecked($this->_doc->headerdata['comission']);

            $this->docform->editpayed->setText(H::fa($this->_doc->payed));
            $this->docform->payed->setText(H::fa($this->_doc->payed));

            $this->docform->notes->setText($this->_doc->notes);

            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('RetCustIssue');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;

                    if ($basedoc->meta_name == 'GoodsReceipt') {
                        $this->docform->store->setValue($basedoc->headerdata['store']);
                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);
                        $this->docform->comission->setChecked($basedoc->headerdata['comission']);
                        $rate = $basedoc->headerdata["rate"] ?? '';
                        $rate = $rate == '' ? 1 : doubleval($rate) ;  
  
                        $this->_itemlist = [];
                        foreach($basedoc->unpackDetails('detaildata') as $i){
                            $i->price = $i->price * $rate;
                            $this->_itemlist[]=$i;
                        }


                    }
                }
                $this->calcTotal();
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('tovar', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? H::fd($item->sdate) : ''));

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
        $rowid =  array_search($item, $this->_itemlist, true);

        $this->_itemlist = array_diff_key($this->_itemlist, array($rowid => $this->_itemlist[$rowid]));

        $this->docform->detail->Reload();
        $this->calcTotal();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("0");
        $this->editdetail->qtystock->setText("");
        $this->docform->setVisible(false);
        $this->_rowid = -1;
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editprice->setText($item->price);

        $this->editdetail->edittovar->setKey($item->item_id);
        $this->editdetail->edittovar->setText($item->itemname);

        $qty = $item->getQuantity();
        $this->editdetail->qtystock->setText(H::fqty($qty));

        $this->_rowid =  array_search($item, $this->_itemlist, true);

    }

    public function saverowOnClick($sender) {

        $id = $this->editdetail->edittovar->getKey();
        if ($id == 0) {
            $this->setError("Не обрано товар");
            return;
        }

        $item = Item::load($id);


        $item->quantity = $this->editdetail->editquantity->getText();

        $item->price = $this->editdetail->editprice->getText();

        if($this->_rowid == -1) {
            $this->_itemlist[] = $item;
        } else {
            $this->_itemlist[$this->_rowid] = $item;
        }

        $this->docform->detail->Reload();
        $this->calcTotal();
        
        
         $this->docform->setVisible(true);
         $this->editdetail->setVisible(false);

    }

    public function cancelrowOnClick($sender) {
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
        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();
        $this->_doc->headerdata['comission'] = $this->docform->comission->isChecked() ? 1:0;

        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->_doc->customer_id > 0) {
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        }

        //  $this->calcTotal();
        $firm = H::getFirmData($this->_doc->firm_id, $this->branch_id);
        $this->_doc->headerdata["firm_name"] = $firm['firm_name'];

        $this->_doc->headerdata['store'] = $this->docform->store->getValue();

        $this->_doc->packDetails('detaildata', $this->_itemlist);
        if ($this->_doc->payed == 0 && $this->_doc->headerdata['payed'] > 0) {
            $this->_doc->payed = $this->_doc->headerdata['payed'];
        }


        $this->_doc->amount = $this->docform->total->getText();
        $this->_doc->payamount = $this->docform->total->getText();
      

        $this->_doc->payed = doubleval($this->docform->payed->getText());
        $this->_doc->headerdata['payed'] = $this->_doc->payed;

        if ($this->checkForm() == false) {
            return;
        }

        if ($this->_doc->payed == 0) {
            $this->_doc->headerdata['payment'] = 0;
        }


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
                if ($this->_doc->payamount > $this->_doc->payed && $this->_doc->headerdata['comission'] != 1) {
                    $this->_doc->updateStatus(Document::STATE_WP);
                }

            } else {

                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }

            $conn->CommitTrans();

            App::Redirect("\\App\\Pages\\Register\\GIList");

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

        $payed=  $total;
        
        if($this->_basedocid >0) {
            $parent = Document::load($this->_basedocid) ;
            
            $payed = $parent->payamount;
            
            
            $k = 1 - ($parent->amount - $total) / $parent->amount;
 
            $payed  = $payed*$k;           
        }        

        
        $this->docform->total->setText(H::fa($total));
        $this->docform->payed->setText(H::fa($payed));
        $this->docform->editpayed->setText(H::fa($payed));
    }

    public function onPayed($sender) {
        $this->docform->payed->setText(H::fa($this->docform->editpayed->getText()));
        $payed = $this->docform->payed->getText();
        $total = $this->docform->total->getText();
        if ($payed > $total) {
            $this->setWarn('Внесена сума більше необхідної');
        } else {
            $this->goAnkor("tankor");
        }
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
        if ($this->docform->payment->getValue() == 0 && $this->_doc->payed > 0) {
            $this->setError("Якщо внесена сума більше нуля, повинна бути обрана каса або рахунок");
        }
        if ($this->_doc->headerdata['comission']==1 && $this->_doc->payed > 0) {
            $this->setError("Оплата не  вноситься якщо Комісія ");
        }

        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }


    public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $item = Item::load($id);

        $this->editdetail->qtystock->setText(H::fqty($item->getQuantity()));
        $cid = $this->docform->customer->getKey();

        $where = " document_id  in (select document_id from  documents_view where  meta_name='GoodsReceipt') and item_id=" . $id;

        if ($id > 0) {
            $where .= " and  customer_id= {$cid} ";
        }


        $e = \App\Entity\Entry::getFirst($where, "entry_id desc");

        $this->editdetail->editprice->setText(H::fa($e->partion));



    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 2);
    }

    public function OnAutoItem($sender) {
        $text = trim($sender->getText());
        return Item::findArrayAC($text);
    }

}

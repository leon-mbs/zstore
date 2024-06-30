<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\MoneyFund;

use App\Helper as H;
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
 * Страница  ввода  оплата  комитенту
 */
class PayComitent extends \App\Pages\Base
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

            $this->setWarn('Оплату слід створювати на основі прибуткової накладної');
        }

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), H::getDefMF()));

        $clist= Customer::findArray("customer_name","status=0 and customer_id in (select customer_id from documents_view where  meta_name='GoodsReceipt' and state=5 and  content like '%<comission>1</comission>%'  )","customer_name") ;
        $ilist= Item::findArray("itemname","item_id in (select item_id from store_stock where  customer_id in  (select customer_id from documents_view where  meta_name='GoodsReceipt' and state=5 and  content like '%<comission>1</comission>%'  ) ) ","itemname") ;
        
        $this->docform->add(new DropDownChoice('customer',$clist,0 ));
        $this->docform->add(new DropDownChoice('item',$ilist,0 ));
        $this->docform->add(new TextInput('iqty'));
        $this->docform->add(new TextInput('iprice'));

        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'saverowOnClick');

        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->docform->add(new Label('total'));

        $this->docform->add(new TextInput('editpayed', "0"));
        $this->docform->add(new SubmitButton('bpayed'))->onClick($this, 'onPayed');
        $this->docform->add(new Label('payed', 0));

 
        if ($docid > 0) {    //загружаем   содержимое  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->document_date->setDate($this->_doc->document_date);


            $this->docform->customer->setValue($this->_doc->customer_id);

            $this->docform->total->setText(H::fa($this->_doc->amount));
            if ($this->_doc->payed == 0 && $this->_doc->headerdata['payed'] > 0) {
                $this->_doc->payed = $this->_doc->headerdata['payed'];
            }

            $this->docform->editpayed->setText(H::fa($this->_doc->payed));
            $this->docform->payed->setText(H::fa($this->_doc->payed));

            $this->docform->notes->setText($this->_doc->notes);

            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('PayComitent');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;

                    if ($basedoc->meta_name == 'GoodsReceipt') {

                        $this->docform->customer->setValue($basedoc->customer_id);

                        $this->_itemlist = $basedoc->unpackDetails('detaildata');


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

 

 
    public function saverowOnClick($sender) {

        $id = $this->docform->item->getValue();
        if ($id == 0) {
            $this->setError("Не обрано товар");
            return;
        }

        $item = Item::load($id);


        $item->quantity = doubleval($this->docform->iqty->getText() );

        $item->price =  doubleval($this->docform->iprice->getText() );

        if($item->quantity == 0  && $item->price == 0 )  {
            return;
        }
        
        $this->_itemlist[] = $item;
  
        $this->docform->detail->Reload();
        $this->calcTotal();
        
        


    }

 

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();

        $this->_doc->customer_id = $this->docform->customer->getValue();
        if ($this->_doc->customer_id > 0) {
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getValueName();
        }

        //  $this->calcTotal();
        $firm = H::getFirmData($this->_doc->firm_id, $this->branch_id);
        $this->_doc->headerdata["firm_name"] = $firm['firm_name'];


        $this->_doc->packDetails('detaildata', $this->_itemlist);
        if ($this->_doc->payed == 0 && $this->_doc->headerdata['payed'] > 0) {
            $this->_doc->payed = $this->_doc->headerdata['payed'];
        }



        $this->_doc->amount = $this->docform->total->getText();
        $this->_doc->payamount = 0;

        $this->_doc->payed = $this->docform->payed->getText();
        $this->_doc->headerdata['payed'] = $this->docform->payed->getText();

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
            $this->_doc->save();
            if ($sender->id == 'execdoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }

                $this->_doc->updateStatus(Document::STATE_EXECUTED);
           

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

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_name);
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
 
        
        $this->docform->total->setText(H::fa($total));
        $this->docform->payed->setText(H::fa($payed));
        $this->docform->editpayed->setText(H::fa($payed));
    }

    public function onPayed($sender) {
        $this->docform->payed->setText(H::fa($this->docform->editpayed->getText()));
 
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
        if ( $this->_doc->payed == 0) {
            $this->setError("Не внесена сума");
        }

        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

 

}

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
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 *  возвратная накладная
 */
class ReturnIssue extends \App\Pages\Base
{
    public $_itemlist = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid     = -1;

    /**
    * @param mixed $docid     редактирование
    * @param mixed $basedocid  создание на  основании
    */
    public function __construct($docid = 0, $basedocid = 0,$pos_id=0) {
        parent::__construct();
        if ($docid == 0 && $basedocid == 0) {

            $this->setWarn('Повернення слід створювати на основі видаткової накладної або чека');
        }
        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));

        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');

        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), 0));

        $this->docform->add(new DropDownChoice('pos', \App\Entity\Pos::findArray('pos_name', "details like '%<usefisc>1</usefisc>%' or details like '%<usefreg>1</usefreg>%'  "), 0))->setVisible($this->_tvars['fiscal']==1 || $this->_tvars['freg']==1 );

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->docform->add(new Label('totalnds'));
        $this->docform->add(new Label('total'));
        $this->docform->add(new Label('discount'));
        $this->docform->add(new Label('bonus'));
        $this->docform->add(new Label('payamount'));
        $this->docform->add(new TextInput('editpayed', "0"));
        $this->docform->add(new SubmitButton('bpayed'))->onClick($this, 'onPayed');
        $this->docform->add(new Label('payed', 0));

        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));

        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->document_date->setDate($this->_doc->document_date);

            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);

            $this->docform->editpayed->setText(H::fa($this->_doc->headerdata['payed']));
            $this->docform->payed->setText(H::fa($this->_doc->headerdata['payed']));

            $this->docform->total->setText(H::fa($this->_doc->amount));
            $this->docform->payamount->setText(H::fa($this->_doc->payamount));

            $this->_itemlist = $this->_doc->unpackDetails('detaildata');

            $this->_basedocid = $this->_doc->parent_id;




        } else {
            $this->_doc = Document::create('ReturnIssue');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;

                    $d = $basedoc->getChildren('ReturnIssue');

                    if (count($d) > 0) {

                        $this->setError('Вже існує документ Повернення');
                        App::Redirect("\\App\\Pages\\Register\\DocList");
                        return;
                    }


                    if ($basedoc->meta_name == 'GoodsIssue') {
                        $this->docform->store->setValue($basedoc->headerdata['store']);
                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $this->_itemlist = $basedoc->unpackDetails('detaildata');


                    }
                    if ($basedoc->meta_name == 'TTN') {
                        $this->docform->store->setValue($basedoc->headerdata['store']);
                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $this->_itemlist = $basedoc->unpackDetails('detaildata');


                    }
                    if ($basedoc->meta_name == 'POSCheck') {
                        $this->docform->store->setValue($basedoc->headerdata['store']);
                        $this->docform->pos->setValue($basedoc->headerdata['pos']);
                        if($pos_id >0) {
                            $this->docform->pos->setValue($pos_id);                            
                        }
                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);

                        $itemlist = $basedoc->unpackDetails('detaildata');

                        $this->_itemlist = array();
                        foreach ($itemlist as $item) {
                            if($item->item_id >0) {
                                $this->_itemlist[] = $item;
                            }

                        }



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

        $row->add(new Label('tovar', $item->itemname));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->snumber > 0 ? ($item->sdate > 0 ? H::fd($item->sdate) : '') : ''));

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
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');
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
        $this->_rowid =  array_search($item, $this->_itemlist, true);
    }

    public function saverowOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $id = $this->editdetail->edittovar->getKey();
        if ($id == 0) {
            $this->setError("Не обрано товар");
            return;
        }



        $item = Item::load($id);

        $item->quantity = $this->editdetail->editquantity->getDouble();

        $item->price = $this->editdetail->editprice->getDouble();
    
        $item->pricenonds= $item->price - $item->price * $item->nds(true);
     
        if($this->_rowid == -1) {
            $this->_itemlist[] = $item;
        } else {
            $this->_itemlist[$this->_rowid] = $item;
        }
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();

        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        $this->calcTotal();
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
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->_doc->customer_id > 0) {
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        }

        $this->_doc->headerdata['nds'] = $this->docform->totalnds->getText();
    
        $firm = H::getFirmData(  $this->branch_id);
        $this->_doc->headerdata["firm_name"] = $firm['firm_name'];

        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();

        $this->_doc->packDetails('detaildata', $this->_itemlist);

        $this->_doc->amount = $this->docform->total->getText();
        $this->_doc->payamount = $this->docform->payamount->getText();

        $this->_doc->payed = doubleval($this->docform->payed->getText());
        $this->_doc->headerdata['payed'] = $this->_doc->payed;
        $this->_doc->headerdata['bonus'] = $this->docform->bonus->getText();
        $this->_doc->headerdata['discount'] = $this->docform->discount->getText();

        if ($this->checkForm() == false) {
            return;
        }
        
        
        $isEdited = $this->_doc->document_id > 0;

        $pos_id = $this->docform->pos->getValue();

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
                if ($this->_doc->payamount > $this->_doc->payed) {
                    $this->_doc->updateStatus(Document::STATE_WP);
                }

                $this->_doc->updateStatus(Document::STATE_EXECUTED);
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }
  
            
            
            if ($pos_id > 0 && $sender->id == 'execdoc') {
                $pos = \App\Entity\Pos::load($pos_id);
                if($pos->usefreg == 1) {
                    $this->_doc->headerdata["passfisc"] = 1;
                    $this->_doc->save();
                }    
                if($pos->usefisc == 1)  {
                     
                    if( $this->_tvars['checkbox'] == true) {

                        $cb = new  \App\Modules\CB\CheckBox($pos->cbkey, $pos->cbpin) ;
                        $ret = $cb->Check($this->_doc) ;

                        if(is_array($ret)) {
                            $this->_doc->headerdata["fiscalnumber"] = $ret['fiscnumber'];
                            $this->_doc->headerdata["tax_url"] = $ret['tax_url'];
                            $this->_doc->headerdata["checkbox"] = $ret['checkid'];
                        } else {

                            throw new \Exception($ret);

                        }


                    }
                    if( $this->_tvars['vkassa'] == true) {
                        $vk = new  \App\Modules\VK\VK($pos->vktoken) ;
                        $ret = $vk->Check($this->_doc) ;

                        if(is_array($ret)) {
                            $this->_doc->headerdata["fiscalnumber"] = $ret['fiscnumber'];
                        } else {

                            throw new \Exception($ret);

                        }         
     
                    }
                    if ( $this->_tvars['ppo'] == true) {
                        $this->_doc->headerdata["fiscalnumberpos"]  =  $pos->fiscalnumber;

                        if ($this->_doc->parent_id > 0) {
                            $basedoc = Document::load($this->_doc->parent_id);
                            $this->_doc->headerdata["docnumberback"] = $basedoc->headerdata["fiscalnumber"];
                        }

                        if (strlen($this->_doc->headerdata["docnumberback"]) == 0) {

                            throw new \Exception("Для фіскалізації створіть повернення на основі фіскального чека");
                        }

                        $this->_doc->headerdata["pos"] = $pos->pos_id;

                        $ret = \App\Modules\PPO\PPOHelper::checkback($this->_doc);
                        if ($ret['success'] == false && $ret['doclocnumber'] > 0) {
                            //повторяем для  нового номера
                            $pos->fiscdocnumber = $ret['doclocnumber'];
                            $pos->save();
                            $ret = \App\Modules\PPO\PPOHelper::checkback($this->_doc);
                        }
                        if ($ret['success'] == false) {

                            throw new \Exception($ret['data']);

                        } else {

                            if ($ret['docnumber'] > 0) {
                                $pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                                $pos->save();
                                $this->_doc->headerdata["fiscalnumber"] = $ret['docnumber'];
                                $this->_doc->headerdata["fiscalamount"] = $ret['fiscalamount'];
                                $this->_doc->headerdata["fiscaltest"] = $ret['fiscaltest'];
                            } else {

                                throw new \Exception("Не повернено фіскальний номер");
                            }
                        }
                    }
                
                    $this->_doc->save();    
                }
                
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
        if($pos !=null) {
            if( $pos->usefreg == 1) {
               $this->addJavaScript("fiscFR({$this->_doc->document_id})",true) ;
            }         
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
            $item->amount = $item->price * $item->quantity;
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
 
        $payamount= $total + $nds ;

        if($this->_basedocid >0) {
            $parent = Document::load($this->_basedocid) ;
            $k=1;
            if($parent->amount >0) {
                $k = 1 - ($parent->amount - $total) / $parent->amount;               
            }

            $parentbonus = intval($parent->getBonus(false));   //списано
            if($parentbonus >0) {
               $retbonus = intval($parentbonus * $k) ;// доля
               $this->docform->bonus->setText($retbonus);
               $payamount -= $retbonus;
            }
            
            if($parent->headerdata["totaldisc"] >0) {
               $disc= H::fa($parent->headerdata["totaldisc"] * $k);
               $this->docform->discount->setText($disc);
               $payamount -= $disc;
            }
            
            
        }
        
        
        $this->docform->payamount->setText(H::fa($payamount));
        //  $this->docform->discount->setText(H::fa($discount));
        $this->docform->payed->setText(H::fa($payamount));
        $this->docform->editpayed->setText(H::fa($payamount));
    }

    public function onPayed($sender) {
        $this->docform->payed->setText(H::fa($this->docform->editpayed->getDouble()));
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
        $c = $this->docform->customer->getKey();
        if ($this->_doc->amount > 0 && $this->_doc->payamount > $this->_doc->payed && $c == 0) {
            $this->setError("Якщо у борг або передоплата або нарахування бонусів має бути обраний контрагент");
        }


        if ($this->docform->payment->getValue() == 0 && $this->_doc->payed > 0) {
            $this->setError("Якщо внесена сума більше нуля, повинна бути обрана каса або рахунок");
        }


        //проверка  что не  поменялась  цена
        $base = Document::load($this->_basedocid);
        if ($base instanceof Document) {
            $base = $base->cast();
            $bt = $base->unpackDetails('detaildata');

            if (is_array($bt)) {

                foreach ($this->_itemlist as $t) {
                    $ok = false;
                    foreach ($bt as $b) {
                        if ($b->item_id == $t->item_id && $b->price == $t->price) {
                            $ok = true;
                            break;
                        }
                    }
                    if ($ok == false) {
                        $this->setError("Повернення має відповідати проданим товарам за тією самою ціною");
                        break;
                    }

                }
            }
        }


        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 1);
    }

    public function OnAutoItem($sender) {

        $text = trim($sender->getText());
        return Item::findArrayAC($text);
    }

}


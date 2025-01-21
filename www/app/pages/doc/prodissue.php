<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
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
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода   списание  на производство
 */
class ProdIssue extends \App\Pages\Base
{
    public $_itemlist  = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid = -1;

    /**
    * @param mixed $docid      редактирование
    * @param mixed $basedocid  создание на  основании
    * @param mixed $st_id      производственный  этап
    */
    public function __construct($docid = 0, $basedocid = 0, $st_id = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));
        $this->docform->add(new DropDownChoice('parea', \App\Entity\ProdArea::findArray("pa_name", ""), 0));
        $this->docform->add(new TextArea('notes'));

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');

        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->docform->add(new TextInput('barcode'));
        $this->docform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');

        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");

        $this->editdetail->add(new TextInput('editserial'));

        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');
        $this->editdetail->edittovar->onChange($this, 'OnChangeItem', true);

        $this->editdetail->add(new Label('qtystock'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->document_date->setDate($this->_doc->document_date);

            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->parea->setValue($this->_doc->headerdata['parea']);

            $this->docform->notes->setText($this->_doc->notes);
            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('ProdIssue');
            $this->docform->document_number->setText($this->_doc->nextNumber());
            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'Task') {

                        $this->docform->notes->setText('Підстава ' . $basedoc->document_number);
                        $this->docform->parea->setValue($basedoc->headerdata['parea']);
                       //комплекты
                        foreach($basedoc->unpackDetails('prodlist') as $prod) {
                            $set =  \App\Entity\ItemSet::find("item_id > 0  and pitem_id=" . $prod->item_id);
                            foreach($set as $m) {
                                if(!isset($this->_itemlist[$m->item_id])) {

                                    $this->_itemlist[$m->item_id] = Item::load($m->item_id);
                                    $this->_itemlist[$m->item_id]->quantity = 0;
                                }
                                $this->_itemlist[$m->item_id]->quantity += ($prod->quantity * $m->qty);


                            }

                           // $this->_itemlist = array_values($this->_itemlist) ;
                        }
                        //работы
                        foreach($basedoc->unpackDetails('detaildata') as $s) {
                            $ser = \App\Entity\Service::load($s->service_id);
                            if(!is_array($ser->itemset)) {
                                continue;
                            }   
                            foreach($ser->itemset as $m) {
                                $itemp = \App\Entity\Item::load($m->item_id);
                                if($itemp == null) {
                                    continue;
                                }
                                $itemp->quantity = $s->quantity * $m->qty;
                                
                                if(!isset($this->_itemlist[$itemp->item_id])) {

                                    $this->_itemlist[$itemp->item_id] = Item::load($itemp->item_id);
                                    $this->_itemlist[$itemp->item_id]->quantity = $itemp->quantity;
                                }
                                $this->_itemlist[$itemp->item_id]->quantity +=  $itemp->quantity;


                            }

                          //  $this->_itemlist = array_values($this->_itemlist) ;
                        }



                    }
                    if ($basedoc->meta_name == 'ServiceAct') {

                        $this->docform->notes->setText('Підстава ' . $basedoc->document_number);
                    }
                    if ($basedoc->meta_name == 'ProdIssue') {
                        $this->docform->store->setValue($basedoc->headerdata['store']);
                        $this->docform->parea->setValue($basedoc->headerdata['parea']);

                        $this->_itemlist = $basedoc->unpackDetails('detaildata');
                    }
                    if ($basedoc->meta_name == 'GoodsReceipt') {
                        $this->docform->store->setValue($basedoc->headerdata['store']);

                        $this->_itemlist = $basedoc->unpackDetails('detaildata');
                    }
                    if ($basedoc->meta_name == 'ProdReceipt') {
                        $this->docform->store->setValue($basedoc->headerdata['store']);
                        $this->docform->parea->setValue($basedoc->headerdata['parea']);

                        $parts = array();
                        foreach ($basedoc->unpackDetails('detaildata') as $tovar) {
                            $plist = \App\Entity\ItemSet::find('pitem_id=' . $tovar->item_id);
                            foreach ($plist as $p) {
                                if (isset($parts[$p->item_id])) {
                                    $parts[$p->item_id]->qty += ($tovar->quantity * $p->qty);
                                } else {
                                    $parts[$p->item_id] = Item::load($p->item_id);
                                    $parts[$p->item_id]->qty = ($tovar->quantity * $p->qty);
                                }
                            }
                        }
                        foreach ($parts as $p) {
                            $it = Item::load($p->item_id);
                            $it->quantity = $p->qty;

                            $this->_itemlist[] = $it;
                        }
                    }
                }
            }
            if ($st_id > 0) {
                $st = \App\Entity\ProdStage::load($st_id);
                $this->docform->parea->setValue($st->pa_id);
                $this->_doc->headerdata['st_id'] = $st->st_id;
                $this->_doc->headerdata['pp_id'] = $st->pp_id;
                $this->docform->notes->setText($st->stagename);


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

        $row->add(new Label('quantity', H::fqty($item->quantity)));

        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? \App\Helper::fd($item->sdate) : ''));
        $row->add(new Label('cell', $item->cell));
        $qty = $item->getQuantity($this->docform->store->getValue());
        $row->add(new Label('qtyon',H::fqty($qty) ));
        $row->add(new Label('toorder','В закупку' ))->setAttribute('onclick',"addItemToCO([{$item->item_id}])");

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
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->editdetail->editquantity->setText("1");

        $this->docform->setVisible(false);
        $this->_rowid = -1;
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity);

        $this->editdetail->edittovar->setKey($item->item_id);
        $this->editdetail->edittovar->setValue($item->itemname);
        $this->editdetail->editserial->setValue($item->snumber);

        $this->editdetail->qtystock->setText(H::fqty($item->getQuantity($this->docform->store->getValue())));
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
        $store_id = $this->docform->store->getValue();

        $item = Item::load($id);


        $item->quantity = $this->editdetail->editquantity->getText();
        $item->snumber = $this->editdetail->editserial->getText();
        $qstock = $this->editdetail->qtystock->getText();
        if ($item->quantity > $qstock) {
            $this->setWarn('Введено більше товару, чим є в наявності');
        }


        if (strlen($item->snumber) == 0 && $item->useserial == 1 && $this->_tvars["usesnumber"] == true) {
            $this->setError("Потрібна партія виробника");
            return;
        }

        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {
            $slist = $item->getSerials($store_id);

            if (in_array($item->snumber, $slist) == false) {
                $this->setError('Невірний номер серії');
                return;
            }
        }


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
        $this->editdetail->editserial->setText("");
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->notes = $this->docform->notes->getText();


        $this->_doc->headerdata['parea'] = $this->docform->parea->getValue();
        $this->_doc->headerdata['pareaname'] = $this->docform->parea->getValueName();
        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['storename'] = $this->docform->store->getValueName();

        $this->_doc->packDetails('detaildata', $this->_itemlist);

        $this->_doc->amount = 0;
        $this->_doc->payamount = 0;
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

                // проверка на минус  в  количестве
                $allowminus = \App\System::getOption("common", "allowminus");
                if ($allowminus != 1) {

                    foreach ($this->_itemlist as $item) {
                        $qty = $item->getQuantity($this->_doc->headerdata['store']);
                        if ($qty < $item->quantity) {
                            $this->setError("На складі всього ".H::fqty($qty)." ТМЦ {$item->itemname}. Списання у мінус заборонено");
                            return;
                        }
                    }
                }

                $this->_doc->updateStatus(Document::STATE_EXECUTED);
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }


            $conn->CommitTrans();
            App::RedirectBack();
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

    public function addcodeOnClick($sender) {
        $code = trim($this->docform->barcode->getText());
        $code0 = $code;
        $code = ltrim($code, '0');

        $this->docform->barcode->setText('');
        if ($code == '') {
            return;
        }

        $store_id = $this->docform->store->getValue();
        if ($store_id == 0) {
            $this->setError('Не обрано склад');
            return;
        }
        $code0 = Item::qstr($code0);

        $code_ = Item::qstr($code);
        $item = Item::getFirst(" item_id in(select item_id from store_stock where store_id={$store_id}) and  (item_code = {$code_} or bar_code = {$code_} or item_code = {$code0} or bar_code = {$code0} )");

        if ($item == null) {
            $this->setError("Товар з кодом `{$code}` не знайдено");
            return;
        }


        $store_id = $this->docform->store->getValue();

        $qty = $item->getQuantity($store_id);
        if ($qty <= 0) {
            $this->setError("Товару {$item->itemname} немає на складі");
        }


        if ($this->_itemlist[$item->item_id] instanceof Item) {
            $this->_itemlist[$item->item_id]->quantity += 1;
        } else {


            $item->quantity = 1;

            if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {

                $serial = $item->getNearestSerie($store_id);


                if (strlen($serial) == 0) {
                    $this->setWarn('Потрібна партія виробника');
                    $this->editdetail->setVisible(true);
                    $this->docform->setVisible(false);

                    $this->editdetail->edittovar->setKey($item->item_id);
                    $this->editdetail->edittovar->setText($item->itemname);
                    $this->editdetail->editserial->setText('');
                    $this->editdetail->editquantity->setText('1');

                    return;
                } else {
                    $item->snumber = $serial;
                }
            }
            $this->_itemlist[$item->item_id] = $item;
        }
        $this->docform->detail->Reload();
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

        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }


    public function OnChangeItem($sender) {

        $id = $sender->getKey();
        $item = Item::load($id);
        $store_id = $this->docform->store->getValue();

        $qty = $item->getQuantity($store_id);

        $this->editdetail->qtystock->setText(H::fqty($qty));

        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {

            $serial = $item->getNearestSerie($store_id);
            $this->editdetail->editserial->setText($serial);
        }


    }

    public function OnAutoItem($sender) {
        //$store_id = $this->docform->store->getValue();
        $text = trim($sender->getText());
        return Item::findArrayAC($text);
    }

}

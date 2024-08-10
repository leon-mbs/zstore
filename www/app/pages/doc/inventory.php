<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Store;
use App\Entity\Category;
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
 * Инвентаризация    склада
 */
class Inventory extends \App\Pages\Base
{
    public $_itemlist = array();
    private $_doc;
    private $_rowid    = 0;
    private $_qint     = false;

    /**
    * @param mixed $docid     редактирование
    */
    public function __construct($docid = 0) {
        parent::__construct();

        $qtydigits = \App\System::getOption("common",'qtydigits');
        
        $this->_qint = intval($qtydigits)==0;
        
        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date', time()));

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()))->onChange($this, 'OnChangeStore');
        $this->docform->add(new DropDownChoice('category', Category::getList(), 0))->onChange($this, 'OnChangeCat');

        $this->docform->add(new TextInput('brand'));
        $this->docform->brand->setDataList(Item::getManufacturers());
        
        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new CheckBox('autoincome'));
        $this->docform->add(new CheckBox('autooutcome'));
        $this->docform->add(new CheckBox('reserved'));
        $this->docform->add(new TextInput('barcode'));
        $this->docform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');
        $this->docform->add(new SubmitLink('loadall'))->onClick($this, 'loadallOnClick');

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitLink('delall'))->onClick($this, 'OnDelAll');
        $this->docform->add(new SubmitLink('sortname'))->onClick($this, 'OnSortName');
        $this->docform->add(new SubmitLink('sortcode'))->onClick($this, 'OnSortCode');

        $this->add(new Form('editdetail'))->setVisible(false);

        $this->editdetail->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutocompleteItem');

        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editserial'))->setText("");

        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saverowOnClick');
        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');

        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);
            // $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->document_date->setDate(time());
            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->category->setValue($this->_doc->headerdata['cat']);
            $this->docform->brand->setText($this->_doc->headerdata['brand']);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->autoincome->setChecked($this->_doc->headerdata['autoincome']);
            $this->docform->autooutcome->setChecked($this->_doc->headerdata['autooutcome']);
            $this->docform->reserved->setChecked($this->_doc->headerdata['reserved']);

            $this->_itemlist = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('Inventory');
            $this->docform->document_number->setText($this->_doc->nextNumber());
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();

        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('item', $item->itemname));
        $row->add(new Label('item_code', $item->item_code));

        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? \App\Helper::fd($item->sdate) : ''));

        //  $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new TextInput('qfact', new \Zippy\Binding\PropertyBinding($item, 'qfact')))->onChange($this, "onText", true);

        if($this->_qint) {
           $row->qfact->setAttribute('type', 'number');            
        }

        $row->setAttribute('style', $item->disabled == 1 ? 'color: #aaa' : null);

        $row->add(new CheckBox('seldel', new \Zippy\Binding\PropertyBinding($item, 'seldel')));

    }

    //для  сохранения формы
    public function onText($sender) {

    }



    public function OnDelAll($sender) {

        $items = [];
        foreach ($this->docform->detail->getDataRows() as $row) {
            $item = $row->getDataItem();
            if ($item->seldel != true) {
                $item->seldel = false;
                $items[]=$item;

            }
        }
        $this->_itemlist = $items;

        $this->docform->detail->Reload();
    }


    public function addrowOnClick($sender) {
        if ($this->docform->store->getValue() == 0) {
            $this->setError("Не обрано склад");
            return;
        }
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setValue('');
        $this->_rowid = -1;

    }

    public function saverowOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $id = $this->editdetail->edititem->getKey();
        if ($id == 0) {
            $this->setError("Не обрано ТМЦ");
            return;
        }
        $item = Item::load($id);
        $store = $this->docform->store->getValue();
        $sn = trim($this->editdetail->editserial->getText());


        $item->quantity = $item->getQuantity($store, $sn, $this->docform->document_date->getDate(0));
        $item->qfact = $this->editdetail->editquantity->getText();
        $item->snumber = $sn;

        foreach($this->_itemlist as $i=> $it) {
            if($it->item_id==$item->item_id && $it->snumber==$item->snumber) {
                $this->setError("ТМЦ  уже  в  списку") ;
                return;
            }
        }
        $this->_itemlist[] = $item;


        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();

        //очищаем  форму
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setValue('');
        $this->editdetail->editquantity->setText("1");
    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setText('');

        $this->editdetail->editquantity->setText("1");
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }

        $this->_doc->notes = $this->docform->notes->getText();

        $this->_doc->headerdata['autoincome'] = $this->docform->autoincome->isChecked() ? 1 : 0;
        $this->_doc->headerdata['autooutcome'] = $this->docform->autooutcome->isChecked() ? 1 : 0;
        $this->_doc->headerdata['reserved'] = $this->docform->reserved->isChecked() ? 1 : 0;
        $this->_doc->headerdata['cat'] = $this->docform->category->getValue();
        $this->_doc->headerdata['brand'] = $this->docform->brand->getText();
        $this->_doc->headerdata['store'] = $this->docform->store->getValue();
        $this->_doc->headerdata['storename'] = $this->docform->store->getValueName();

        $reserved = array();
        if($this->_doc->headerdata['reserved'] ==1) {
            $conn = \ZDB\DB::getConnect();
            $sql = "select item_id,sum(0-quantity) as cnt from entrylist_view where tag=-64 and stock_id in(select stock_id from store_stock where  store_id= {$this->_doc->headerdata['store']}) group by item_id"  ;
            foreach($conn->Execute($sql) as $row) {
                $reserved[$row['item_id']]  = $row['cnt'] ;
            }
        }



        foreach ($this->_itemlist as $item) {
            $item->quantity = $item->getQuantity($this->_doc->headerdata['store'], $item->snumber, $this->docform->document_date->getDate(0));
            if($reserved[$item->item_id] > 0  && $this->_doc->headerdata['reserved']  ==1) {
                $item->quantity += $reserved[$item->item_id] ;
            }
        }

        $this->_doc->packDetails('detaildata', $this->_itemlist);

        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());

        if ($this->checkForm() == false) {
            return;
        }


        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

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

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {

        if (strlen(trim($this->docform->document_number->getText())) == 0) {
            $this->setError("Введіть номер документа");
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
            $this->setError("Не введено ТМЦ");
        }
        if (($this->docform->store->getValue() > 0) == false) {
            $this->setError("Не обрано склад");
        }


        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnChangeStore($sender) {

        //очистка  списка  товаров
        $this->_itemlist = array();
        $this->docform->detail->Reload();
    }

    public function OnChangeCat($sender) {
        $cat_id = $sender->getValue();

        if ($cat_id > 0) {

            $c = Category::load($cat_id) ;
            $ch = $c->getChildren();
            $ch[]=$cat_id;



            $itemlist = array();
            foreach ($this->_itemlist as $item) {
                if (in_array($item->cat_id, $ch)) {
                    $itemlist[$item->item_id] = $item;
                }
            }
            $this->_itemlist = $itemlist;

            $this->docform->detail->Reload();
        }
    }

    public function OnAutocompleteItem($sender) {
        $store_id = $this->docform->store->getValue();
        $text = trim($sender->getText());
        $cat_id = intval($this->docform->category->getValue());
        $common = \App\System::getOptions('common')  ;
        if($common['usecattree'] != 1 || $cat_id==0) {
            return Item::findArrayAC($text, $store_id, $cat_id);
        }

        $c = Category::load($cat_id) ;
        $ch = $c->getChildren();
        $ch[]=$cat_id;
        $ret = array();
        foreach($ch as $id) {
            foreach(Item::findArrayAC($text, $store_id, $id) as $k=>$v) {
                $ret[$k]=$v;
            }
        }



        return $ret;
    }

    public function loadallOnClick($sender) {
        $this->_itemlist = array();
        $store_id = $this->docform->store->getValue();

        $w = " disabled<> 1 and  item_id in (select item_id from  store_stock_view where  qty>0 and store_id={$store_id})    ";

        $brand =trim( $this->docform->brand->getText() );
        if(strlen($brand) >0){
           $w = $w . " and manufacturer = " .Item::qstr($brand) ;
        }
        $cat_id = $this->docform->category->getValue();
        if ($cat_id > 0) {

            $c = Category::load($cat_id) ;
            $ch = $c->getChildren();
            $ch[]=$cat_id;
            $cats = implode(",", $ch)  ;


            $w = $w . " and cat_id in ({$cats}) ";
        }
        
        foreach (Item::findYield($w, 'itemname') as $item) {
            $item->qfact = 0;
            $item->quantity = 0;
            $this->_itemlist[$item->item_id] = $item;
        }
        $this->docform->detail->Reload();
    }

    public function addcodeOnClick($sender) {
        $code = trim($this->docform->barcode->getText());
        $this->docform->barcode->setText('');
        $code0 = $code;
        $code = ltrim($code, '0');

        foreach($this->_itemlist as $i=> $it) {
            if($it->item_code==$code || $it->bar_code==$code) {
                $d= $this->_itemlist[$i]->qfact;
                $qf= doubleval($d) ;
                $this->_itemlist[$i]->qfact = $qf + 1;

                // Издаем звук если всё ок
                App::$app->getResponse()->addJavaScript("new Audio('/assets/good.mp3').play()", true);


                $this->docform->detail->Reload();
                return;
            }
        }



        $store = $this->docform->store->getValue();
        $code_ = Item::qstr($code);
        $code0 = Item::qstr($code0);

        $cat_id = $this->docform->category->getValue();
        $w = "item_code={$code_} or bar_code={$code_} or  item_code={$code0} or bar_code={$code0} ";
        if ($cat_id > 0) {


            $c = Category::load($cat_id) ;
            $ch = $c->getChildren();
            $ch[]=$cat_id;
            $cats = implode(",", $ch)  ;


            $w = $w . " and cat_id in ({$cats}) ";
        }
        $item = Item::getFirst($w);
        if ($item == null) {
            $this->setError("ТМЦ з кодом `{$code}` не знайдено");
            // Издаем звук если ШК не найден
            App::$app->getResponse()->addJavaScript("new Audio('/assets/error.mp3').play()", true);
            return;
        } else {
            // Издаем звук если всё ок
            App::$app->getResponse()->addJavaScript("new Audio('/assets/good.mp3').play()", true);
        }

        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {

            $this->editdetail->setVisible(true);
            $this->docform->setVisible(false);
            $this->editdetail->edititem->setKey($item->item_id);
            $this->editdetail->edititem->setText($item->itemname);
            $this->editdetail->editserial->setText('');
            $this->editdetail->editquantity->setText('1');
            return;
        }


        if (!isset($this->_itemlist[$item->item_id])) {
            $item->qfact = 0;
            $item->quantity = $item->getQuantity($store);
            $this->_itemlist[$item->item_id] = $item;
        }

        $d= $this->_itemlist[$item->item_id]->qfact;
        $qf= doubleval($d) ;
        $this->_itemlist[$item->item_id]->qfact = $qf + 1;


        $this->docform->detail->Reload();
    }

    public function OnSortName($sender) {
         usort($this->_itemlist, function ($a, $b) {
            return $a->itemname > $b->itemname;
        });
        $this->docform->detail->Reload();
        
    }
        
    public function OnSortCode($sender) {
         usort($this->_itemlist, function ($a, $b) {
            return $a->item_code > $b->item_code;
        });
        $this->docform->detail->Reload();

    }
    
}

<?php

namespace App\Pages\Service;

use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Category;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Image;
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
use Zippy\Html\Panel;
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * АРМ кассира общепита
 */
class ARMFood extends \App\Pages\Base
{

    private $_pricetype;
    private $_worktype = 0;
    private $_pos;
    private $_store;
    public  $_pt       = -1;


    private $_doc;
    public  $_itemlist = array();
    public  $_catlist  = array();
    public  $_prodlist = array();
    public  $_doclist  = array();

    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowSer('ARMFood')) {
            return;
        }
        $food = System::getOptions("food");
        if (!is_array($food)) {
            $food = array();
            $this->setWarn('nocommonoptions');
        }
        $this->_worktype = $food['worktype'];

        $this->_tvars['delivery'] = $food['delivery'] ?? 0;
        $this->_tvars['tables'] = $food['tables'] ?? 0;
        $this->_tvars['pack'] = $food['pack'] ?? 0;


        $filter = \App\Filter::getFilter("armfood");
        if ($filter->isEmpty()) {
            $filter->pos = 0;
            $filter->store = H::getDefStore();
            $filter->pricetype = $food['pricetype'] ?? 'price1';

            $filter->nal = H::getDefMF();
            $filter->beznal = H::getDefMF();

        }

        //обшие настройки
        $this->add(new Form('setupform'))->onSubmit($this, 'setupOnClick');

        $this->setupform->add(new DropDownChoice('pos', \App\Entity\Pos::findArray('pos_name', ''), $filter->pos));
        $this->setupform->add(new DropDownChoice('store', \App\Entity\Store::getList(), $filter->store));
        $this->setupform->add(new DropDownChoice('nal', \App\Entity\MoneyFund::getList(1), $filter->nal));
        $this->setupform->add(new DropDownChoice('beznal', \App\Entity\MoneyFund::getList(2), $filter->beznal));

        //список  заказов
        $this->add(new Panel('orderlistpan'))->setVisible(false);

        $this->orderlistpan->add(new ClickLink('neworder', $this, 'onNewOrder'));
        $this->orderlistpan->add(new DataView('orderlist', new ArrayDataSource($this, '_doclist'), $this, 'onDocRow'));
        $this->orderlistpan->add(new \Zippy\Html\DataList\Paginator('pag',  $this->orderlistpan->orderlist));
        $this->orderlistpan->orderlist->setPageSize(H::getPG());

        $this->orderlistpan->add(new Form('searchform'))->onSubmit($this, 'updateorderlist');
        $this->orderlistpan->searchform->add(new AutocompleteTextInput('searchcust'))->onText($this, 'OnAutoCustomer');
        $this->orderlistpan->searchform->add(new TextInput('searchnumber', $filter->searchnumber));

        //панель статуса,  просмотр
        $this->orderlistpan->add(new Panel('statuspan'))->setVisible(false);

        $sf = $this->orderlistpan->statuspan->add(new Form('statusform'));
        $sf->add(new  SubmitButton('bedit'))->onClick($this, 'onStatus');

        $sf->add(new  SubmitButton('bpay'))->onClick($this, 'onStatus');

        $sf->add(new  SubmitButton('bclose'))->onClick($this, 'onStatus');
        $sf->add(new  SubmitButton('brefuse'))->onClick($this, 'onStatus');


        $this->orderlistpan->statuspan->add(new \App\Widgets\DocView('docview'))->setVisible(false);

        //оформление заказа

        $this->add(new Panel('docpanel'))->setVisible(false);
        $this->docpanel->add(new ClickLink('toorderlist', $this, 'onOrderList'));

        $this->docpanel->add(new Panel('catpan'))->setVisible(false);
        $this->docpanel->catpan->add(new DataView('catlist', new ArrayDataSource($this, '_catlist'), $this, 'onCatRow'));
        $this->docpanel->catpan->add(new ClickLink('stopcat', $this, 'onStopCat'));

        $this->docpanel->add(new Panel('prodpan'))->setVisible(false);
        $this->docpanel->prodpan->add(new DataView('prodlist', new ArrayDataSource($this, '_prodlist'), $this, 'onProdRow'));
        $this->docpanel->prodpan->add(new ClickLink('stopprod', $this, 'onStopCat'));

        $this->docpanel->add(new Form('navform'));

        $this->docpanel->navform->add(new TextInput('barcode'));
        $this->docpanel->navform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');

        $this->docpanel->navform->add(new SubmitButton('baddnewpos'))->onClick($this, 'addnewposOnClick');

        $this->docpanel->navform->add(new ClickLink('openshift', $this, 'OnOpenShift'));
        $this->docpanel->navform->add(new ClickLink('closeshift', $this, 'OnCloseShift'));
        
        
        $this->docpanel->add(new Form('listsform'))->setVisible(false);
        $this->docpanel->listsform->add(new DataView('itemlist', new ArrayDataSource($this, '_itemlist'), $this, 'onItemRow'));

        $this->docpanel->listsform->add(new SubmitButton('btosave'))->onClick($this, 'tosaveOnClick');
        $this->docpanel->listsform->add(new SubmitButton('btopay'))->onClick($this, 'topayOnClick');
        $this->docpanel->listsform->add(new SubmitButton('btoprod'))->onClick($this, 'toprodOnClick');
        $this->docpanel->listsform->add(new SubmitButton('btodel'))->onClick($this, 'todelOnClick');
        $this->docpanel->listsform->add(new Label('totalamount', "0"));

        $this->docpanel->listsform->add(new TextInput('address'));
        $this->docpanel->listsform->add(new Date('dt', time()));
        $this->docpanel->listsform->add(new \Zippy\Html\Form\Time('time'));
        $this->docpanel->listsform->add(new TextInput('notes'));
        $this->docpanel->listsform->add(new TextInput('contact'));
        $this->docpanel->listsform->add(new TextInput('table'));
        $this->docpanel->listsform->add(new DropDownChoice('delivery', Document::getDeliveryTypes(), 0))->onChange($this, 'OnDelivery');
        $this->docpanel->listsform->add(new ClickLink('addcust'))->onClick($this, 'addcustOnClick');
        $this->docpanel->listsform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docpanel->listsform->customer->onChange($this, 'OnChangeCustomer', true);

        $this->docpanel->add(new Form('payform'))->setVisible(false);
        $this->docpanel->payform->add(new TextInput('pfamount'));
        $this->docpanel->payform->add(new TextInput('pfdisc'));
        $this->docpanel->payform->add(new TextInput('pfforpay'));
        $this->docpanel->payform->add(new TextInput('pfpayed'));
        $this->docpanel->payform->add(new TextInput('pfrest'));
        $this->docpanel->payform->add(new TextInput('pftrans'));
        $this->docpanel->payform->add(new TextInput('pfbonus'));

        $this->docpanel->payform->add(new CheckBox('passfisc'));
 
        $bind = new  \Zippy\Binding\PropertyBinding($this, '_pt');
        $this->docpanel->payform->add(new \Zippy\Html\Form\RadioButton('pfnal', $bind, 1));
        $this->docpanel->payform->add(new \Zippy\Html\Form\RadioButton('pfbeznal', $bind, 2));

        $this->docpanel->payform->add(new ClickLink('bbackitems'))->onClick($this, 'backItemsOnClick');
        $this->docpanel->payform->add(new SubmitButton('btocheck'))->onClick($this, 'payandcloseOnClick');
        $this->docpanel->add(new Panel('checkpan'))->setVisible(false);
        $this->docpanel->checkpan->add(new ClickLink('bnewcheck'))->onClick($this, 'onNewOrder');
        $this->docpanel->checkpan->add(new Label('checktext'));
        $this->docpanel->checkpan->add(new Button('btoprint'))->onClick($this,"OnPrint",true);

        //добавление нового контрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new TextInput('editaddress'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        $this->OnDelivery($this->docpanel->listsform->delivery);
    }

    public function setupOnClick($sender) {
        $store = $this->setupform->store->getValue();
        $nal = $this->setupform->nal->getValue();
        $beznal = $this->setupform->beznal->getValue();

        $this->_pos = \App\Entity\Pos::load($this->setupform->pos->getValue());

        if ($store == 0 || $nal == 0 || $beznal == 0 || $this->_pos == null) {
            $this->setError(H::l("notalldata"));
            return;
        }
        $filter = \App\Filter::getFilter("armfood");


        $filter->store = $store;
        $filter->pos = $this->_pos->pos_id;

        $filter->nal = $nal;
        $filter->beznal = $beznal;
        $this->_store = $store;
        $this->_pricetype = $filter->pricetype;


        $this->setupform->setVisible(false);

        $this->onNewOrder();
    }

    public function onNewOrder($sender = null) {
        //  $this->orderlistpan->statuspan->setVisible(true);
        $this->docpanel->setVisible(true);

        $this->docpanel->listsform->setVisible(true);
        $this->docpanel->listsform->clean();
        $this->docpanel->listsform->dt->setDate(time());
        $this->docpanel->listsform->time->setDateTime(time() + 3600);
        $this->docpanel->navform->setVisible(true);
        $this->docpanel->navform->clean();

        $this->orderlistpan->setVisible(false);
        $this->docpanel->checkpan->setVisible(false);

        $this->_doc = \App\Entity\Doc\Document::create('OrderFood');


        $this->_itemlist = array();

        $this->docpanel->listsform->itemlist->Reload();
        $this->calcTotal();
        $this->orderlistpan->searchform->clean();

        $this->docpanel->listsform->delivery->setValue(0);
        $this->OnDelivery($this->docpanel->listsform->delivery);
    }


    public function OnDelivery($sender) {
        $this->docpanel->listsform->contact->setVisible(false);
        $this->docpanel->listsform->address->setVisible(false);
        $this->docpanel->listsform->dt->setVisible(false);
        $this->docpanel->listsform->time->setVisible(false);
        $this->docpanel->listsform->table->setVisible(false);
        $this->docpanel->listsform->btopay->setVisible(false);
        $this->docpanel->listsform->btodel->setVisible(false);
        $this->docpanel->listsform->btoprod->setVisible(false);

        if ($sender->getValue() == 0) {
            $this->docpanel->listsform->table->setVisible(true);
            if ($this->_worktype == 0 || $this->_worktype == 1) {
                $this->docpanel->listsform->btopay->setVisible(true);
            }
            if ($this->_worktype == 2) {
                $this->docpanel->listsform->btoprod->setVisible(true);
            }
        }
        if ($sender->getValue() > 0) {
            $this->docpanel->listsform->dt->setVisible(true);
            $this->docpanel->listsform->time->setVisible(true);
            $this->docpanel->listsform->btodel->setVisible(true);
            $this->docpanel->listsform->contact->setVisible(true);
        }
        if ($sender->getValue() > 1) {
            $this->docpanel->listsform->address->setVisible(true);

        }
    }

    //открыть список  ордеров
    public function onOrderList($sender) {
        $this->docpanel->setVisible(false);
        $this->docpanel->prodpan->setVisible(false);
        $this->docpanel->catpan->setVisible(false);
        $this->docpanel->catpan->setVisible(false);
        $this->docpanel->payform->setVisible(false);
        $this->docpanel->checkpan->setVisible(false);

        $this->orderlistpan->setVisible(true);
        $this->orderlistpan->statuspan->setVisible(true);
        $this->orderlistpan->searchform->clean();
        $this->updateorderlist(null);
    }

    public function addnewposOnClick($sender) {
        $this->docpanel->catpan->setVisible(true);
        $this->docpanel->prodpan->setVisible(false);
        $this->docpanel->listsform->setVisible(false);
        $this->docpanel->navform->setVisible(false);


        $this->_catlist = Category::find(" cat_id in(select cat_id from  items where  disabled <>1  ) and  coalesce(parent_id,0)=0 and detail  not  like '%<nofastfood>1</nofastfood>%' ");
        $this->docpanel->catpan->catlist->Reload();
    }

    //список  заказов
    public function onDocRow($row) {
        $doc = $row->getDataItem();
        $row->add(new ClickLink('docnumber', $this, 'OnDocViewClick'))->setValue($doc->document_number);
        $row->add(new Label('state', Document::getStateName($doc->state)));
        $row->add(new Label('docdate', H::fd($doc->document_date)));

        $row->add(new Label('docamount', H::fa(($doc->payamount > 0) ? $doc->payamount : ($doc->amount > 0 ? $doc->amount : ""))));
        
        $row->add(new Label('docnotes', $doc->notes));
        $row->add(new Label('tablenumber', $doc->headerdata['table']));
        $row->add(new Label('wp'))->setVisible($doc->payamount > $doc->payed);
        $row->add(new Label('isdel'))->setVisible($doc->headerdata['delivery'] > 0);

        if ($doc->document_id == $this->_doc->document_id) {
            $row->setAttribute('class', 'table-success');
        }

    }

    public function updateorderlist($sender) {
        $where = " state not in(9,17) ";
        if ($sender instanceof Form) {
            $text = trim($sender->searchnumber->getText());
            $cust = $sender->searchcust->getKey();
            if ($cust > 0) {
                $where = "   customer_id=" . $cust;
            }
            if (strlen($text) > 0) {

                $where = "   document_number=" . Document::qstr($text);
            }


        }
        $where .= " and meta_name='OrderFood'   ";


        $this->_doclist = Document::find($where, 'priority desc,document_id desc');
        $this->orderlistpan->orderlist->Reload();
        $this->orderlistpan->statuspan->setVisible(false);


    }

    //категории
    public function onCatRow($row) {
        $cat = $row->getDataItem();
        $row->add(new Panel('catbtn'))->onClick($this, 'onCatBtnClick');
        $row->catbtn->add(new Label('catname', $cat->cat_name));
        $row->catbtn->add(new Image('catimage', "/loadimage.php?id=" . $cat->image_id));
    }

    //товары
    public function onProdRow($row) {
        //  $store_id = $this->setupform->store->getValue();

        $prod = $row->getDataItem();
        $prod->price = $prod->getPrice($this->_pricetype, $this->_store);
        $row->add(new Panel('prodbtn'))->onClick($this, 'onProdBtnClick');
        $row->prodbtn->add(new Label('prodname', $prod->itemname));
        $row->prodbtn->add(new Label('prodprice', H::fa($prod->price)));
        $row->prodbtn->add(new Image('prodimage', "/loadimage.php?id=" . $prod->image_id));
    }

    //выбрана  группа
    public function onCatBtnClick($sender) {
        $cat = $sender->getOwner()->getDataItem();
        $catlist = Category::find("  detail  not  like '%<nofastfood>1</nofastfood>%' and   coalesce(parent_id,0)= " . $cat->cat_id);
        if (count($catlist) > 0) {
            $this->_catlist = $catlist;
            $this->docpanel->catpan->catlist->Reload();
        } else {
            $this->_prodlist = Item::find('disabled<>1  and  item_type in (1,4 )  and cat_id=' . $cat->cat_id);
            $this->docpanel->catpan->setVisible(false);
            $this->docpanel->prodpan->setVisible(true);
            $this->docpanel->prodpan->prodlist->Reload();
        }

    }

    // выбран  товар
    public function onProdBtnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $store_id = $this->setupform->store->getValue();

        $qty = $item->getQuantity($store_id);
        if ($qty <= 0 && $item->autoincome != 1) {

            $this->setWarn("noitemonstore", $item->itemname);
        }


        if (isset($this->_itemlist[$item->item_id])) {
            $this->_itemlist[$item->item_id]->quantity++;
        } else {
            $item->myself = $this->_worktype == 0;
            if ($this->_tvars['pack'] == false) {
                $item->myself = 0;
            }
            $item->quantity = 1;
            // $item->price = $item->getPrice($this->_pricetype, $this->_store);
            $this->_itemlist[$item->item_id] = $item;
        }

        $this->_catlist = Category::find(" coalesce(parent_id,0)=0 and detail  not  like '%<nofastfood>1</nofastfood>%' ");
        $this->docpanel->catpan->catlist->Reload();


        $this->docpanel->catpan->setVisible(true);
        $this->docpanel->prodpan->setVisible(false);

    }

    //закончить  добавление  товаров
    public function onStopCat($sender) {

        $this->docpanel->listsform->setVisible(true);
        $this->docpanel->navform->setVisible(true);
        $this->docpanel->catpan->setVisible(false);
        $this->docpanel->prodpan->setVisible(false);
        $this->docpanel->listsform->itemlist->Reload();
        $this->calcTotal();
    }


    public function addcodeOnClick($sender) {
        $code = trim($this->docpanel->navform->barcode->getText());
         $code0 = $code;
               $code = ltrim($code,'0');

        $this->docpanel->navform->barcode->setText('');
        if ($code == '') {
            return;
        }

        foreach ($this->_itemlist as $ri => $_item) {
            if ($_item->bar_code == $code || $_item->item_code == $code || $_item->bar_code == $code0 || $_item->item_code == $code0) {
                $this->_itemlist[$ri]->quantity += 1;
                $this->docpanel->listsform->itemlist->Reload();
                $this->calcTotal();
                return;
            }
        }


        $store_id = $this->setupform->store->getValue();


        $code_ = Item::qstr($code);
        $item = Item::getFirst(" item_id in(select item_id from store_stock where store_id={$store_id}) and   (item_code = {$code_} or bar_code = {$code_})");

        if ($item == null) {

            $this->setWarn("noitemcode", $code);
            return;
        }


        $qty = $item->getQuantity($store_id);
        if ($qty <= 0) {

            $this->setWarn("noitemonstore", $item->itemname);
        }


        $price = $item->getPrice($this->_pricetype, $store_id);
        $item->price = $price;
        $item->quantity = 1;
        $item->myself = $this->_worktype == 0;
        if ($this->_tvars['pack'] == false) {
            $item->myself = 0;
        }
        $this->_itemlist[$item->item_id] = $item;


        $this->docpanel->listsform->itemlist->Reload();
        $this->calcTotal();


    }


    //список позиций
    public function onItemRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('itemname', $item->itemname));
        $row->add(new Label('item_code', $item->item_code));
        $row->add(new Label('qty', H::fqty($item->quantity)));
        $row->add(new Label('price', H::fa($item->price)));
        $row->add(new Label('amount', H::fa($item->price * $item->quantity)));
        $row->add(new ClickLink('myselfon', $this, 'onMyselfClick'))->setVisible($item->myself == 1);
        $row->add(new ClickLink('myselfoff', $this, 'onMyselfClick'))->setVisible($item->myself != 1);
        $row->add(new ClickLink('qtymin'))->onClick($this, 'onQtyClick');
        $row->add(new ClickLink('qtyplus'))->onClick($this, 'onQtyClick');
        $row->add(new ClickLink('removeitem'))->onClick($this, 'onDelItemClick');
        if ($item->foodstate == 1) {
            $row->removeitem->setVisible(false);
            $row->myselfon->setVisible(false);
            $row->myselfoff->setVisible(false);
            $row->qtymin->setVisible(false);
            $row->qtyplus->setVisible(false);
            $row->removeitem->setVisible(false);
        }
    }

    public function onQtyClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        if (strpos($sender->id, "qtyplus") === 0) {
            $item->quantity++;
        }
        if (strpos($sender->id, "qtymin") === 0 && $item->quantity > 1) {
            $item->quantity--;
        }

        $this->docpanel->listsform->itemlist->Reload();
        $this->calcTotal();
    }

    //с собой
    public function onMyselfClick($sender) {
        $item = $sender->getOwner()->getDataItem();

        $item->myself = strpos($sender->id, "myselfon") === 0 ? 0 : 1;
        $this->docpanel->listsform->itemlist->Reload();

    }

    public function onDelItemClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->_itemlist = array_diff_key($this->_itemlist, array($item->item_id => $this->_itemlist[$item->item_id]));

        $this->docpanel->listsform->itemlist->Reload();
        $this->calcTotal();
    }


    public function OnDocViewClick($sender) {

        $this->_doc = Document::load($sender->getOwner()->getDataItem()->document_id);
        $this->OnDocView();

    }

    public function OnDocView() {
        $this->orderlistpan->statuspan->setVisible(true);
        $this->orderlistpan->statuspan->statusform->setVisible(true);

        $sf = $this->orderlistpan->statuspan->statusform;

        $sf->bedit->setVisible(false);

        $sf->bpay->setVisible(false);
        $sf->bclose->setVisible(false);
        $sf->brefuse->setVisible(false);

        if ($this->_doc->state == Document::STATE_DELIVERED && $this->_doc->payamount <= $this->_doc->payed) {

            $sf->bclose->setVisible(true);
        }

        if ($this->_doc->state < 4 || $this->_doc->state == Document::STATE_INPROCESS || $this->_doc->state == Document::STATE_READYTOSHIP) {
            $sf->bedit->setVisible(true);
            $sf->brefuse->setVisible(true);
        }
        if ($this->_doc->payamount > $this->_doc->payed && $this->_doc->state > 4 && $this->_doc->state != Document::STATE_CLOSED && $this->_doc->state != Document::STATE_FAIL) { //к  оплате
            $sf->bpay->setVisible(true);
        }
        if ($this->_doc->hasPayments()  || $this->_doc->hasPayments() ) { //оплачено
            $sf->bedit->setVisible(false);
            $sf->brefuse->setVisible(false);
        }


        $this->orderlistpan->statuspan->docview->setDoc($this->_doc);
        $this->orderlistpan->orderlist->Reload(false);

        $this->goAnkor('dankor');
    }

    public function onStatus($sender) {
        if ($sender->id == 'bedit') {
            $this->orderlistpan->setVisible(false);
            $this->orderlistpan->statuspan->setVisible(false);
            //   $this->orderlistpan->statuspan->docview->setVisible(false);
            //   $this->orderlistpan->statuspan->statusform->setVisible(false);
            $this->docpanel->setVisible(true);
            $this->docpanel->listsform->clean();

            $this->docpanel->listsform->notes->setText($this->_doc->notes);
            $this->docpanel->listsform->table->setText($this->_doc->headerdata['table']);

            if ($this->_doc->customer_id > 0) {
                $this->docpanel->listsform->customer->setKey($this->_doc->customer_id);
                $this->docpanel->listsform->customer->setText($this->_doc->customer_name);
            }

            if (intval($this->_doc->headerdata['delivery']) > 0) {
                $this->docpanel->listsform->delivery->setValue($this->_doc->headerdata['delivery']);
                $this->OnDelivery($this->docpanel->listsform->delivery);
                $this->docpanel->listsform->address->setText($this->_doc->headerdata['ship_address']);
                $this->docpanel->listsform->contact->setText($this->_doc->headerdata['contact']);
                $this->docpanel->listsform->dt->setDate($this->_doc->headerdata['deltime']);
                $this->docpanel->listsform->time->setDateTime($this->_doc->headerdata['deltime']);

            }

            $this->_itemlist = $this->_doc->unpackDetails('detaildata');

            $this->docpanel->listsform->itemlist->Reload();
            $this->calcTotal();
            return;
        }
        if ($sender->id == 'bclose') {
            $this->_doc->updateStatus(Document::STATE_CLOSED);

        }
        if ($sender->id == 'brefuse') {
            $this->_doc->updateStatus(Document::STATE_FAIL);

        }
        if ($sender->id == 'bpay') {

            $this->docpanel->setVisible(true);

            $this->orderlistpan->statuspan->setVisible(false);
            $this->orderlistpan->setVisible(false);

            $this->docpanel->payform->setVisible(true);
            $this->docpanel->listsform->setVisible(false);
            $this->docpanel->navform->setVisible(false);
            $this->docpanel->payform->clean();
            $amount = $this->_doc->payamount;
            $this->docpanel->payform->pfamount->setText(H::fa($amount));
            $disc = 0;
            $bonus = 0;
            if ($this->_doc->customer_id > 0) {
                $customer = \App\Entity\Customer::load($this->_doc->customer_id);
                $d = $customer->getDiscount() ;
                if ($d > 0) {
                    $disc = round($amount * ($d / 100));
                } else {
                    $bonus = $customer->getBonus();
                    if ($bonus > 0) {
                       if ($amount < $bonus) {
                           $bonus = $bonus - $amount; 
                       }

                        
                    }
                }

            }

            $this->docpanel->payform->pfdisc->setText(H::fa($disc));
            $this->docpanel->payform->pfbonus->setText(H::fa($bonus));
            $this->docpanel->payform->pfforpay->setText(H::fa($amount - $disc - $bonus));
            //  $this->docpanel->payform->pfpayed->setText(H::fa($amount))  ;
            $this->docpanel->payform->pfrest->setText(H::fa(0));
            $this->docpanel->payform->bbackitems->setVisible(false);

            return;
        }
        $this->orderlistpan->statuspan->setVisible(false);


        $this->updateorderlist(null);

    }


    public function calcTotal() {
        $amount = 0;
        foreach ($this->_itemlist as $item) {
            $amount += ($item->quantity * $item->price);
        }
        $this->docpanel->listsform->totalamount->setText(H::fa($amount));
    }

    public function OnAutoCustomer($sender) {
        return \App\Entity\Customer::getList($sender->getText(), 1);
    }


    // в   доставку
    public function todelOnClick($sender) {
        $this->_doc->headerdata['delivery'] = $this->docpanel->listsform->delivery->getValue();
        $this->_doc->headerdata['delivery_name'] = $this->docpanel->listsform->delivery->getValueName();
        $this->_doc->headerdata['ship_address'] = trim($this->docpanel->listsform->address->getText());
        $this->_doc->headerdata['contact'] = trim($this->docpanel->listsform->contact->getText());
        $dt = $this->docpanel->listsform->dt->getDate();
        $this->_doc->headerdata['deltime'] = $this->docpanel->listsform->time->getDateTime($dt);
        if ($this->_doc->headerdata['delivery'] > 1 && $this->_doc->headerdata['ship_address'] == "") {
            $this->setError('enteraddress');
            return;
        }
        if ($this->_doc->headerdata['delivery'] > 0 && $this->_doc->headerdata['contact'] == "") {
            $this->setError('entercontact');
            return;
        }

        if ($this->createdoc() == false) {
            return;
        }
        if ($this->_worktype == 0) {
            $this->_doc->updateStatus(Document::STATE_READYTOSHIP);
            $conn = \ZDB\DB::getConnect();
            $conn->BeginTrans();
            //списываем  со  склада
            try {
                $this->_doc = $this->_doc->cast();
                
                $this->_doc->DoStore();
                $this->_doc->save();

                $conn->CommitTrans();
            } catch(\Throwable $ee) {
                global $logger;
                $conn->RollbackTrans();
                $this->setErrorTopPage($ee->getMessage());

                $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);
                return;
            }

            $n = new \App\Entity\Notify();
            $n->user_id = \App\Entity\Notify::DELIV;
            $n->dateshow = time();
            $n->message = serialize(array('document_id' => $this->_doc->document_id));

            $n->save();
            $this->setInfo('sentdelivary');

        } else {  //в  производство
            $this->_doc->updateStatus(Document::STATE_INPROCESS);
            $n = new \App\Entity\Notify();
            $n->user_id = \App\Entity\Notify::ARMFOODPROD;
            $n->dateshow = time();
            $n->message = serialize(array('cmd' => 'new', 'document_id' => $this->_doc->document_id));

            $n->save();

            $this->setInfo('sentprod');

        }

        $this->onNewOrder();
    }

    // в  производство
    public function toprodOnClick($sender) {


        if ($this->createdoc() == false) {
            return;
        }

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
      
        try {
            
                $conn->Execute("delete from entrylist where document_id =" . $this->_doc->document_id);
                $conn->Execute("delete from iostate where document_id=" . $this->_doc->document_id);


     

                $n = new \App\Entity\Notify();
                $n->user_id = \App\Entity\Notify::ARMFOODPROD;
                $n->dateshow = time();
                $n->message = serialize(array('cmd' => 'update'));

            
            if( $this->_doc->state== Document::STATE_NEW)  {
                $this->_doc->updateStatus(Document::STATE_INPROCESS);
                $n->message = serialize(array('cmd' => 'new','document_id'=>$this->_doc->document_id));
                
            }
            $n->save();            

              
            $this->_doc = $this->_doc->cast();

            $this->_doc->DoStore();
            $this->_doc->save();
   
            $conn->CommitTrans();
        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setErrorTopPage($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);
            return;
        }


        $this->setInfo('sentprod');
        $this->onNewOrder();
    }

  // сохранить  
    public function tosaveOnClick($sender) {


        if ($this->createdoc() == false) {
            return;
        }

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
      
        try {
        

     
            
            if( $this->_doc->state != Document::STATE_NEW)  {
                $this->_doc->updateStatus(Document::STATE_EDITED);
                
            }

              
            $this->_doc = $this->_doc->cast();

            $this->_doc->save();
   
            $conn->CommitTrans();
        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setErrorTopPage($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);
            return;
        }


        $this->setInfo('sentprod');
        $this->onNewOrder();
    }


    //к  оплате
    public function topayOnClick($sender) {

        if ($this->createdoc() == false) {
            return;
        }
        $this->docpanel->payform->passfisc->setChecked(false);
 
        $this->docpanel->payform->setVisible(true);
        $this->docpanel->listsform->setVisible(false);
        $this->docpanel->navform->setVisible(false);
        $this->docpanel->payform->clean();

        $amount = $this->docpanel->listsform->totalamount->getText();
        $this->docpanel->payform->pfamount->setText(H::fa($amount));
        $disc = 0;
        if ($this->_doc->customer_id > 0) {
            $customer = \App\Entity\Customer::load($this->_doc->customer_id);
            $d= $customer->getDiscount();
            if ($d > 0) {
                $disc = round($amount * ($d / 100));
            } else {
                $bonus = $customer->getBonus();
                if ($bonus > 0) {
                    if ($amount < $bonus) {

                        $bonus = $amount;

                    }
                }
            }


        }


        $this->docpanel->payform->pfdisc->setText(H::fa($disc));
        $this->docpanel->payform->pfbonus->setText(H::fa($bonus));
        $this->docpanel->payform->pfforpay->setText(H::fa($amount - $disc));
        //  $this->docpanel->payform->pfpayed->setText(H::fa($amount))  ;
        $this->docpanel->payform->pfrest->setText(H::fa(0));
        $this->docpanel->payform->bbackitems->setVisible(true);


    }

    //Оплата
    public function payandcloseOnClick() {

        if ($this->_pt != 1 && $this->_pt != 2) {
            $this->setError("noselpaytype");
            return;
        }


        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();

        try {


            $this->_doc->payamount = $this->docpanel->payform->pfforpay->getText();
            $this->_doc->payed = $this->docpanel->payform->pfpayed->getText();
            $this->_doc->headerdata['exchange'] = $this->docpanel->payform->pfrest->getText();
            $this->_doc->headerdata['payed'] = $this->docpanel->payform->pfpayed->getText();

            $this->_doc->headerdata['bonus'] = $this->docpanel->payform->pfbonus->getText();
            $this->_doc->headerdata['paydisc'] = $this->docpanel->payform->pfdisc->getText();
            $this->_doc->headerdata['trans'] = $this->docpanel->payform->pftrans->getText();
            if ($this->_pt == 2) {
                $this->_doc->headerdata['payment'] = $this->setupform->beznal->getValue();
            } else {
                $this->_doc->headerdata['payment'] = $this->setupform->nal->getValue();
            }

            if ($this->_doc->payamount > $this->_doc->payed) {
                $this->setError("toolowamount");
                return;
            }
            $this->_doc->save();
            $this->_doc = $this->_doc->cast();
            $this->_doc->DoPayment();

            if ($this->_worktype == 0) {
                if ($this->_doc->state < 4) {
                    $this->_doc->DoStore();
                    $this->_doc->updateStatus(Document::STATE_EXECUTED);
                }

            }
            if ($this->_worktype == 1)  // в  производство
            {

                $this->_doc->updateStatus(Document::STATE_INPROCESS);
                $this->setInfo('sentprod');


            }
            //если  оплачен и  закончен   закрываем
            if ($this->_doc->payamount <= $this->_doc->payed && ($this->_doc->state == Document::STATE_EXECUTED || $this->_doc->state == Document::STATE_DELIVERED || $this->_doc->state == Document::STATE_FINISHED)) {
                    if($this->docpanel->payform->passfisc->isChecked()) {
                      $ret = \App\Modules\PPO\PPOHelper::check($this->_doc,true);
  
                    }   else {
                
                      if ($this->_pos->usefisc == 1 && $this->_tvars['ppo'] == true) {
                        $this->_doc->headerdata["fiscalnumberpos"]  =  $this->_pos->fiscalnumber;
         

                        $ret = \App\Modules\PPO\PPOHelper::check($this->_doc);
                        if ($ret['success'] == false && $ret['doclocnumber'] > 0) {
                            //повторяем для  нового номера
                            $this->_pos->fiscdocnumber = $ret['doclocnumber'];
                            $this->_pos->save();
                            $ret = \App\Modules\PPO\PPOHelper::check($this->_doc);
                        }
                        if ($ret['success'] == false) {
                            $this->setErrorTopPage($ret['data']);
                            $conn->RollbackTrans();
                            return;
                        } else {
                            
                            if ($ret['docnumber'] > 0) {
                                $this->_pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                                $this->_pos->save();
                                $this->_doc->headerdata["fiscalnumber"] = $ret['docnumber'];
                            } else {
                                $this->setError("ppo_noretnumber");
                                 $conn->RollbackTrans();
                                return;
                            }
                        }
                    }
     
                }
         
                $this->_doc->updateStatus(Document::STATE_CLOSED);
            }
            $conn->CommitTrans();

        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setErrorTopPage($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);
            return;
        }

        $check = $this->_doc->generatePosReport();

        $this->docpanel->checkpan->checktext->setText($check, true);
        $this->docpanel->checkpan->setVisible(true);
        $this->docpanel->payform->setVisible(false);
    }

    public function backItemsOnClick($sender) {
        $this->docpanel->listsform->setVisible(true);
        $this->docpanel->navform->setVisible(true);
        $this->docpanel->payform->setVisible(false);

    }

    public function createdoc() {

        $idnew = $this->_doc->document_id == 0;

        if (count($this->_itemlist) == 0) {
            $this->setError('noenterpos');
            return false;  
        }
        if ($idnew) {
            $this->_doc->document_number = $this->_doc->nextNumber();

            if (false == $this->_doc->checkUniqueNumber()) {
                $next = $this->_doc->nextNumber();
                $this->_doc->document_number = $next;
                if (strlen($next) == 0) {
                    $this->setError('docnumbercancreated');
                    return false;   
                }
            }
        }

        $this->_doc->document_date = time();
        $this->_doc->headerdata['time'] = time();
        $this->_doc->headerdata['contact'] = $this->docpanel->listsform->contact->getText();
        $this->_doc->notes = $this->docpanel->listsform->notes->getText();
        $this->_doc->customer_id = $this->docpanel->listsform->customer->getKey();
        if ($this->_doc->customer_id > 0) {
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docpanel->listsform->customer->getText() . ' ' . $customer->phone;
        }

        $this->_doc->headerdata['table'] = $this->docpanel->listsform->table->getText();
        $this->_doc->headerdata['pos'] = $this->_pos->pos_id;
        $this->_doc->headerdata['pos_name'] = $this->_pos->pos_name;
        $this->_doc->headerdata['store'] = $this->_store;
        $this->_doc->headerdata['pricetype'] = $this->_pt;

        $this->_doc->firm_id = $this->_pos->firm_id;
        $this->_doc->username = System::getUser()->username;

        $firm = H::getFirmData($this->_doc->firm_id);
        $this->_doc->headerdata["firm_name"] = $firm['firm_name'];
        $this->_doc->headerdata["inn"] = $firm['inn'];
        $this->_doc->headerdata["address"] = $firm['address'];
        $this->_doc->headerdata["phone"] = $firm['phone'];

        $this->_doc->packDetails('detaildata', $this->_itemlist);
        $this->_doc->amount = $this->docpanel->listsform->totalamount->getText();
        $this->_doc->payamount = $this->_doc->amount;

        $this->_doc->save();

        if ($idnew) {
            $this->_doc->updateStatus(Document::STATE_NEW);
        }


        return true;
    }


    //добавление нового контрагента
    public function addcustOnClick($sender) {
        $this->editcust->setVisible(true);
        $this->docpanel->listsform->setVisible(false);

        $this->editcust->editcustname->setText('');
        $this->editcust->editaddress->setText('');
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
        $cust->address = $this->editcust->editaddress->getText();
        $cust->phone = $this->editcust->editphone->getText();
        $cust->phone = \App\Util::handlePhone($cust->phone);

        if (strlen($cust->phone) > 0 && strlen($cust->phone) != H::PhoneL()) {
            $this->setError("");
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
        $cust->type = Customer::TYPE_BAYER;
        $cust->save();
        $this->docpanel->listsform->customer->setText($cust->customer_name);
        $this->docpanel->listsform->customer->setKey($cust->customer_id);

        $this->editcust->setVisible(false);
        $this->docpanel->listsform->setVisible(true);

    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docpanel->listsform->setVisible(true);
    }

    public function OnChangeCustomer($sender) {


        $customer_id = $sender->getKey();
        if ($customer_id > 0) {
            $customer = Customer::load($customer_id);
            if ("" == trim($this->docpanel->listsform->address->getText())) {
                $this->docpanel->listsform->address->setText($customer->address);

            }
            if ("" == trim($this->docpanel->listsform->contact->getText())) {

                $this->docpanel->listsform->contact->setText($customer->customer_name . ', ' . $customer->phone);
            }



        } else {
            return;
        }


    }


    public function getMessages($args, $post) {

        $cntorder = 0;
        $cntprod = 0;
        $mlist = \App\Entity\Notify::find("checked <> 1 and user_id=" . \App\Entity\Notify::ARMFOOD);
        foreach ($mlist as $n) {
            $msg = @unserialize($n->message);

            $doc = Document::load(intval($msg['document_id']));
            if ($doc->state == Document::STATE_FINISHED || $doc->state == Document::STATE_CLOSED) {
                $cntprod++;
            }
            if ($doc->state == Document::STATE_NEW) {
                $cntorder++;
            }
        }

        \App\Entity\Notify::markRead(\App\Entity\Notify::ARMFOOD);

        return json_encode(array("cntprod" => $cntprod, 'cntorder' => $cntorder), JSON_UNESCAPED_UNICODE);
    }

    //фискализация
    public function OnOpenShift() {
        $ret = \App\Modules\PPO\PPOHelper::shift($this->_pos->pos_id, true);
        if ($ret['success'] == false && $ret['doclocnumber'] > 0) {
            //повторяем для  нового номера
            $this->_pos->fiscdocnumber = $ret['doclocnumber'];
            $this->_pos->save();
            $ret = \App\Modules\PPO\PPOHelper::shift($this->_pos->pos_id, true);
        }
        if ($ret['success'] == false) {
            $this->setErrorTopPage($ret['data']);
            return false;
        } else {
            $this->setSuccess("ppo_shiftopened");
            if ($ret['doclocnumber'] > 0) {
                $this->_pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                $this->_pos->save();
             //   $this->_doc->headerdata["fiscalnumber"] = $ret['docnumber'];
            }  
            \App\Modules\PPO\PPOHelper::clearStat($this->_pos->pos_id);
        }


        $this->_pos->save();
        return true;
    }

    public function OnCloseShift($sender) {
        $ret = $this->zform();
        if ($ret == true) {
            $this->closeshift();
        }
    }

    public function zform() {

        $stat = \App\Modules\PPO\PPOHelper::getStat($this->_pos->pos_id);
        $rstat = \App\Modules\PPO\PPOHelper::getStat($this->_pos->pos_id, true);

        $ret = \App\Modules\PPO\PPOHelper::zform($this->_pos->pos_id, $stat, $rstat);
        if (strpos($ret['data'], 'ZRepAlreadyRegistered')) {
            return true;
        }
        if ($ret['success'] == false && $ret['doclocnumber'] > 0) {
            //повторяем для  нового номера
            $this->_pos->fiscdocnumber = $ret['doclocnumber'];
            $this->_pos->save();
            $ret = \App\Modules\PPO\PPOHelper::zform($this->_pos->pos_id, $stat, $rstat);
        }
        if ($ret['success'] == false) {
            $this->setErrorTopPage($ret['data']);
            return false;
        } else {

            if ($ret['doclocnumber'] > 0) {
                $this->_pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                $this->_pos->save();
            } else {
                $this->setError("ppo_noretnumber");
                return;
            }
        }


        return true;
    }

    public function closeshift() {
        $ret = \App\Modules\PPO\PPOHelper::shift($this->_pos->pos_id, false);
        if ($ret['success'] == false && $ret['doclocnumber'] > 0) {
            //повторяем для  нового номера
            $this->_pos->fiscdocnumber = $ret['doclocnumber'];
            $this->_pos->save();
            $ret = \App\Modules\PPO\PPOHelper::shift($this->_pos->pos_id, false);
        }
        if ($ret['success'] == false) {
            $this->setErrorTopPage($ret['data']);
            return false;
        } else {
            $this->setSuccess("ppo_shiftclosed");
            if ($ret['doclocnumber'] > 0) {
                $this->_pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                $this->_pos->save();
            }  
            \App\Modules\PPO\PPOHelper::clearStat($this->_pos->pos_id);
        }


        return true;
    }

  public function OnPrint($sender) {
     
 
        if(intval(\App\System::getUser()->prtype ) == 0){
  
              
            $this->addAjaxResponse("  $('#checktext').printThis() ");
         
            return;
        }
        
     try{
        $doc = $this->_doc->cast();
        $xml = $doc->generatePosReport(true);

        $buf = \App\Printer::xml2comm($xml);
        $b = json_encode($buf) ;                   
          
        $this->addAjaxResponse("  sendPS('{$b}') ");      
      }catch(\Exception $e){
           $message = $e->getMessage()  ;
           $message = str_replace(";","`",$message)  ;
           $this->addAjaxResponse(" toastr.error( '{$message}' )         ");  
                   
        }
 
    }        
    
}

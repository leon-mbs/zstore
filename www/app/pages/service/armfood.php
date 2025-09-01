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
    public $_itemlist = array();
    public $_catlist  = array();
    public $_prodlist = array();
    public $_doclist  = array();

    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowSer('ARMFood')) {
            return;
        }
        $food = System::getOptions("food");
        if (!is_array($food)) {
            $food = array();
            $this->setWarn('Не вказано параметри в  налаштуваннях');
        }
        $this->_worktype = intval( $food['worktype'] );

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
        $this->setupform->add(new ClickLink('options', $this, 'onOptions'));
   
        //список  заказов
        $this->add(new Panel('orderlistpan'))->setVisible(false);

        $this->orderlistpan->add(new ClickLink('neworder', $this, 'onNewOrder'));
        $this->orderlistpan->add(new DataView('orderlist', new ArrayDataSource($this, '_doclist'), $this, 'onDocRow'));
        $this->orderlistpan->add(new \Zippy\Html\DataList\Paginator('pag', $this->orderlistpan->orderlist));
        $this->orderlistpan->orderlist->setPageSize(H::getPG());

        $this->orderlistpan->add(new ClickLink('refresh'))->onClick($this, 'updateorderlist');

        $this->orderlistpan->add(new Form('searchform'))->onSubmit($this, 'updateorderlist');
        $this->orderlistpan->searchform->add(new AutocompleteTextInput('searchcust'))->onText($this, 'OnAutoCustomer');
        $this->orderlistpan->searchform->add(new TextInput('searchnumber', $filter->searchnumber));

        //панель статуса,  просмотр
        $this->orderlistpan->add(new Panel('statuspan'))->setVisible(false);


        $this->orderlistpan->statuspan->add(new \App\Widgets\DocView('docview'))->setVisible(false);

        //оформление заказа

        $this->add(new Panel('docpanel'))->setVisible(false);

        $this->docpanel->add(new Panel('catpan'))->setVisible(false);
        $this->docpanel->catpan->add(new DataView('catlist', new ArrayDataSource($this, '_catlist'), $this, 'onCatRow'));
        $this->docpanel->catpan->add(new ClickLink('stopcat', $this, 'onStopCat'));

        $this->docpanel->add(new Panel('prodpan'))->setVisible(false);
        $this->docpanel->prodpan->add(new DataView('prodlist', new ArrayDataSource($this, '_prodlist'), $this, 'onProdRow'));
        $this->docpanel->prodpan->add(new ClickLink('stopprod', $this, 'onStopCat'));

        $this->docpanel->add(new Form('navbar'));
        $this->docpanel->navbar->add(new ClickLink('toorderlist', $this, 'onOrderList'));
        $this->docpanel->navbar->add(new ClickLink('openshift', $this, 'OnOpenShift'));
        $this->docpanel->navbar->add(new ClickLink('closeshift', $this, 'OnCloseShift'));
      
        $this->docpanel->add(new Form('navform'));

        $this->docpanel->navform->add(new TextInput('barcode'));
        $this->docpanel->navform->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');

        $this->docpanel->navform->add(new SubmitButton('baddnewpos'))->onClick($this, 'addnewposOnClick');

        $this->docpanel->navform->add(new AutocompleteTextInput('itemfast'))->onText($this, 'OnAutoItem');
        $this->docpanel->navform->add(new SubmitButton('addfast'))->onClick($this, 'addfastOnClick');

        $this->docpanel->navform->add(new TextInput('promocode'));
        $this->docpanel->navform->promocode->setVisible(\App\Entity\PromoCode::findCnt('') > 0);
        
        


        $this->docpanel->add(new Form('listsform'))->setVisible(false);
        $this->docpanel->listsform->add(new DataView('itemlist', new ArrayDataSource($this, '_itemlist'), $this, 'onItemRow'));

        $this->docpanel->listsform->add(new SubmitButton('btosave'))->onClick($this, 'tosaveOnClick');
        $this->docpanel->listsform->add(new SubmitButton('btopay'))->onClick($this, 'topayOnClick');
        $this->docpanel->listsform->add(new SubmitButton('btoprod'))->onClick($this, 'toprodOnClick');
        $this->docpanel->listsform->add(new SubmitButton('btodel'))->onClick($this, 'todelOnClick');
        $this->docpanel->listsform->add(new Label('totalamount', "0"));
        $this->docpanel->listsform->add(new TextInput('totaldisc', "0"));
        $this->docpanel->listsform->add(new TextInput('bonus', "0"));

        $this->docpanel->listsform->add(new DropDownChoice('execuser', \App\Entity\User::findArray('username', 'disabled<>1', 'username')));
        $this->docpanel->listsform->add(new CheckBox('forbar'));
        $this->docpanel->listsform->add(new TextInput('address'));
        $this->docpanel->listsform->add(new Date('dt', time()));
        $this->docpanel->listsform->add(new \Zippy\Html\Form\Time('time'));
        $this->docpanel->listsform->add(new TextInput('notes'));
        $this->docpanel->listsform->add(new TextInput('contact'));
        $this->docpanel->listsform->add(new TextInput('table'));
        $this->docpanel->listsform->add(new DropDownChoice('delivery', Document::getDeliveryTypes(), 0))->onChange($this, 'OnDelivery');
        $this->docpanel->listsform->add(new ClickLink('addcust'))->onClick($this, 'addcustOnClick');
        $this->docpanel->listsform->add(new Label('custinfo'))->setVisible(false);

        $this->docpanel->listsform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docpanel->listsform->customer->onChange($this, 'OnChangeCustomer');
        $this->docpanel->listsform->add(new \Zippy\Html\Link\BookmarkableLink('cinfo'));

        $this->docpanel->listsform->add(new TextInput('editqtyi'));
        $this->docpanel->listsform->add(new TextInput('editqtyq'));
        $this->docpanel->listsform->add(new SubmitButton('beditqty'))->onClick($this, 'editqtyOnClick');

        $this->docpanel->add(new Form('payform'))->setVisible(false);

        $this->docpanel->payform->add(new TextInput('pfforpay'));
        $this->docpanel->payform->add(new TextInput('pfpayed'));
        $this->docpanel->payform->add(new TextInput('pfrest'));
        $this->docpanel->payform->add(new TextInput('pftrans'));

        $this->docpanel->payform->add(new TextInput('pfexch2b'));

        $this->docpanel->payform->add(new CheckBox('passfisc'));
        $this->docpanel->payform->add(new CheckBox('passprod'));

        $bind = new  \Zippy\Binding\PropertyBinding($this, '_pt');
        $this->docpanel->payform->add(new \Zippy\Html\Form\RadioButton('pfnal', $bind, 1));
        $this->docpanel->payform->add(new \Zippy\Html\Form\RadioButton('pfbeznal', $bind, 2));

        $this->docpanel->payform->add(new ClickLink('bbackitems'))->onClick($this, 'backItemsOnClick');
        $this->docpanel->payform->add(new SubmitButton('btocheck'))->onClick($this, 'payandcloseOnClick');
        $this->docpanel->add(new Panel('checkpan'))->setVisible(false);
        $this->docpanel->checkpan->add(new ClickLink('bnewcheck'))->onClick($this, 'onNewOrder');
        $this->docpanel->checkpan->add(new Label('checktext'));
        $this->docpanel->checkpan->add(new Button('btoprint'))->onClick($this, "OnPrint", true);

        $this->docpanel->add(new Panel('billpan'))->setVisible(false);
        $this->docpanel->billpan->add(new ClickLink('bnewcheck2'))->onClick($this, 'onNewOrder');
        $this->docpanel->billpan->add(new Label('billtext'));
        $this->docpanel->billpan->add(new Button('btoprintbill'))->onClick($this, "OnPrintBill", true);

        //добавление нового контрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new TextInput('editaddress'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        $this->OnDelivery($this->docpanel->listsform->delivery);
        
        
  
        $this->add(new Form('optionsform'))->onSubmit($this, 'saveOptions');
        $this->optionsform->add(new DropDownChoice('foodpricetype', \App\Entity\Item::getPriceTypeList(), $food['pricetype']));
        $this->optionsform->add(new DropDownChoice('foodpricetypeout', \App\Entity\Item::getPriceTypeList(), $food['pricetypeout']??0));
        $this->optionsform->add(new DropDownChoice('foodworktype', array(), $food['worktype']));
        $this->optionsform->add(new CheckBox('fooddelivery', $food['delivery']));
        $this->optionsform->add(new CheckBox('foodtables', $food['tables']));
        $this->optionsform->add(new CheckBox('foodpack', $food['pack']));

        $this->optionsform->add(new Textinput('goodname', $food['name']));
        $this->optionsform->add(new Textinput('goodaddress', $food['address']));
        $this->optionsform->add(new Textinput('goodphone', $food['phone']));
        $this->optionsform->add(new Textinput('timepn', $food['timepn']));
        $this->optionsform->add(new Textinput('timesa', $food['timesa']));
        $this->optionsform->add(new Textinput('timesu', $food['timesu']));
        $this->optionsform->setVisible(false) ;

        $menu= \App\Entity\Category::findArray('cat_name', "detail  not  like '%<nofastfood>1</nofastfood>%' and coalesce(parent_id,0)=0",'cat_name')  ;
       
        $this->optionsform->add(new DropDownChoice('foodbasemenu',$menu,$food['foodbasemenu']));
        $this->optionsform->add(new DropDownChoice('foodmenu2',$menu,$food['foodmenu2']));
        $this->optionsform->add(new DropDownChoice('foodmenu3',$menu,$food['foodmenu3']));
        $this->optionsform->add(new DropDownChoice('foodmenu4',$menu,$food['foodmenu4']));
        
        
        
    }

    public function setupOnClick($sender) {
        $store = $this->setupform->store->getValue();
        $nal = $this->setupform->nal->getValue();
        $beznal = $this->setupform->beznal->getValue();

        $this->_pos = \App\Entity\Pos::load($this->setupform->pos->getValue());

        if ($store == 0 || $nal == 0 || $beznal == 0 || $this->_pos == null) {
            $this->setError("Не зазначено всі дані");
            return;
        }
        $filter = \App\Filter::getFilter("armfood");


        $filter->store = $store;
        $filter->pos = $this->_pos->pos_id;

        $filter->nal = $nal;
        $filter->beznal = $beznal;
        $this->_store = $store;
        $this->_pricetype = $filter->pricetype;

        if($this->_pos->usefisc != 1) {
            $this->_tvars['fiscal']  = false;
        }
 
        $this->_tvars['fiscaltestmode']  = $this->_pos->testing==1;


        $this->setupform->setVisible(false);

        $this->onNewOrder();
    }

    public function onNewOrder($sender = null) {
        //  $this->orderlistpan->statuspan->setVisible(true);
        $this->docpanel->setVisible(true);

        $this->docpanel->listsform->setVisible(true);
        $forbar=$this->docpanel->listsform->forbar->isChecked() ? 1 : 0;
        $this->docpanel->listsform->clean();
        $this->docpanel->listsform->forbar->setChecked($forbar) ;
        $this->docpanel->listsform->dt->setDate(time());
        $this->docpanel->listsform->time->setDateTime(time() + 3600);
        $this->docpanel->navform->setVisible(true);
        $this->docpanel->navform->clean();

        $this->orderlistpan->setVisible(false);
        $this->docpanel->checkpan->setVisible(false);

        $this->_doc = \App\Entity\Doc\Document::create('OrderFood');
        $this->_doc->headerdata['delivery'] = 0;

        $this->_itemlist = array();

        $this->docpanel->listsform->itemlist->Reload();
        $this->calcTotal();
        $this->orderlistpan->searchform->clean();

        $this->docpanel->listsform->delivery->setValue(0);
        //      $this->docpanel->listsform->execuser->setValue(0);
        $this->OnDelivery($this->docpanel->listsform->delivery);

        $this->docpanel->listsform->addcust->setVisible(true) ;
        $this->docpanel->listsform->cinfo->setVisible(false) ;


    }

    public function OnDelivery($sender) {
        $this->docpanel->listsform->contact->setVisible(false);
        $this->docpanel->listsform->address->setVisible(false);
        $this->docpanel->listsform->dt->setVisible(false);
        $this->docpanel->listsform->time->setVisible(false);
        $this->docpanel->listsform->table->setVisible(false);
    //    $this->docpanel->listsform->btopay->setVisible(false);
        $this->docpanel->listsform->btodel->setVisible(false);
        $this->docpanel->listsform->btoprod->setVisible(false);

        if ($sender->getValue() == 0) {
            $this->docpanel->listsform->table->setVisible(true);
            if ($this->_worktype == 0 || $this->_worktype == 1) {
       //         $this->docpanel->listsform->btopay->setVisible(true);
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

    public function addfastOnClick($sender) {
         $key=$this->docpanel->navform->itemfast->getKey();  

         if($key >0){
             
           $item = Item::load($key);
           $item = $this->calcitem($item);
     
           $this->addItem($item);
 
         }
         
         $this->docpanel->navform->itemfast->setKey(0);  
         $this->docpanel->navform->itemfast->setText('');  
         
    }
 
    public function OnAutoItem($sender) {
        $text = trim($sender->getText());
        $like = Item::qstr('%' . $text . '%');
         
        return Item::findArray('itemname',"disabled<>1  and  item_type in (1,4,5 )  and  (itemname like {$like} or item_code like {$like} ) and cat_id in (select cat_id from item_cat where detail  not  like '%<nofastfood>1</nofastfood>%') "  );        
        

    }
    
    
    public function addnewposOnClick($sender) {
        $this->docpanel->catpan->setVisible(true);
        $this->docpanel->prodpan->setVisible(false);
        $this->docpanel->listsform->setVisible(false);
        $this->docpanel->navform->setVisible(false);


        $this->_catlist = Category::find(" cat_id in(select cat_id from  items where  disabled <>1  ) and detail  not  like '%<nofastfood>1</nofastfood>%' ");
        usort($this->_catlist, function ($a, $b) {
            return $a->order > $b->order;
        });

        $this->docpanel->catpan->catlist->Reload();
    }

    
    
    
    //список  заказов
    public function onDocRow($row) {
        $doc = $row->getDataItem();
        $doc = $doc->cast();
        $row->add(new ClickLink('docnumber', $this, 'OnDocViewClick'))->setValue($doc->document_number);
        $row->add(new Label('state', Document::getStateName($doc->state)));
        $row->add(new Label('delivery'))->setVisible(($doc->headerdata['delivery'] ??0) >0);

        $row->add(new Label('docamount', H::fa(($doc->payamount > 0) ? $doc->payamount : ($doc->amount > 0 ? $doc->amount : ""))));

        $row->add(new Label('author', $doc->username));
        $row->add(new Label('docnotes', $doc->notes));
        $row->add(new Label('tablenumber', $doc->headerdata['table']));
        $row->add(new Label('rtlist'));
        $time= H::ft($doc->headerdata['time']) ;
        
        if(date('Ymd') !=date('Ymd',$doc->headerdata['time'])) {
           $time= H::fdt($doc->headerdata['time']) ;   //не  сегодня
        }
        
        $row->add(new Label('doctime',$time));
        
        
        
        $t ="<table   style=\"font-size:smaller\"> " ;

        $tlist=  $doc->unpackDetails('detaildata')  ;

        foreach($tlist as $prod) {
            $t .="<tr> " ;
            $t .="<td style=\"padding:2px\" >{$prod->itemname} </td>" ;
            $t .="<td style=\"padding:2px\" class=\"text-right\">". H::fa($prod->quantity) ."</td>" ;
            $t .="<td style=\"padding:2px\" class=\"text-right\">". H::fa($prod->price) ."</td>" ;
            $t .="<td style=\"padding:2px\" class=\"text-right\">". H::fa($prod->quantity * $prod->price) ."</td>" ;
            $t .=  ($prod->myself==1 ? "<td style=\"padding:2px\"> <i class=\"fa fa-bag-shopping\"></i>  </td>" : "<td style=\"padding:2px\">   </td>")    ;
            $t .="</tr> " ;
        }

        $t .="</table> " ;

        $row->rtlist->setText($t, true);


        $row->add(new ClickLink('brpay', $this, 'OnStatus')) ;
        $row->add(new ClickLink('brprint', $this, 'OnStatus')) ;
        $row->add(new ClickLink('bredit', $this, 'OnStatus')) ;
        $row->add(new ClickLink('brclose', $this, 'OnStatus')) ;
        $row->add(new ClickLink('brrefuse', $this, 'OnStatus')) ;
        $row->add(new ClickLink('brrunner', $this, 'OnPrintRunner',true)) ;

        $row->brpay->setVisible(false);
        $row->brprint->setVisible(false);
        $row->brclose->setVisible(false);
        $row->brrefuse->setVisible(false);
        $row->bredit->setVisible(false);
        $row->brrunner->setVisible(false);


        $haspayment = $doc->hasPayments() ;
        $inprod = $doc->inProcess()  ;
        $hasstore = $doc->hasStore()  ;

        if ($doc->state < 4 || $doc->state == Document::STATE_INPROCESS) {
            $row->bredit->setVisible(true);
            $row->brrefuse->setVisible(true);
        }

        if ($doc->payamount > $doc->payed && $doc->state > 4) { //к  оплате
            $row->brpay->setVisible(true);
        }
        if ($doc->payamount == $doc->payed && $hasstore) {
            $row->brclose->setVisible(true);
            $row->brprint->setVisible(false);
        }
        if ($haspayment) {
            $row->bredit->setVisible(false);
            $row->brprint->setVisible(false);
        }
        if ($haspayment== false && $doc->state>4 ) {
            $row->brprint->setVisible(true);
        }
        
        if ( $doc->state>4   ) {
           $row->brrunner->setVisible(true);
        }
        
        
        if ($inprod) {
            $row->brclose->setVisible(false);
        }



        if ($doc->state == Document::STATE_READYTOSHIP
            || $doc->state == Document::STATE_DELIVERED
            || $doc->state == Document::STATE_CLOSED
            || $doc->state == Document::STATE_FAIL
            || $doc->state == Document::STATE_INSHIPMENT
        ) {
            $row->brpay->setVisible(false);
            $row->brclose->setVisible(false);
            $row->brrefuse->setVisible(false);
            $row->bredit->setVisible(false);
            $row->brprint->setVisible(false);
            $row->brrunner->setVisible(false);
        }
     
        if ($doc->state == Document::STATE_WP   ) {
            $row->brpay->setVisible(true);
            $row->brclose->setVisible(false);
            $row->brrefuse->setVisible(false);
            $row->bredit->setVisible(false);

        }

        $row->add(new ClickLink('checkfisc', $this, "onFisc"))->setVisible(($doc->headerdata['passfisc'] ?? "") == 1) ;
        if($doc->state <5) {
           $row->checkfisc->setVisible(false);
        }
        if($this->_pos->usefisc != 1) {
           $row->checkfisc->setVisible(false);
        }
        
 
        

    }

    public function updateorderlist($sender) {
        $conn = \ZDB\DB::getConnect();
        $where = " (state not in(9) or content like '%<passfisc>1</passfisc>%' ) and  document_date  >= " . $conn->DBDate(strtotime('-1 week'))    ;
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
        $row->catbtn->add(new Image('catimage',   $cat->getImageUrl()));
    }

    
    private function calcitem($prod){
         $customer_id = $this->docpanel->listsform->customer->getKey()  ;
         $prod->price = $prod->getPriceEx(
            array(
              'pricetype'=>$this->_pricetype,
              'store'=>$this->_store,
              'customer'=>$customer_id)
        );

        $prod->pureprice = $prod->getPurePrice($this->_pricetype, $this->_store);

        $prod->disc=0;
        if($prod->price >0 && $prod->pureprice >0) {
            $prod->disc = number_format((1 - ($prod->price/($prod->pureprice)))*100, 1, '.', '') ;
        }
        if($prod->disc < 0) {
            $prod->disc=0;
        }

        if($prod->disc ==0 && $customer_id >0) {
            $c = Customer::load($customer_id) ;
            $d = $c->getDiscount();
            if($d >0) {
                $prod->disc = $d;
                $prod->price = H::fa($prod->pureprice - ($prod->pureprice*$d/100)) ;
            }
        }    
        
        
        return $prod; 
    }
    
    //товары
    public function onProdRow($row) {
        //  $store_id = $this->setupform->store->getValue();
 
        $prod = $row->getDataItem();

        $prod = $this->calcitem($prod);
        
        $row->add(new Panel('prodbtn'))->onClick($this, 'onProdBtnClick');
        $row->prodbtn->add(new Label('prodname', $prod->itemname));
        $row->prodbtn->add(new Label('prodprice', H::fa($prod->price)));
        $row->prodbtn->add(new Image('prodimage', $prod->getImageUrl()));
    }

    //выбрана  группа
    public function onCatBtnClick($sender) {
        $cat = $sender->getOwner()->getDataItem();
        $catlist = Category::find(" cat_id in(select cat_id from  items where  disabled <>1  ) and detail  not  like '%<nofastfood>1</nofastfood>%' and   coalesce(parent_id,0)= " . $cat->cat_id, "cat_name");
        if (count($catlist) > 0) {
            $this->_catlist = $catlist;
            $this->docpanel->catpan->catlist->Reload();
        } else {
            $this->_prodlist = Item::find('disabled<>1  and  item_type in (1,4,5 )  and cat_id=' . $cat->cat_id);
            $this->docpanel->catpan->setVisible(false);
            $this->docpanel->prodpan->setVisible(true);
            $this->docpanel->prodpan->prodlist->Reload();
        }

    }

    // выбран  товар
    private function addItem($item) {

         
        $store_id = $this->setupform->store->getValue();

        $qty = $item->getQuantity($store_id);
        if ($qty <= 0 && $item->autoincome != 1) {

            $this->setWarn("Товару {$item->itemname} немає на складі");
        }

        $found=false;
        foreach($this->_itemlist as $i=>$it) {
            if($it->item_id==$item->item_id && intval($it->foodstate)==0) {
                $this->_itemlist[$i]->quantity++;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $item->myself = $this->_worktype == 0;
            if ($this->_tvars['pack'] == false) {
                $item->myself = 0;
            }
            $item->quantity = 1;
            $item->foodstate = 0;
            // $item->price = $item->getPrice($this->_pricetype, $this->_store);
            $this->_itemlist[] = $item;
        }

       $this->setSuccess("Позиція додана");
        $this->docpanel->listsform->itemlist->Reload();
        $this->calcTotal(); 
    }
    
    public function onProdBtnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        
        $this->addItem($item);


        $this->_catlist = Category::find(" cat_id in(select cat_id from  items where  disabled <>1  ) and detail  not  like '%<nofastfood>1</nofastfood>%' ");
        usort($this->_catlist, function ($a, $b) {
            return $a->order > $b->order;
        });
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
        $code = ltrim($code, '0');

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

            $this->setWarn("Товар з кодом `{$code}` не знайдено");
            return;
        }


        $qty = $item->getQuantity($store_id);
        if ($qty <= 0) {

            $this->setWarn("Товару {$item->itemname} немає на складі");
        }


        $price = $item->getPrice($this->_pricetype, $store_id);
        $item->price = $price;
        $pureprice = $item->getPurePrice($this->_pricetype, $store_id);
        $item->pureprice = $pureprice;
        $item->disc=0;
        if($item->price >0 && $item->pureprice >0) {
            $item->disc = number_format((1 - ($item->price/($item->pureprice)))*100, 1, '.', '') ;
        }
        if($item->disc < 0) {
            $item->disc=0;
        }

        $customer_id = $this->docpanel->listsform->customer->getKey()  ;

        if($item->disc ==0 && $customer_id >0) {
            $c = Customer::load($customer_id) ;
            $d = $c->getDiscount();
            if($d >0) {
                $item->disc = $d;
                $item->price = H::fa($item->pureprice - ($item->pureprice*$d/100)) ;

            }
        }


        $item->quantity = 1;
        $item->myself = $this->_worktype == 0;
        if ($this->_tvars['pack'] == false) {
            $item->myself = 0;
        }
        $this->_itemlist[] = $item;


        $this->docpanel->listsform->itemlist->Reload();
        $this->calcTotal();


    }

    //список позиций
    public function onItemRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('itemname', $item->itemname));
        $row->add(new Label('item_code', $item->item_code));
        $qty = H::fqty($item->quantity) ;
        $row->add(new Label('qty', $qty));


        $row->add(new Label('disc',doubleval($item->disc)  ==0 ?"" : '-'. H::fa1($item->disc ??0)));
        $row->add(new Label('price', H::fa($item->price)));
        $row->add(new Label('amount', H::fa($item->price * $item->quantity)));
        $row->add(new ClickLink('myselfon', $this, 'onMyselfClick'))->setVisible($item->myself == 1);
        $row->add(new ClickLink('myselfoff', $this, 'onMyselfClick'))->setVisible($item->myself != 1);
        $row->add(new ClickLink('qtymin'))->onClick($this, 'onQtyClick');
        $row->add(new ClickLink('qtyplus'))->onClick($this, 'onQtyClick');
        $row->add(new ClickLink('removeitem'))->onClick($this, 'onDelItemClick');
        $rowid =  array_search($item, $this->_itemlist, true);

        $row->add(new Label('qtyedit'))->setAttribute('onclick', "qtyedit({$rowid},{$qty})") ;




        $state="Новий";
        if ($item->foodstate == 1) {
            $state="В черзi";
        }
        if ($item->foodstate == 2) {
            $state="Готуєтся";
        }
        if ($item->foodstate == 3) {
            $state="Готово";
        }
        if ($item->foodstate == 4) {
            $state="Видано";
        }
        $row->add(new Label('state', $state));

        if ($item->foodstate > 0) {
            $row->removeitem->setVisible(false);
            $row->myselfon->setVisible(false);
            $row->myselfoff->setVisible(false);
            $row->qtymin->setVisible(false);
            $row->qtyplus->setVisible(false);
            $row->qtyedit->setVisible(false);

        }    
        if ($item->foodstate ==1 ) {
            $row->removeitem->setVisible(true);
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

        $rowid =  array_search($item, $this->_itemlist, true);

        $this->_itemlist = array_diff_key($this->_itemlist, array($rowid => $this->_itemlist[$rowid]));

        $this->docpanel->listsform->itemlist->Reload();
        $this->calcTotal();
    }

    public function OnDocViewClick($sender) {

        $this->_doc = Document::load($sender->getOwner()->getDataItem()->document_id);
        $this->OnDocView();

    }

    public function OnDocView() {
        $this->orderlistpan->statuspan->setVisible(true);



        $this->orderlistpan->statuspan->docview->setDoc($this->_doc);
        $this->orderlistpan->orderlist->Reload(false);

        $this->goAnkor('dankor');
    }

    public function onStatus($sender) {
        $this->_doc = Document::load($sender->getOwner()->getDataItem()->document_id);
        $this->_doc = $this->_doc->cast();
    
        $this->docpanel->billpan->setVisible(false);
       
        if (strpos($sender->id, 'bredit') === 0) {
            $this->orderlistpan->setVisible(false);
            $this->orderlistpan->statuspan->setVisible(false);
            $this->docpanel->setVisible(true);
            $this->docpanel->navform->setVisible(true);
            $this->docpanel->listsform->setVisible(true);
            $this->docpanel->listsform->clean();

            $this->docpanel->listsform->notes->setText($this->_doc->notes);
            $this->docpanel->listsform->table->setText($this->_doc->headerdata['table']);
            $this->docpanel->navform->promocode->setText($this->_doc->headerdata['promocode']);
            $this->docpanel->listsform->bonus->setText($this->_doc->headerdata['bonus']);
            $this->docpanel->listsform->totaldisc->setText($this->_doc->headerdata['totaldisc']);
            $this->docpanel->listsform->addcust->setVisible(false) ;
            $this->docpanel->listsform->cinfo->setVisible(true) ;
            $this->docpanel->listsform->forbar->setChecked($this->_doc->headerdata['forbar']);
            $this->docpanel->listsform->execuser->SetValue($this->_doc->user_id);
            $this->docpanel->listsform->btopay->setVisible(true);
  
            if ($this->_doc->customer_id > 0) {
                $this->docpanel->listsform->customer->setKey($this->_doc->customer_id);
                $this->docpanel->listsform->customer->setText($this->_doc->customer_name);
                $this->OnChangeCustomer($this->docpanel->listsform->customer) ;
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
        if (strpos($sender->id, 'brclose') === 0) {
            $this->_doc->updateStatus(Document::STATE_CLOSED);

        }
        if (strpos($sender->id, 'brrefuse') === 0) {
            $this->_doc->updateStatus(Document::STATE_FAIL);

        }
        if (strpos($sender->id, 'brprint') === 0) {
            $this->_doc->updateStatus(Document::STATE_WP);
            $this->docpanel->setVisible(true);

            $this->orderlistpan->statuspan->setVisible(false);
            $this->orderlistpan->setVisible(false);

            $this->docpanel->billpan->setVisible(true);
            $this->docpanel->listsform->setVisible(false);
            $this->docpanel->navform->setVisible(false);
            $check = $this->_doc->generatePosReport(false,true);

            $this->docpanel->billpan->billtext->setText($check, true);
              
            

        }
        if (strpos($sender->id, 'brpay') === 0) {

            $this->docpanel->setVisible(true);

            $this->orderlistpan->statuspan->setVisible(false);
            $this->orderlistpan->setVisible(false);

            $this->docpanel->payform->setVisible(true);
            $this->docpanel->listsform->setVisible(false);
            $this->docpanel->navform->setVisible(false);
            $this->docpanel->payform->clean();
            $amount = $this->_doc->payamount;
            $bonus = $this->_doc->headerdata["bonus"] ;
            $totaldisc = $this->_doc->headerdata["totaldisc"] ;
            $amount =  H::fa($amount - floatval($totaldisc)  - floatval($bonus)) ;


            $this->docpanel->payform->pfforpay->setText(H::fa($amount));

            $this->docpanel->payform->pfrest->setText(H::fa(0));
            $this->docpanel->payform->bbackitems->setVisible(false);
          
            $inprod = $this->_doc->checkStates([Document::STATE_INPROCESS]) ;//уже в  производстве
            $this->docpanel->payform->passprod->setVisible($this->_worktype > 0 && $inprod == false);

            return;
        }
        //   $this->orderlistpan->statuspan->setVisible(false);


        $this->updateorderlist(null);

    }

    public function calcTotal() {
        $amount = 0;

        foreach ($this->_itemlist as $item) {
            $amount += H::fa($item->quantity * $item->price);
        }
        
        $code= trim($this->docpanel->navform->promocode->getText());
        if($code != '') {
            $r = \App\Entity\PromoCode::check($code,$this->docpanel->listsform->customer->getKey())  ;
            if($r == ''){
                $p = \App\Entity\PromoCode::findByCode($code);
                $disc = doubleval($p->disc );
                $discf = doubleval($p->discf );
                 
                if($disc >0)  {
                    $td = H::fa( $amount * ($p->disc/100) );
                    $this->docpanel->listsform->totaldisc->setText($td);
                }  
                if($discf > 0) {
                    if( $amount < $discf  ) {
                        $discf = $amount;
                    }
                    $this->docpanel->listsform->totaldisc->setText($discf);                       
                }      
            }
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
            $this->setError('Введіть адресу');
            return;
        }
        if ($this->_doc->headerdata['delivery'] > 0 && $this->_doc->headerdata['contact'] == "") {
            $this->setError('Введіть контактні дані');
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
            $this->setInfo('Відправлено в доставку');

        } else {  //в  производство
            $this->toprod()  ;

        }

        $this->onNewOrder();
    }

    // в  производство
    public function toprodOnClick($sender) {

        if ($this->createdoc() == false) {
            return;
        }
  
       
        $this->toprod()  ;

       // $this->onOrderList();
        $this->onOrderList(null);
    }

    private function toprod() {



        $n = new \App\Entity\Notify();
        $n->user_id = \App\Entity\Notify::ARMFOODPROD;
        $n->dateshow = time();
        $n->message = serialize(array('cmd' => 'update'));


        foreach($this->_itemlist as $i=>$p) {
            if(intval($this->_itemlist[$i]->foodstate) ==0) {
                $this->_itemlist[$i]->foodstate = 1;
            }

        }

        $this->_doc->packDetails('detaildata', $this->_itemlist);
        $this->_doc->save();


        if($this->_doc->state < 4) {
            $this->_doc->updateStatus(Document::STATE_INPROCESS);
            $n->message = serialize(array('cmd' => 'new','document_id'=>$this->_doc->document_id));

        }
        $n->save();



        $this->setInfo('Відправлено у виробництво');

    }


   // проверка
    public function checkdoc( ) {
  
        
        return true;
    }
    // сохранить
    public function tosaveOnClick($sender) {


        if ($this->createdoc() == false) {
            return;
        }

   

        if($this->_doc->state != Document::STATE_NEW) {
            $this->_doc->updateStatus(Document::STATE_EDITED);

        }


        //            $this->_doc = $this->_doc->cast();

        $this->_doc->save();


        $this->onNewOrder();
    }

    //к  оплате
    public function topayOnClick($sender) {

        if ($this->createdoc() == false) {
            return;
        }
        $this->docpanel->payform->passfisc->setChecked(false);
        $this->docpanel->payform->passprod->setChecked(false);

        $this->docpanel->payform->setVisible(true);
        $this->docpanel->listsform->setVisible(false);
        $this->docpanel->navform->setVisible(false);
        $this->docpanel->payform->clean();

        $amount = $this->docpanel->listsform->totalamount->getText();
        $bonus = $this->docpanel->listsform->bonus->getText();
        $totaldisc = $this->docpanel->listsform->totaldisc->getText();
        $amount =  H::fa($amount - floatval($totaldisc)  - floatval($bonus)) ;


        $this->docpanel->payform->pfforpay->setText(H::fa($amount));
        $this->docpanel->payform->pfrest->setText(H::fa(0));
        $this->docpanel->payform->bbackitems->setVisible(true);

        
        $inprod = $this->_doc->checkStates([Document::STATE_INPROCESS]) ;//уже в  производстве
        $this->docpanel->payform->passprod->setVisible($this->_worktype > 0 && $inprod == false);
  
        $bonus = intval($this->docpanel->listsform->bonus->getText());
        $customer_id = $this->docpanel->listsform->customer->getKey();
        
        if ($bonus >0 && $customer_id > 0) {
            $c = Customer::load($customer_id) ;
            $b=$c->getBonus();
            if($bonus> $b) {
                $this->setError("У  контрагента  вього {$b} бонусів на рахунку");                
                return;
            }
           
        }
        if($amount == 0) {
            $this->setWarn("0 до сплати");  
            $this->docpanel->payform->passfisc->setChecked(true);
                          
        }
    }

    public function editqtyOnClick($sender) {
        $qty =  $this->docpanel->listsform->editqtyq->getText();
        $id  =  $this->docpanel->listsform->editqtyi->getText();
        $this->_itemlist[$id]->quantity = H::fqty($qty);
        $this->docpanel->listsform->itemlist->Reload();
        $this->calcTotal();

    }

    //Оплата
    public function payandcloseOnClick() {

        if ($this->_pt != 1 && $this->_pt != 2) {
            $this->setError("Не вказано спосіб оплати");
            return;
        }


        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();


        try {
            $this->_doc = $this->_doc->cast();

            $this->_doc->payamount = $this->docpanel->payform->pfforpay->getText();

            $this->_doc->payed = doubleval($this->docpanel->payform->pfpayed->getText());
            $this->_doc->headerdata['exchange'] = $this->docpanel->payform->pfrest->getText();
            $this->_doc->headerdata['payed'] = $this->_doc->payed;
            $this->_doc->headerdata['exch2b'] = $this->docpanel->payform->pfexch2b->getText();
            $this->_doc->headerdata['paytype'] = $this->_pt;


            $this->_doc->headerdata['trans'] = $this->docpanel->payform->pftrans->getText();
            if ($this->_pt == 2) {
                $this->_doc->headerdata['payment'] = $this->setupform->beznal->getValue();
            } else {
                $this->_doc->headerdata['payment'] = $this->setupform->nal->getValue();
            }

            if ($this->_doc->payamount > $this->_doc->payed) {
                $this->setError("Недостатня сума");
                return;
            }

            if ($this->_doc->amount > 0 && $this->_doc->payamount > $this->_doc->payed && $this->_doc->customer_id == 0) {
                $this->setError("Якщо у борг або передоплата  має бути обраний контрагент");
                return;
            }

            if (doubleval($this->_doc->headerdata['bonus']) >0 && $this->_doc->customer_id == 0) {
                $this->setError("Якщо   нарахування бонусів має бути обраний контрагент");
                return;
            }
            if (doubleval($this->_doc->headerdata['bonus']) >0) {
                $c = Customer::load($this->_doc->customer_id);
                if($this->_doc->headerdata['bonus']  > $c->getBonus()) {
                    $this->setError("Недостатньо бонусів");
                    return;
                }


            }
            if (doubleval($this->_doc->headerdata['exch2b']) >0 && $this->_doc->customer_id == 0) {
                $this->setError("Якщо оплата бонусами має бути обраний контрагент");
                return;
            }

            $this->_doc->save();
            $this->_doc->DoStore();
            $this->_doc->DoPayment();

            // если оплачено
            if ($this->_doc->payamount <= $this->_doc->payed) {
                if ($this->_worktype >0  && $this->docpanel->payform->passprod->isChecked() == false)  {  
                    $inprod = $this->_doc->checkStates([Document::STATE_INPROCESS]) ;//уже в  производстве
                    if($inprod== false) {
                       $this->toprod()  ;
                    }
                }
     
                if ($this->_worktype == 0  ||  $this->docpanel->payform->passprod->isChecked() == true) {
                    if ($this->_doc->state < 4) {
                        $this->_doc->updateStatus(Document::STATE_EXECUTED);
                    }

                    $this->_doc->updateStatus(Document::STATE_CLOSED);
                }
                if ($this->_worktype == 2) {
                    $b=true;
                    foreach ($this->_doc->unpackDetails('detaildata') as $rowid=>$item) {
                        $fs = intval($item->foodstate);
                        if($fs < 4) {
                            $b = false;
                            break;
                        }
                    }

                    if($b) {
                        $this->_doc->updateStatus(Document::STATE_CLOSED);
                    }

                }
                if ($this->_worktype == 1  ) {
                    if ($this->_doc->state < 4) {
                        $this->_doc->updateStatus(Document::STATE_EXECUTED);
                    }

                    $this->_doc->updateStatus(Document::STATE_CLOSED);
                }
                
            }

           if ($this->_doc->payamount > $this->_doc->payed ) {
               $this->_doc->updateStatus(Document::STATE_WP);
            }            

            
           
            
            if ($this->_doc->payamount <= $this->_doc->payed) {
              
                    
                if($this->_pos->usefisc == 1){
                    if( $this->docpanel->payform->passfisc->isChecked()) {
                        $this->_doc->headerdata["passfisc"]  = 1;
                    } else {     
                        $this->_doc->headerdata["passfisc"]  = 0;
                        
                        if( $this->_tvars['checkbox'] == true) {

                            $cb = new  \App\Modules\CB\CheckBox($this->_pos->cbkey, $this->_pos->cbpin) ;
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
                            $vk = new  \App\Modules\VK\VK($this->_pos->vktoken) ;
                            $ret = $vk->Check($this->_doc) ;

                            if(is_array($ret)) {
                                $this->_doc->headerdata["fiscalnumber"] = $ret['fiscnumber'];
                                $this->_doc->headerdata["tax_url"] = $ret['tax_url'];
                                $this->_doc->headerdata["vkassa"] = $ret['checkid'];
                                           
                            } else {
                                throw new \Exception($ret);
                            }         
                        }
                        if( $this->_tvars['ppo'] == true) {
                            $this->_doc->headerdata["fiscalnumberpos"]  =  $this->_pos->fiscalnumber;


                            $ret = \App\Modules\PPO\PPOHelper::check($this->_doc);
                            if ($ret['success'] == false && $ret['doclocnumber'] > 0) {
                                //повторяем для  нового номера
                                $this->_pos->fiscdocnumber = $ret['doclocnumber'];
                                $this->_pos->save();
                                $ret = \App\Modules\PPO\PPOHelper::check($this->_doc);
                            }
                            if ($ret['success'] == false) {

                                throw new \Exception($ret['data']);
                            } else {

                                if ($ret['docnumber'] > 0) {
                                    $this->_pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                                    $this->_pos->save();
                                    $this->_doc->headerdata["fiscalnumber"] = $ret['docnumber'];
                                } else {

                                    throw new \Exception("Не повернено фіскальний номер");
                                }
                            }
                        }
                       
                    }
                }
                $this->_doc->save();     

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
            $this->setError('Не введено позиції');
            return false;
        }
        if ($idnew) {
            $this->_doc->document_number = $this->_doc->nextNumber();

            if (false == $this->_doc->checkUniqueNumber()) {
                $next = $this->_doc->nextNumber();
                $this->_doc->document_number = $next;
                if (strlen($next) == 0) {
                    $this->setError('Не створено унікальный номер документа');
                    return false;
                }
            }
        }

        $execuser = $this->docpanel->listsform->execuser->getValue() ;
        if($execuser >0) {
            $this->_doc->user_id = $execuser;
        }
        $this->_doc->headerdata['forbar'] =  $this->docpanel->listsform->forbar->isChecked() ? 1 : 0;
        $this->_doc->headerdata['arm'] = 1;
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
        $this->_doc->headerdata['pricetype'] = $this->_pricetype;

       
        $this->_doc->username = System::getUser()->username;

        $firm = H::getFirmData( );
        $this->_doc->headerdata["firm_name"] = $firm['firm_name'];
        $this->_doc->headerdata["inn"] = $firm['inn'];
        $this->_doc->headerdata["address"] = $firm['address'];
        $this->_doc->headerdata["phone"] = $firm['phone'];
        $this->_doc->headerdata["promocode"] = $this->docpanel->navform->promocode->getText();
        $this->_doc->headerdata["totaldisc"] = $this->docpanel->listsform->totaldisc->getText();
        $this->_doc->headerdata["bonus"] = $this->docpanel->listsform->bonus->getText();

        if($this->_doc->customer_id==0  && $this->_doc->headerdata["bonus"] >0   ) {
            $this->setError('Якщо  бонуси має бути вибраний  контрагент');
            return false;
        }
        
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
            $this->setError("Не введено назву");
            return;
        }
        $cust = new Customer();
        $cust->customer_name = $custname;
        $cust->address = $this->editcust->editaddress->getText();
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

        $this->docpanel->listsform->custinfo->setText('');

        $customer_id = $sender->getKey();
        if ($customer_id > 0) {
            $customer = Customer::load($customer_id);
            if ("" == trim($this->docpanel->listsform->address->getText())) {
                $this->docpanel->listsform->address->setText($customer->address);

            }
            if ("" == trim($this->docpanel->listsform->contact->getText())) {

                $this->docpanel->listsform->contact->setText($customer->customer_name . ', ' . $customer->phone);
            }
            $d = $customer->getDiscount();
            if($d >0) {
                $this->docpanel->listsform->custinfo->setText("Знижка {$d}%");
            } else {
                $b= $customer->getBonus();
                if($b>0) {
                    $this->docpanel->listsform->custinfo->setText("Бонусiв {$b}");
                }
            }


            if($d > 0) {

                foreach($this->_itemlist as $it) {
                    if($it->disc == 0) {

                        $it->disc = $d;
                        $it->price = H::fa($it->pureprice - ($it->pureprice*$d/100)) ;
                    }

                }

                $this->docpanel->listsform->itemlist->Reload();
                $this->calcTotal();
            }
            $this->docpanel->listsform->addcust->setVisible(false) ;
            $this->docpanel->listsform->cinfo->setVisible(true) ;
            $this->docpanel->listsform->cinfo->setAttribute('onclick', "customerInfo({$customer_id});") ;


        } else {
            return;
        }


    }

    public function getMessages($args, $post) {

        $tables=[];
        $cntorder = 0;
        $cntprod = 0;
        $mlist = \App\Entity\Notify::find("checked <> 1 and user_id=" . \App\Entity\Notify::ARMFOOD);
        foreach ($mlist as $n) {             
            $msg = @unserialize($n->message);
            if(($msg['document_id'] ??0) >0) {
                $doc = Document::load(intval($msg['document_id']));

                if ($doc->state == Document::STATE_NEW) {
                    $cntorder++;
                } 
            }
            if(($msg['tableno'] ??0) >0) {
                $tables[] = $msg['tableno'];
            }
        }

        \App\Entity\Notify::markRead(\App\Entity\Notify::ARMFOOD);


        if($cntorder>0) {
           return json_encode(array( 'cntorder' => $cntorder), JSON_UNESCAPED_UNICODE);    
        }
        if(count($tables) > 0 ) {
           $msg = implode(', ',$tables)  ;
             
           return json_encode(array( 'tableno' => $msg), JSON_UNESCAPED_UNICODE);    
        }
        
    }

    public function getProdItems($args, $post=null) {
        $itemlist = [];
        
        foreach(Document::findYield(" meta_name='OrderFood' and  state=". Document::STATE_INPROCESS, 'document_id desc') as $doc) {
            foreach ($doc->unpackDetails('detaildata') as $rowid=>$item) {
                $fs = intval($item->foodstate);
                if($fs==3) {
                    $itemlist[] = array(
                       'name'=>$item->itemname,
                       'table'=>$doc->headerdata['table'] ?? '',
                       'document_id'=>$doc->document_id,
                       'rowid'=>$rowid,
                       'ordern' =>$doc->document_number
                    );
                }

            }
        }


        return json_encode($itemlist, JSON_UNESCAPED_UNICODE);
    }

    public function OnPrint($sender) {


        if(intval(\App\System::getUser()->prtype) == 0) {


            $this->addAjaxResponse("  $('#checktext').printThis() ");

            return;
        }

        try {
            $doc = $this->_doc->cast();
            $xml = $doc->generatePosReport(true);

            $buf = \App\Printer::xml2comm($xml);
            $b = json_encode($buf) ;

            $this->addAjaxResponse("  sendPS('{$b}') ");
        } catch(\Exception $e) {
            $message = $e->getMessage()  ;
            $message = str_replace(";", "`", $message)  ;
            $this->addAjaxResponse(" toastr.error( '{$message}' )         ");

        }

    }
    

    public function OnPrintBill($sender) {


        if(intval(\App\System::getUser()->prtype) == 0) {


            $this->addAjaxResponse("  $('#billtext').printThis() ");

            return;
        }

        try {
            $doc = $this->_doc->cast();
            $xml = $doc->generatePosReport(true,true);

            $buf = \App\Printer::xml2comm($xml);
            $b = json_encode($buf) ;

            $this->addAjaxResponse("  sendPS('{$b}') ");
        } catch(\Exception $e) {
            $message = $e->getMessage()  ;
            $message = str_replace(";", "`", $message)  ;
            $this->addAjaxResponse(" toastr.error( '{$message}' )         ");

        }

    }
  
    public function OnPrintRunner($sender) {
        $printer = \App\System::getOptions('printer') ;
        $user = \App\System::getUser() ;

        $doc = $sender->getOwner()->getDataItem();
        $header = [];
        
        $header['document_number']    =  $doc->document_number   ;
        $header['time']    =  H::ft($doc->headerdata['time']) ;
        $header['table']   =  $doc->headerdata['table'] ;
        $header['notes']   =  $doc->notes ;
        $header['detail']  =  [];
        foreach ($doc->unpackDetails('detaildata') as $item) {
            if( intval( $item->foodstate) > 1)  {
                continue;
            }
            $name = strlen($item->shortname) > 0 ? $item->shortname : $item->itemname;

            $header['detail'] [] = array(
                "myself" => $item->myself ?' З собою':'',
                "itemname" => $name,
                "qty"   => H::fqty($item->quantity)
                
            );
        }        
        if(count($header['detail']) == 0) {
            $this->addAjaxResponse(" toastr.warning( 'Всі позиції вже в роботі' )         ");
            return;   
        }
        if(intval($user->prtype) == 0) {
  
            $report = new \App\Report('runner.tpl');
            $html =  $report->generate($header);                  

            
            if($user->usemobileprinter == 1) {
                \App\Session::getSession()->printform =  $html;

                $this->addAjaxResponse("   window.open('/index.php?p=App/Pages/ShowReport&arg=print')");
            } else {
                $this->addAjaxResponse("  $('#rtag').html('{$html}') ; $('#prform').modal()");
            }            
            
            

            return;
        }
       
        try {
            $buf=[];
            if(intval($user->prtype) == 1) {
                
                $report = new \App\Report('runner_ps.tpl');
            
                $html =  $report->generate($header);              
                
                $buf = \App\Printer::xml2comm($html);
            }
           
            $b = json_encode($buf) ;
            $this->addAjaxResponse(" sendPS('{$b}') ");

        } catch(\Exception $e) {
            $message = $e->getMessage()  ;
            $message = str_replace(";", "`", $message)  ;
            $this->addAjaxResponse(" toastr.error( '{$message}' )         ");

        }       
    }  
    
    //фискализация
    public function OnOpenShift($sender) {
 
        if($this->_tvars['checkbox'] == true) {


            $cb = new  \App\Modules\CB\CheckBox($this->_pos->cbkey, $this->_pos->cbpin) ;
            $ret = $cb->OpenShift() ;

            if($ret === true) {
                $this->setSuccess("Зміна відкрита");
            } else {
                $this->setError($ret);
            }

          if($this->_pos->autoshift >0){
                $task = new  \App\Entity\CronTask()  ;
                $task->tasktype = \App\Entity\CronTask::TYPE_AUTOSHIFT;
                $t =   strtotime(  date('Y-m-d ') .  $this->_pos->autoshift.':00' );  
                  
                $task->starton=$t;
                $task->taskdata= serialize(array(
                       'pos_id'=>$this->_pos->pos_id, 
                       'type'=>'cb' 
       
                    ));         
                $task->save();
                    
            } 

            return  ;
        }
         if($this->_tvars['vkassa'] == true) {


            $vk = new  \App\Modules\VK\VK($this->_pos->vktoken) ;
            $ret = $vk->OpenShift() ;

            if($ret === true) {
                $this->setSuccess("Зміна відкрита");
            } else {
                $this->setError($ret);
            }
            if($this->_pos->autoshift >0){
                $task = new  \App\Entity\CronTask()  ;
                $task->tasktype = \App\Entity\CronTask::TYPE_AUTOSHIFT;
                $t =   strtotime(  date('Y-m-d ') .  $this->_pos->autoshift.':00' );  
                 
                $task->starton=$t;
                $task->taskdata= serialize(array(
                       'pos_id'=>$this->_pos->pos_id, 
                       'type'=>'vk' 
       
                    ));         
                $task->save();
                    
            }  


            return;
        }

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
            $this->setSuccess("Зміна відкрита");
            if ($ret['doclocnumber'] > 0) {
                $this->_pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                $this->_pos->save();

            }
            \App\Modules\PPO\PPOHelper::clearStat($this->_pos->pos_id);
            
            
            if($this->_pos->autoshift >0){
                $task = new  \App\Entity\CronTask()  ;
                $task->tasktype = \App\Entity\CronTask::TYPE_AUTOSHIFT;
                $t =   strtotime(  date('Y-m-d ') .  $this->_pos->autoshift.':00' );  
                 
                $task->starton=$t;
                $task->taskdata= serialize(array(
                       'pos_id'=>$this->_pos->pos_id, 
                       'type'=>'ppro' 
       
                    ));         
                $task->save();
                    
            }            
        }


        $this->_pos->save();
        return  ;
    }

    public function OnCloseShift($sender) {

        if($this->_tvars['checkbox'] == true) {

            $cb = new  \App\Modules\CB\CheckBox($this->_pos->cbkey, $this->_pos->cbpin) ;
            $ret = $cb->CloseShift() ;

            if($ret === true) {
                $this->setSuccess("Зміна закрита");
            } else {
                $this->setError($ret);
            }

            return;
        }
        if($this->_tvars['vkassa'] == true) {

            $vk = new  \App\Modules\VK\VK($this->_pos->vktoken) ;
            $ret = $vk->CloseShift() ;

            if($ret === true) {
                $this->setSuccess("Зміна закрита");
            } else {
                $this->setError($ret);
            }

            return;
        }

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
                $this->setError("Не повернено фіскальний номер");
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
            $sc = \App\System::getSession()->shiftclose;
            if(strlen($sc)>0) {
               \App\System::getSession()->shiftclose="";
               $this->setInfoTopPage("Зміна закрита. ".$sc );                               
            } else {
               $this->setSuccess("Зміна закрита");    
            }
            if ($ret['doclocnumber'] > 0) {
                $this->_pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                $this->_pos->save();
            }
            \App\Modules\PPO\PPOHelper::clearStat($this->_pos->pos_id);
        }


        return true;
    }
    
  public function onFisc($sender) {

        $doc =  $sender->getOwner()->getDataItem();
           $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

        if($this->_tvars['checkbox'] == true) {

            $cb = new  \App\Modules\CB\CheckBox($this->_pos->cbkey, $this->_pos->cbpin) ;
            $ret = $cb->Check($doc) ;

            if(is_array($ret)) {
                $doc->headerdata["fiscalnumber"] = $ret['fiscnumber'];
                $doc->headerdata["tax_url"] = $ret['tax_url'];
                $doc->headerdata["checkbox"] = $ret['checkid'];
                $doc->headerdata["passfisc"] = 0;
                $doc->save();
                $this->setSuccess("Виконано");
            } else {
               throw new \Exception($ret);
 
            }


        }
        if($this->_tvars['vkassa'] == true) {
            $vk = new  \App\Modules\VK\VK($this->_pos->vktoken) ;
            $ret = $vk->Check($doc) ;

            if(is_array($ret)) {
                $doc->headerdata["fiscalnumber"] = $ret['fiscnumber'];
                $doc->headerdata["passfisc"] = 0;
                $doc->save();
              
            } else {
                throw new \Exception($ret);
 
            }  
        }


        if ($this->_tvars['ppo'] == true) {


            $doc->headerdata["fiscalnumberpos"]  = $this->_pos->fiscalnumber;


            $ret = \App\Modules\PPO\PPOHelper::check($doc);
            if ($ret['success'] == false && $ret['doclocnumber'] > 0) {
                //повторяем для  нового номера
                $this->_pos->fiscdocnumber = $ret['doclocnumber'];
                $this->_pos->save();
                $ret = \App\Modules\PPO\PPOHelper::check($this->_doc);
            }
            if ($ret['success'] == false) {
                  throw new \Exception($ret['data']);

            } else {
                //  $this->setSuccess("Выполнено") ;
                if ($ret['docnumber'] > 0) {
                    $this->_pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                    $this->_pos->save();
                    $doc->headerdata["fiscalnumber"] = $ret['docnumber'];
                    $doc->headerdata["fiscalamount"] = $ret['fiscalamount'];
                    $doc->headerdata["fiscaltest"] = $ret['fiscaltest'];
                    $doc->headerdata["passfisc"] = 0;
                    $doc->save();
                    $this->setSuccess("Виконано");
                } else {

                     throw new \Exception("Не повернено фіскальний номер");

                }
            }

        }
            $conn->CommitTrans();
        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setErrorTopPage($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $doc->meta_desc);
           
            
            return;
        } 
        $this->updateorderlist(null);
    }
    

     public function checkPromo($args, $post=null) {
        $code = trim($args[0]) ;
        if($code=='')  {
            return json_encode([], JSON_UNESCAPED_UNICODE);             
        }
        $r = \App\Entity\PromoCode::check($code,$this->docpanel->listsform->customer->getKey())  ;
        if($r != ''){
            return json_encode(array('error'=>$r), JSON_UNESCAPED_UNICODE);                
        }
        $total = 0;

        foreach ($this->_itemlist as $item) {
            $total += H::fa($item->quantity * $item->price);
        }        

        $p = \App\Entity\PromoCode::findByCode($code);
        $disc = doubleval($p->disc );
        $discf = doubleval($p->discf );
        if($disc >0)  {
            $td = H::fa( $total * ($p->disc/100) );
            $ret=array('disc'=>$td) ;
            return json_encode($ret, JSON_UNESCAPED_UNICODE);
             
        }        
        
        if($discf >0)  {
          
            if($total < $discf)  {
               $discf =  $total;
            }
            $ret=array('disc'=>$discf) ;
            return json_encode($ret, JSON_UNESCAPED_UNICODE);
             
        }        
        
        return json_encode([], JSON_UNESCAPED_UNICODE);             
       

    }   

     public function onOptions($sender){
         $this->optionsform->setVisible(true) ;
         $this->setupform->setVisible(false);
     }
    
     public function saveOptions($sender){
         
         
          $food = System::getOptions("food");
        if (!is_array($food)) {
            $food = array();
        }

        $food['worktype'] = $sender->foodworktype->getValue();
        $food['pricetype'] = $sender->foodpricetype->getValue();
        $food['pricetypeout'] = $sender->foodpricetypeout->getValue();
        $food['delivery'] = $sender->fooddelivery->isChecked() ? 1 : 0;
        $food['tables'] = $sender->foodtables->isChecked() ? 1 : 0;

        $food['pack'] = $sender->foodpack->isChecked() ? 1 : 0;
        $food['name'] = $sender->goodname->getText() ;
        $food['address'] = $sender->goodaddress->getText() ;
        $food['phone'] = $sender->goodphone->getText() ;
        $food['timepn'] = $sender->timepn->getText() ;
        $food['timesa'] = $sender->timesa->getText() ;
        $food['timesu'] = $sender->timesu->getText() ;
        $food['foodbasemenu'] = $sender->foodbasemenu->getValue() ;
        $food['foodbasemenuname'] = $sender->foodbasemenu->getValueName() ;
        $food['foodmenu2'] = $sender->foodmenu2->getValue() ;
        $food['foodmenu3'] = $sender->foodmenu3->getValue() ;
        $food['foodmenu4'] = $sender->foodmenu4->getValue() ;
        $food['foodmenuname'] = $sender->foodmenu2->getValueName() ;
        $food['foodmenuname3'] = $sender->foodmenu3->getValueName() ;
        $food['foodmenuname4'] = $sender->foodmenu4->getValueName() ;

        System::setOptions("food", $food);
        $this->setSuccess('Збережено');       
         
         
       \App\Application::Redirect("\\App\\Pages\\Service\\ARMFood");; 
     }
    
    
    
}

<?php

namespace App\Pages\Service;

use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Category;
use App\Entity\Service;
use Zippy\Html\Image;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\RadioButton;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use App\Application as App;

/**
 * АРМ кассира
 */
class ARMPos extends \App\Pages\Base
{
    public $_itemlist   = array();
    public $_serlist    = array();
    public $_paytype    = 0;
    private $pos;
    private $_doc        = null;
    private $_rowid      = -1;
    private $_pt         = 0;
    private $_store_id   = 0;
    private $_salesource = 0;
    private $_mfbeznal = 0;
    private $_mfnal = 0;
    private $_editrow =false;
    private $_docid =0;
    private $_basedocid =0;

    public $_doclist = array();

    public function __construct($docid=0,$basedocid=0) {
        parent::__construct();

        if (false == \App\ACL::checkShowSer('ARMPos')) {
            return;
        }
        
        $this->_docid = $docid;
        $this->_basedocid = $basedocid;
        $ss='';
        if($basedocid >0) {
            $bd= Document::load($basedocid)->cast();
            $ss = $bd->getHD('salesource','') ;
        }
        
        $filter = \App\Filter::getFilter("armpos");
        if ($filter->isEmpty()) {
            $filter->pos = 0;
            $filter->store = H::getDefStore();
            $filter->pricetype = H::getDefPriceType();
            $filter->salesource =  strlen($ss) > 0 ? $ss : H::getDefSaleSource();
            $filter->mfnal = H::getDefSaleSource();
            $filter->mfbeznal = H::getDefSaleSource();


        }
        
        //обшие настройки
        $this->add(new Form('form1'));
        $plist = \App\Entity\Pos::findArray('pos_name', '');

        $this->form1->add(new DropDownChoice('pos', $plist, $filter->pos));
        $this->form1->add(new DropDownChoice('store', \App\Entity\Store::getList(), $filter->store));
        $this->form1->add(new DropDownChoice('pricetype', \App\Entity\Item::getPriceTypeList(), $filter->pricetype));
        $this->form1->add(new DropDownChoice('salesource', H::getSaleSources(), $filter->salesource));
        $this->form1->add(new DropDownChoice('mfnal', \App\Entity\MoneyFund::getList(1), $filter->mfnal));
        $this->form1->add(new DropDownChoice('mfbeznal', \App\Entity\MoneyFund::getList(2), $filter->mfbeznal));

        $this->form1->add(new SubmitButton('next1'))->onClick($this, 'next1docOnClick');


        $this->add(new Panel('checklistpan'))->setVisible(false);
        $this->checklistpan->add(new ClickLink('newcheck', $this, 'newdoc'));
        $this->checklistpan->add(new DataView('checklist', new ArrayDataSource($this, '_doclist'), $this, 'onDocRow'));
        $this->checklistpan->add(new \Zippy\Html\DataList\Paginator('pag', $this->checklistpan->checklist));
        $this->checklistpan->checklist->setPageSize(H::getPG());

        //панель статуса,  просмотр
        $this->checklistpan->add(new Form('searchform'))->onSubmit($this, 'updatechecklist');
        $this->checklistpan->searchform->add(new AutocompleteTextInput('searchcust'))->onText($this, 'OnAutoCustomer');
        $this->checklistpan->searchform->add(new TextInput('searchnumber', $filter->searchnumber));

        $this->checklistpan->add(new Panel('statuspan'))->setVisible(false);

        $this->checklistpan->statuspan->add(new \App\Widgets\DocView('docview'))->setVisible(false);


        $this->add(new Panel('docpanel'))->setVisible(false);
        $this->docpanel->add(new Panel('navbar')) ;

        $this->docpanel->navbar->add(new ClickLink('tochecklist', $this, 'onCheckList'));
        $this->docpanel->navbar->add(new ClickLink('tosimple', $this, 'onModeOn'));
        $this->docpanel->navbar->add(new ClickLink('tostandard', $this, 'onModeOn'));
        $this->docpanel->navbar->add(new ClickLink('openshift', $this, 'OnOpenShift'));
        $this->docpanel->navbar->add(new ClickLink('closeshift', $this, 'OnCloseShift'));


        $this->docpanel->add(new Form('form2'))->setVisible(false);

        //  ввод товаров

        $this->docpanel->form2->add(new SubmitButton('tosave'))->onClick($this, 'tosaveOnClick');
        $this->docpanel->form2->add(new SubmitButton('topass'))->onClick($this, 'tosaveOnClick');
        $this->docpanel->form2->add(new Button('frompass'))->onClick($this, 'fromPass');
        
        $this->docpanel->form2->add(new Button('tocancel'))->onClick($this, 'newdoc');
        $this->docpanel->form2->add(new SubmitButton('topay'))->onClick($this, 'topayOnClick');
        $this->docpanel->form2->add(new TextInput('barcode'));
        $this->docpanel->form2->add(new SubmitLink('addcode'))->onClick($this, 'addcodeOnClick');
        $this->docpanel->form2->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docpanel->form2->add(new SubmitLink('addser'))->onClick($this, 'addserOnClick');
        $this->docpanel->form2->addser->setVisible(Service::findCnt('disabled<>1') > 0);  //показываем  если  есть  услуги
        $this->docpanel->form2->add(new Label('total'));


        $this->docpanel->form2->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'));
        $this->docpanel->form2->add(new DataView('detailser', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_serlist')), $this, 'serOnRow'));
        $this->docpanel->form2->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docpanel->form2->customer->onChange($this, 'OnChangeCustomer');

        $this->docpanel->form2->add(new TextInput('promocode'));
        $this->docpanel->form2->promocode->setVisible(\App\Entity\PromoCode::findCnt('') > 0);


        $bind = new  \Zippy\Binding\PropertyBinding($this, '_paytype');
 
        $this->docpanel->form2->add(new RadioButton('pt1',$bind,1));
        $this->docpanel->form2->add(new RadioButton('pt2',$bind,2));
        $this->docpanel->form2->add(new RadioButton('pt3',$bind,3));
        
        $this->docpanel->form2->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');
        $this->docpanel->form2->add(new Label('custinfo'))->setVisible(false);
        $this->docpanel->form2->add(new \Zippy\Html\Link\BookmarkableLink('cinfo'))->setVisible(false);


        $this->docpanel->form2->add(new AutocompleteTextInput('addtovarsm'))->onText($this, 'OnAutoItemSm');
        $this->docpanel->form2->addtovarsm->onChange($this, 'OnChangeItemSm',true);
        $this->docpanel->form2->add(new TextInput('qtysm'));
        $this->docpanel->form2->add(new Label('storeqtysm'));
        $this->docpanel->form2->add(new SubmitLink('additemsm'))->onClick($this, 'addItemSmOnClick');
        $this->docpanel->form2->add(new TextInput('bonus'));
        $this->docpanel->form2->add(new TextInput('totaldisc'));
        $this->docpanel->form2->add(new TextInput('prepaid'));

        //оплата
        $this->docpanel->add(new Form('form3'))->setVisible(false);


        $this->docpanel->form3->add(new TextInput('paytypeh'));
        $this->docpanel->form3->add(new TextInput('document_number'));

        $this->docpanel->form3->add(new Date('document_date'))->setDate(time());
        $this->docpanel->form3->add(new TextArea('notes'));
        $this->docpanel->form3->add(new TextInput('exch2b'));


        $this->docpanel->form3->add(new Button('cancel2'))->onClick($this, 'cancel2docOnClick');
        $this->docpanel->form3->add(new SubmitButton('save'))->onClick($this, 'savedocOnClick');


        $this->docpanel->form3->add(new TextInput('payamount'));
        $this->docpanel->form3->add(new TextInput('payed'));
        $this->docpanel->form3->add(new TextInput('payedcard'));
        $this->docpanel->form3->add(new TextInput('exchange'));

        $this->docpanel->form3->add(new TextInput('trans'));


        $this->docpanel->form3->add(new CheckBox('passfisc'));
        //печать
        $this->docpanel->add(new Form('formcheck'))->setVisible(false);
        $this->docpanel->formcheck->add(new Label('showcheck'));
        $this->docpanel->formcheck->add(new Button('newdoc'))->onClick($this, 'newdoc');
        $this->docpanel->formcheck->add(new Button('print'))->onClick($this, "OnPrint", true);
        $this->docpanel->formcheck->add(new Button('qrpaybtn'));

        $this->docpanel->add(new Form('editdetail'))->setVisible(false);
        $this->docpanel->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->docpanel->editdetail->add(new TextInput('editprice'));
        $this->docpanel->editdetail->add(new TextInput('editserial'));

        $this->docpanel->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');
        $this->docpanel->editdetail->edittovar->onChange($this, 'OnChangeItem', true);

        $this->docpanel->editdetail->add(new Label('qtystock'));
        $this->docpanel->editdetail->add(new Label('qtystockex'));
        $this->docpanel->editdetail->add(new ClickLink('openitemsel', $this, 'onOpenItemSel'));
        $this->docpanel->editdetail->add(new ClickLink('opencatpan', $this, 'onOpenCatPan'));
 
        $this->docpanel->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->docpanel->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');

        $this->docpanel->add(new \App\Widgets\ItemSel('wselitem', $this, 'onSelectItem'))->setVisible(false);
 

        $this->docpanel->add(new Form('editserdetail'))->setVisible(false);
        $this->docpanel->editserdetail->add(new TextInput('editserquantity'))->setText("1");
        $this->docpanel->editserdetail->add(new TextInput('editserprice'));


        $this->docpanel->editserdetail->add(new DropDownChoice('editser', Service::findArray("service_name", "disabled<>1", "service_name")))->onChange($this, 'OnChangeSer', true);
           


        $this->docpanel->editserdetail->add(new Button('cancelser'))->onClick($this, 'cancelrowOnClick');
        $this->docpanel->editserdetail->add(new SubmitButton('submitser'))->onClick($this, 'saveserOnClick');

        //добавление нового контрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new TextInput('editemail'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        $this->add(new Label('qrimg')) ;

        $this->_tvars['simplemode']  = false;
        $common = System::getOptions("common");

        $this->_tvars["colspan"] = 6; 
        if($common['usesnumber'] >0) {
            $this->_tvars["colspan"] = 7;
        }
        if($common['usesnumber'] ==2) {
            $this->_tvars["colspan"] = 8;
        }
         
        if(H::getKeyVal('issimple_'.System::getUser()->user_id)=="tosimple"){
           $this->onModeOn($this->docpanel->navbar->tosimple); 
        }
        $this->_tvars['scaleurl'] =  System::getUser()->scaleserver;
        $this->_tvars['showscalebtn'] =  strlen($this->_tvars['scaleurl']) >0;
          
    }

    public function onModeOn($sender) {
        $this->_tvars['simplemode']  = $sender->id == 'tosimple';
        if($this->_tvars['simplemode'] == true) {
            $this->_tvars['usesnumber']  = false;
            $this->docpanel->form2->qtysm->setText("");
            $this->docpanel->form2->storeqtysm->setText("");
            
        } else {
            $options = System::getOptions('common');
            $this->_tvars["usesnumber"] = $options['usesnumber'] == 1;

        }
        H::setKeyVal('issimple_'.System::getUser()->user_id,$sender->id);
    }

    public function onCheckList($sender) {
        $this->docpanel->setVisible(false);
        $this->docpanel->form2->setVisible(false);
        $this->docpanel->form3->setVisible(false);
        $this->docpanel->formcheck->setVisible(false);
        $this->docpanel->editserdetail->setVisible(false);
        $this->docpanel->wselitem->setVisible(false);
        $this->docpanel->editdetail->setVisible(false);

        $this->checklistpan->setVisible(true);
        $this->checklistpan->statuspan->setVisible(true);
        $this->updatechecklist(null);
    }

    public function cancel2docOnClick($sender) {

        $this->docpanel->form2->setVisible(true);
        $this->docpanel->form3->setVisible(false);
        $this->_editrow =  false;
        $this->_paytype=0;
    }

    public function cancel3docOnClick($sender) {

        $this->docpanel->form3->setVisible(true);
        $this->docpanel->formcheck->setVisible(false);
    }

    public function next1docOnClick($sender) {
        $this->pos = \App\Entity\Pos::load($this->form1->pos->getValue());

        $this->_store_id = $this->form1->store->getValue();
        $this->_salesource = $this->form1->salesource->getValue();
        $this->_pt = $this->form1->pricetype->getValue();
        $this->_mfnal = $this->form1->mfnal->getValue();
        $this->_mfbeznal = $this->form1->mfbeznal->getValue();

        if ($this->pos == null) {
            $this->setError("Не обрано термінал");
            return;
        }
        if ($this->_mfnal == 0) {
            $this->setError("Не обрана готівкова каса");
            return;
        }


        if ($this->_store_id == 0) {
            $this->setError("Не обрано склад");
            return;
        }

        if (strlen($this->_pt) == 0) {
            $this->setError("Не вказано тип ціни");
            return;
        }


        if($this->pos->usefisc != 1) {
            $this->_tvars['fiscal']  = false;
        }
 
        $this->_tvars['fiscaltestmode']  = $this->pos->testing==1;

        $filter = \App\Filter::getFilter("armpos");

        $filter->pos = $this->form1->pos->getValue();



        $filter->store = $this->_store_id;
        $filter->pricetype = $this->_pt;
        $filter->salesource = $this->_salesource;
        $filter->mfnal = $this->_mfnal;
        $filter->mfbeznal = $this->_mfbeznal;

        $this->form1->setVisible(false);
        $this->docpanel->form2->setVisible(true);

        
        $this->_tvars['onlynal']  = $filter->mfbeznal==0;
        
 


        $this->newdoc(null);
    }

    public function newdoc($sender) {
        $this->docpanel->setVisible(true);

        $this->docpanel->form2->setVisible(true);
 
        $this->checklistpan->setVisible(false);
        $this->checklistpan->searchform->clean();
 
        $this->_itemlist = array();
        $this->_serlist = array();
        $this->docpanel->form2->detail->Reload();
        $this->docpanel->form2->detailser->Reload();
        $this->calcTotal();
 
        $this->docpanel->form3->document_date->setDate(time());
        $this->_doc = \App\Entity\Doc\Document::create('POSCheck');
        $this->_doc->headerdata['arm'] = 1;
        $this->_doc->headerdata['time'] = time();
        $this->_doc->document_date = time();
        $this->_doc->document_number = $this->_doc->nextNumber();
        
      

        $this->docpanel->form2->customer->setKey(0);
        $this->docpanel->form2->customer->setText('');

        $this->docpanel->form2->bonus->setText('0');
        $this->docpanel->form2->totaldisc->setText('0');
        $this->docpanel->form2->promocode->setText('');

 
        
        $this->docpanel->form3->payamount->setText('0');
        $this->docpanel->form3->payed->setText('0');
        $this->docpanel->form3->payedcard->setText('0');
        $this->docpanel->form3->exchange->setText('0');
        $this->docpanel->form2->custinfo->setText('');
        $this->docpanel->form3->trans->setText('') ;
        $this->docpanel->form2->prepaid->setText('') ;
        $this->docpanel->form3->passfisc->setChecked(false) ;
        $this->docpanel->form2->setVisible(true);

        $this->docpanel->formcheck->setVisible(false);
        $this->docpanel->form2->addcust->setVisible(true) ;
        $this->docpanel->form2->cinfo->setVisible(false) ;

        $this->_paytype=0;

        
        if($this->_docid >0) { //загрузка  чека
            
            $doc = Document::load($this->_docid);
            if($doc->checkStates([Document::STATE_CANCELED])==false){  //если не  отменялся
               $doc->document_date=time();                
            }            
            $this->loadDoc($doc);
           
            $this->_docid = 0;
        }
        if($this->_basedocid >0) { //на основании
            
            $bd = Document::load($this->_basedocid) ;
            $this->docpanel->form2->bonus->setText($bd->headerdata['bonus']);
            $this->docpanel->form2->totaldisc->setText($bd->headerdata['totaldisc']);
            $this->_doc->parent_id=$this->_basedocid;
   
            $this->docpanel->form2->customer->setKey($bd->customer_id);
            $this->docpanel->form2->customer->setText($bd->customer_name);
            if($bd->meta_name=='ServiceAct') {
                if($bd->getHD('paytype',0) != 3){
                    $this->setWarn('В Акті не повинно бути оплати якщо оплата  чеком  ') ;
                    App::Redirect("\\App\\Pages\\Register\\SerList");
                    return; 
                }                
                $this->_itemlist = $bd->unpackDetails('detail2data');
                $this->_serlist =  $bd->unpackDetails('detaildata');
            }
            if($bd->meta_name=='Order') {
                if($bd->getHD('paytype',0) != 3){
                    $this->setWarn('В Замовленні не повинно бути оплати якщо оплата  чеком  ') ;
                    App::Redirect("\\App\\Pages\\Register\\OrderList");
                    return; 
                }
          
                
                $this->_itemlist = $bd->unpackDetails('detaildata');
            }

            $this->docpanel->form2->detail->Reload();
            $this->docpanel->form2->detailser->Reload();
            $this->calcTotal();          
          
            $this->_basedocid = 0;
        }
        

    }

    //к  оплате      
    public function topayOnClick($sender) {
        if (count($this->_itemlist) == 0 && count($this->_serlist) == 0) {
            $this->setError('Не введено позиції');
            return;
        }

        $total =   floatval($this->docpanel->form2->total->getText()) ;
        $bonus =   floatval($this->docpanel->form2->bonus->getText()) ;
        $totaldisc =   floatval($this->docpanel->form2->totaldisc->getText()) ;
        $prepaid =   floatval($this->docpanel->form2->prepaid->getText()) ;

        $payamount = $total - $bonus - $totaldisc - $prepaid;

        if($this->_tvars['onlynal']==true) {
            $this->_paytype=1;
        }

        if(($this->_paytype==0 || $this->_paytype=='') && $payamount > 0) {
            $this->setError('Не вказаний тип оплати');
            return;

        }

        $this->docpanel->form3->document_number->setText($this->_doc->document_number);
        $this->docpanel->form3->document_date->setDate($this->_doc->document_date);


        $this->docpanel->form3->paytypeh->setText($this->_paytype);

        $this->form1->setVisible(false);
        $this->docpanel->form2->setVisible(false);
        $this->docpanel->form3->setVisible(true);

        $this->docpanel->form3->exch2b->setText('');
        $this->docpanel->form3->exchange->setText('');

        //к  оплате



        $this->docpanel->form3->payamount->setText(H::fa($payamount));

        if($this->_mfbeznal == 0) {
            $this->docpanel->form3->payed->setText($payamount);
            $this->docpanel->form3->payedcard->setVisible(false);
        } else {
            $this->docpanel->form3->payed->setAttribute('disabled', null);
            $this->docpanel->form3->payedcard->setAttribute('disabled', null);

            if($this->_paytype == 1) {
                $this->docpanel->form3->payed->setText($payamount);
                $this->docpanel->form3->payedcard->setAttribute('disabled', 'disabled');
                $this->docpanel->form3->payedcard->setText(0);
            }
            if($this->_paytype == 2) {
                $this->docpanel->form3->payedcard->setText($payamount);
                $this->docpanel->form3->payed->setAttribute('disabled', 'disabled');
                $this->docpanel->form3->payed->setText(0);

            }
            if($this->_paytype == 3) {
                $half= H::fa($payamount/2);
                $this->docpanel->form3->payed->setText($half);
                $this->docpanel->form3->payedcard->setText($payamount - $half);



            }

        }

        //если  предоплата
        $hidep = ($payamount ==0 && $prepaid >0) ;
        $this->docpanel->form3->payed->setVisible(!$hidep);
        $this->docpanel->form3->payedcard->setVisible(!$hidep);
        $this->docpanel->form3->exchange->setVisible(!$hidep);
        $this->docpanel->form3->exch2b->setVisible(!$hidep);

        
        $bonus = intval($this->docpanel->form2->bonus->getText());
        $customer_id = $this->docpanel->form2->customer->getKey();
        
        if ($bonus >0 && $customer_id > 0) {
            $c = Customer::load($customer_id) ;
            $b=$c->getBonus();
            if($bonus> $b) {
                $this->setError("У  контрагента  вього {$b} бонусів на рахунку");                
                return;
            }

           
        }


    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('tovar', $item->itemname));

        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('snumber', $item->snumber));
        $row->add(new Label('sdate', $item->sdate > 0 ? \App\Helper::fd($item->sdate) : ''));

        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('disc', H::fa($item->disc)));
        $row->add(new Label('price', H::fa($item->price)));

        $row->add(new Label('amount', H::fa(floatval($item->quantity) * floatval($item->price)  )));
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');

    }

    public function serOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('service', $item->service_name));

        $row->add(new Label('serquantity', H::fqty($item->quantity)));
        $row->add(new Label('serprice', H::fa($item->price)));
        $row->add(new Label('serdisc', H::fa($item->disc)));

        $row->add(new Label('seramount', H::fa($item->quantity * $item->price)));
        $row->add(new ClickLink('serdelete'))->onClick($this, 'serdeleteOnClick');
        $row->add(new ClickLink('seredit'))->onClick($this, 'sereditOnClick');
    }

  
    public function editOnClick($sender) {
        $tovar = $sender->owner->getDataItem();
        $this->docpanel->editdetail->setVisible(true);
        $this->docpanel->editdetail->edittovar->setKey($tovar->item_id);
        $this->docpanel->editdetail->edittovar->setText($tovar->itemname);
        $this->docpanel->editdetail->editquantity->setText($tovar->quantity);
        $this->docpanel->editdetail->editprice->setText($tovar->price);
        $this->docpanel->editdetail->editserial->setText($tovar->snumber);

        $store = $this->form1->store->getValue();
        $qty = $tovar->getQuantity($store);
        $qtyex = $tovar->getQuantity() - $qty;

        $this->docpanel->editdetail->qtystock->setText(H::fqty($qty));
        $this->docpanel->editdetail->qtystockex->setText(H::fqty($qtyex));

        $this->docpanel->form2->setVisible(false);

        $this->_rowid =  array_search($tovar, $this->_itemlist, true);
        $this->_editrow =  true;
    }

    public function deleteOnClick($sender) {

        $tovar = $sender->owner->getDataItem();
        $rowid =  array_search($tovar, $this->_itemlist, true);

        $this->_itemlist = array_diff_key($this->_itemlist, array($rowid => $this->_itemlist[$rowid]));
        $this->docpanel->form2->detail->Reload();
        $this->calcTotal();
    }

    public function sereditOnClick($sender) {
        $ser = $sender->owner->getDataItem();
        $this->docpanel->editserdetail->setVisible(true);
        $this->docpanel->editserdetail->editser->setValue($ser->service_id);

        $this->docpanel->editserdetail->editserquantity->setText($ser->quantity);
        $this->docpanel->editserdetail->editserprice->setText($ser->price);

        $this->docpanel->form2->setVisible(false);
        $this->_rowid =  array_search($ser, $this->_serlist, true);
    }

    public function serdeleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }

        $ser = $sender->owner->getDataItem();
        $rowid =  array_search($ser, $this->_serlist, true);

        $this->_serlist = array_diff_key($this->_serlist, array($rowid => $this->_serlist[$rowid]));
        $this->docpanel->form2->detailser->Reload();
        $this->calcTotal();
    }

    public function addrowOnClick($sender) {
        $this->docpanel->editdetail->setVisible(true);
        $this->docpanel->editdetail->editquantity->setText("1");
        $this->docpanel->editdetail->editprice->setText("0");
        $this->docpanel->editdetail->qtystock->setText("");
        $this->docpanel->editdetail->qtystockex->setText("");
        $this->docpanel->form2->setVisible(false);
        $this->_rowid = -1;
        $this->docpanel->navbar->setVisible(false);
        $this->_editrow =  false;
        
        $this->addJavaScript("$(\"#edittovar\").focus()",true)  ;
    }

    public function addserOnClick($sender) {
        $this->docpanel->editserdetail->setVisible(true);
        $this->docpanel->editserdetail->editserquantity->setText("1");
        $this->docpanel->editserdetail->editserprice->setText("0");

        $this->docpanel->form2->setVisible(false);
        $this->_rowid = -1;
        $this->_editrow =  false;
    }

    public function saverowOnClick($sender) {
        $store = $this->form1->store->getValue();

        $id = $this->docpanel->editdetail->edittovar->getKey();
        if ($id == 0) {
            $this->setError("Не вибраний товар");
            return;
        }


        $item = Item::load($id);


        $item->quantity = doubleval($this->docpanel->editdetail->editquantity->getText());
        if ($item->quantity == 0) {
            $this->setError("Не введена  кількість");
            return;
        }
        
        $item->snumber = $this->docpanel->editdetail->editserial->getText();

        $qstock = $item->getQuantity($store);

        $item->price = H::fa($this->docpanel->editdetail->editprice->getText());
        $item->disc = '';
        $item->pureprice = $item->getPurePrice();
        if($item->pureprice > $item->price) {
            $item->disc = number_format((1 - ($item->price/($item->pureprice)))*100, 1, '.', '') ;
        }
        if ($item->quantity > $qstock) {
            $this->setWarn('Введено більше товару, чим є в наявності');
        }

        if (strlen($item->snumber) == 0 && $item->useserial == 1 && $this->_tvars["usesnumber"] == true) {
            $this->setError("Потрібна партія виробника");
            return;
        }

        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {
            $slist = $item->getSerials($store);

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


        //   $this->docpanel->editdetail->setVisible(false);
        //    $this->docpanel->form2->setVisible(true);

        $this->docpanel->editdetail->edittovar->setKey(0);
        $this->docpanel->editdetail->edittovar->setText('');

        $this->docpanel->editdetail->editquantity->setText("1");

        $this->docpanel->editdetail->editprice->setText("");
        $this->docpanel->editdetail->editserial->setText("");
        //  $this->docpanel->wselitem->setVisible(false);




        $this->docpanel->form2->detail->Reload();


        $this->calcTotal();

        //очищаем  форму
        $this->docpanel->editdetail->edittovar->setKey(0);
        $this->docpanel->editdetail->edittovar->setText('');

        $this->docpanel->editdetail->editquantity->setText("1");

        $this->docpanel->editdetail->editprice->setText("");
        $this->docpanel->editdetail->qtystockex->setText("");

        if($this->_editrow) {
            $this->docpanel->editdetail->setVisible(false);
            $this->docpanel->form2->setVisible(true);

        }
        $this->_rowid = -1;
        $this->_editrow =  false;
        $this->setSuccess("Позиція додана");
     //   $this->addJavaScript("$(\"#edittovar\").focus()",true)  ;        

    }

    public function saveserOnClick($sender) {

        $id = $this->docpanel->editserdetail->editser->getValue();
        if ($id == 0) {
            $this->setError("Не обрано послугу або роботу");
            return;
        }

        $ser = Service::load($id);

        $ser->quantity = doubleval( $this->docpanel->editserdetail->editserquantity->getText());
        if ($ser->quantity == 0) {
            $this->setError("Не введена  кількість");
            return;
        }
        
        $ser->pureprice = $ser->getPurePrice();

        $ser->price = H::fa($this->docpanel->editserdetail->editserprice->getText());
        $ser->disc = '';
        if($ser->pureprice > $ser->price) {
            $ser->disc = number_format((1 - ($ser->price/($ser->pureprice)))*100, 1, '.', '') ;
        }


        if($this->_rowid == -1) {
            $this->_serlist[] = $ser;
        } else {
            $this->_serlist[$this->_rowid] = $ser;
        }


        $this->_rowid = -1;

        $this->docpanel->editserdetail->setVisible(false);
        $this->docpanel->form2->setVisible(true);
        $this->docpanel->form2->detailser->Reload();

        //очищаем  форму
        $this->docpanel->editserdetail->editser->setValue(0);

        $this->docpanel->editserdetail->editserquantity->setText("1");
        $this->docpanel->editserdetail->editserprice->setText("");
        $this->calcTotal();
    }

    public function cancelrowOnClick($sender) {
        $this->docpanel->editdetail->setVisible(false);
        $this->docpanel->editserdetail->setVisible(false);
        $this->docpanel->form2->setVisible(true);
        $this->docpanel->wselitem->setVisible(false);

        $this->docpanel->form2->detail->Reload();

        //очищаем  форму
        $this->docpanel->editdetail->edittovar->setKey(0);
        $this->docpanel->editdetail->edittovar->setText('');

        $this->docpanel->editdetail->editquantity->setText("1");

        $this->docpanel->editdetail->editprice->setText("");
        $this->docpanel->editdetail->qtystockex->setText("");
        $this->docpanel->navbar->setVisible(true);

    }

    //справочник
    public function onOpenItemSel($sender) {
        $this->docpanel->wselitem->setVisible(true);

        $this->docpanel->wselitem->setPriceType($this->getPriceType());
        $this->docpanel->wselitem->Reload();
    }

    public function onOpenCatPan($sender) {
        $this->docpanel->wselitem->setVisible(true);

        $this->docpanel->wselitem->setPriceType($this->getPriceType());
        $this->docpanel->wselitem->Reload(true);
    }

    public function onSelectItem($item_id, $itemname) {
        $this->docpanel->editdetail->edittovar->setKey($item_id);
        $this->docpanel->editdetail->edittovar->setText($itemname);
        $this->OnChangeItem($this->docpanel->editdetail->edittovar);
    }

    private function _calcTotal() {

        $total = 0;
        $disc = 0;

        foreach ($this->_itemlist as $item) {
            $item->amount = H::fa($item->price * $item->quantity);
            if($item->disc >0) {
                //  $disc += ($item->quantity * ($item->pureprice - $item->price) );
            }

            $total = $total + $item->amount;
        }
        foreach ($this->_serlist as $item) {
            $item->amount = H::fa($item->price * $item->quantity);
            if($item->disc >0) {
                // $disc += ($item->quantity * ($item->pureprice - $item->price) );
            }

            $total = $total + $item->amount;
        }
    

        return $total;

    }
 
    private function calcTotal() {

        $total = $this->_calcTotal();
        
        $code= trim($this->docpanel->form2->promocode->getText());
        if($code != '') {
            $r = \App\Entity\PromoCode::check($code,$this->docpanel->form2->customer->getKey())  ;
            if($r == ''){
                $p = \App\Entity\PromoCode::findByCode($code);
                $disc = doubleval($p->disc );
                $discf = doubleval($p->discf );
                  
                if($disc >0)  {
                    $td = H::fa( $total * ($p->disc/100) );
                    $this->docpanel->form2->totaldisc->setText($td);
                }        
                if($discf > 0) {
                    if( $total < $discf  ) {
                        $discf = $total;
                    }
                    $this->docpanel->form2->totaldisc->setText($discf);
                }      
            }
        }        
        
        $this->docpanel->form2->total->setText(H::fa($total));

    }

    public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $item = Item::load($id);
        $store = $this->form1->store->getValue();


        $customer_id = $this->docpanel->form2->customer->getKey();

        $pt=     $this->getPriceType() ;
        $price = $item->getPriceEx(array(
         'pricetype'=>$pt,
         'store'=>$store,
         'customer'=>$customer_id
         ));

        $qty = $item->getQuantity($store);
        $qtyex = $item->getQuantity() - $qty;

        $this->docpanel->editdetail->qtystock->setText(H::fqty($qty));
        $this->docpanel->editdetail->qtystockex->setText( H::fqty($qtyex));
        
        $this->docpanel->editdetail->editprice->setText($price);
        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {

            $serial = '';
            $slist = $item->getSerials($store);
            if (count($slist) == 1) {
                $serial = array_pop($slist);
            }
            $this->docpanel->editdetail->editserial->setText($serial);
        }

    

    }

    public function OnAutoItem($sender) {

        $text = trim($sender->getText());
        return Item::findArrayAC($text);
    }

  

    public function OnChangeSer($sender) {
        $id = $sender->getValue();
        $ser = Service::load($id);
        $customer_id = $this->docpanel->form2->customer->getKey();

        $price = $ser->getPrice($customer_id);

        $this->docpanel->editserdetail->editserprice->setText($price);


    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 1);
    }

    public function OnChangeCustomer($sender) {
        $this->docpanel->form2->custinfo->setVisible(false);

        $disc = 0;
        $bonus = 0;

        $customer_id = $this->docpanel->form2->customer->getKey();

        if ($customer_id > 0) {
            $cust = Customer::load($customer_id);

            $disctext = "";
            $d = $cust->getDiscount() ;
            if (doubleval($d) > 0) {
                $disctext = "Постійна знижка {$d}%";

                $tmp=[];
                foreach($this->_itemlist as $i) {
                    if(floatval($i->disc)==0) {
                        $i->disc = $d;
                        $i->price = $i->pureprice - ($i->pureprice * $i->disc / 100)  ;
                    }
                    $tmp[]=$i;
                }
                $this->_itemlist  = $tmp;
                $tmp=[];
                foreach($this->_serlist as $i) {
                    if(floatval($i->disc)==0) {
                        $i->disc = $d;
                        $i->price = $i->pureprice - ($i->pureprice * $i->disc / 100)  ;
                    }
                    $tmp[]=$i;
                }
                $this->_serlist  = $tmp;
                $this->docpanel->form2->detail->Reload();
                $this->docpanel->form2->detailser->Reload();
                $this->calcTotal();


            } else {
                $bonus = $cust->getBonus();
                if ($bonus > 0) {
                    $disctext = "Нараховано бонусів {$bonus} ";
                }
            }
            $this->docpanel->form2->custinfo->setText($disctext);
            $this->docpanel->form2->custinfo->setVisible(strlen($disctext) >0);
      
        }
        $this->docpanel->form2->addcust->setVisible(false) ;
        $this->docpanel->form2->cinfo->setVisible(true) ;
        $this->docpanel->form2->cinfo->setAttribute('onclick', "customerInfo({$customer_id});") ;
      

    }

    //добавление нового контрагента
    public function addcustOnClick($sender) {
        $this->editcust->setVisible(true);
        $this->docpanel->form2->setVisible(false);

        $this->editcust->editcustname->setText('');
        $this->editcust->editphone->setText('');
        $this->editcust->editemail->setText('');
    }

    public function savecustOnClick($sender) {
        $custname = trim($this->editcust->editcustname->getText());
        if (strlen($custname) == 0) {
            $this->setError("Не введено назву");
            return;
        }
        $cust = new Customer();
        $cust->customer_name = $custname;
        $cust->email = $this->editcust->editemail->getText();
        $cust->phone = $this->editcust->editphone->getText();
        $cust->phone = \App\Util::handlePhone($cust->phone);

        if (strlen($cust->phone) > 0 && strlen($cust->phone) != H::PhoneL()) {
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

        $cust->type = 1;
        $cust->save();
        $this->docpanel->form2->customer->setText($cust->customer_name);
        $this->docpanel->form2->customer->setKey($cust->customer_id);

        $this->editcust->setVisible(false);
        $this->docpanel->form2->setVisible(true);
        $this->docpanel->form2->custinfo->setVisible(false);

    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docpanel->form2->setVisible(true);
    }

    //по  кнопке
    public function tosaveOnClick($sender) {

        if (count($this->_itemlist) == 0 && count($this->_serlist) == 0) {
            $this->setError('Не введено позиції');
            return;
        }
        $this->_doc->document_number = $this->_doc->nextNumber();

        if (false == $this->_doc->checkUniqueNumber()) {
            $next = $this->_doc->nextNumber();
            $this->_doc->document_number = $next;
            if (strlen($next) == 0) {
                $this->setError('Не створено унікальный номер документа');
                return  ;
            }
        }
        $this->_doc->document_date = time();
        $this->_doc->customer_id = $this->docpanel->form2->customer->getKey();
        $this->_doc->packDetails('detaildata', $this->_itemlist);
        $this->_doc->packDetails('services', $this->_serlist);
        $this->_doc->headerdata['pos'] = $this->pos->pos_id;
        $this->_doc->headerdata['pos_name'] = $this->pos->pos_name;
        $this->_doc->headerdata['store'] = $this->_store_id;
        $this->_doc->headerdata['salesource'] = $this->_salesource;
        $this->_doc->headerdata['totaldisc'] = $this->docpanel->form2->totaldisc->getText();
        $this->_doc->headerdata['promocode'] = $this->docpanel->form2->promocode->getText();
        $this->_doc->headerdata['bonus'] = $this->docpanel->form2->bonus->getText();
        $this->_doc->headerdata['prepaid'] = $this->docpanel->form2->prepaid->getText();
        $this->_doc->headerdata['pricetype'] = $this->getPriceType();

        
        $this->_doc->username =System::getUser()->username;
        $this->calcTotal()  ;
        $this->_doc->amount = $this->docpanel->form2->total->getText();

        $this->_doc->save()  ;
        $this->_doc->updateStatus(Document::STATE_EDITED);
       
        //отложеный
        if($sender->id=="topass") {
           \App\Session::getSession()->armpass=$this->_doc->document_id; 
        }

        $this->newdoc(null)  ;
    }

    
    //оплатить
    public function savedocOnClick($sender) {

        $this->_doc->document_number = $this->docpanel->form3->document_number->getText();

        $doc = Document::getFirst(" document_id <> {$this->_doc->document_id}  and   document_number = '{$this->_doc->document_number}' ");
        if ($doc instanceof Document) {   //если уже  кто то  сохранил  с таким номером
            $this->_doc->document_number = $this->_doc->nextNumber();
        }
        if (false == $this->_doc->checkUniqueNumber()) {
            $next = $this->_doc->nextNumber();
            $this->docpanel->form3->document_number->setText($next);
            $this->_doc->document_number = $next;
            if (strlen($next) == 0) {
                $this->setError('Не створено унікальный номер документа');
            }
        }
        $this->_doc->document_date = $this->docpanel->form3->document_date->getDate();
        $this->_doc->notes = trim($this->docpanel->form3->notes->getText());

        $this->_doc->customer_id = $this->docpanel->form2->customer->getKey();
        $this->_doc->payamount = $this->docpanel->form3->payamount->getText();

        $this->_doc->headerdata['promocode'] = $this->docpanel->form2->promocode->getText();
        $this->_doc->payed = doubleval($this->docpanel->form3->payed->getText()) + doubleval($this->docpanel->form3->payedcard->getText()) ;
        $this->_doc->headerdata['payed'] = $this->docpanel->form3->payed->getText();
        $this->_doc->headerdata['payedcard'] = $this->docpanel->form3->payedcard->getText();
        $this->_doc->headerdata['exchange'] = $this->docpanel->form3->exchange->getText();
        $this->_doc->headerdata['exch2b'] = $this->docpanel->form3->exch2b->getText() ;
        $this->_doc->headerdata['prepaid'] = $this->docpanel->form2->prepaid->getText() ;
        $this->_doc->headerdata['trans'] = trim($this->docpanel->form3->trans->getText());
        $this->_doc->notes = trim($this->_doc->notes . ' ' . $this->_doc->headerdata['trans']) ;
        //        $this->_doc->headerdata['totaldisc'] = $this->docpanel->form2->totaldisc->getText();

        $this->_doc->headerdata['mfnal'] = $this->form1->mfnal->getValue();
        $this->_doc->headerdata['mfbeznal'] = $this->form1->mfbeznal->getValue();

        $this->_doc->headerdata['bonus'] = $this->docpanel->form2->bonus->getText();
        $this->_doc->headerdata['totaldisc'] = $this->docpanel->form2->totaldisc->getText();

        if ($this->_doc->payamount > 0 && $this->_doc->payamount > $this->_doc->payed && $this->_doc->customer_id == 0) {
            $this->setError("Якщо у борг або передоплата або нарахування бонусів має бути обраний контрагент");
            return;
        }
 
        if (doubleval($this->_doc->headerdata['bonus']) >0 && $this->_doc->customer_id == 0) {
            $this->setError("Якщо у борг    або нарахування бонусів має бути обраний контрагент");
            return;
        }

        if (doubleval($this->_doc->headerdata['exch2b']) >0 && $this->_doc->customer_id == 0) {
            $this->setError("Для нарахування бонуса має бути обраний контрагент");
            return;
        }
        if (doubleval($this->_doc->headerdata['prepaid']) >0 && $this->_doc->customer_id == 0) {
            $this->setError("Якщо передоплата має бути обраний контрагент");
            return;
        }
        if (doubleval($this->_doc->headerdata['exchange']) >0 && doubleval($this->_doc->headerdata['payedcard'] >0)) {
            $this->setError("При оплаті карткою решта має бути 0");
            return;
        }
 
        $this->_doc->headerdata['pos'] = $this->pos->pos_id;
        $this->_doc->headerdata['pos_name'] = $this->pos->pos_name;
        $this->_doc->headerdata['store'] = $this->_store_id;
        $this->_doc->headerdata['salesource'] = $this->_salesource;
        $this->_doc->headerdata['pricetype'] = $this->getPriceType();

        
        $this->_doc->username =System::getUser()->username;

        $firm = H::getFirmData( );
        $this->_doc->headerdata["firm_name"] = $firm['firm_name'];
        $this->_doc->headerdata["inn"] = $firm['inn'];
        $this->_doc->headerdata["address"] = $firm['address'];
        $this->_doc->headerdata["phone"] = $firm['phone'];
       
        $this->_doc->packDetails('detaildata', $this->_itemlist);
        $this->_doc->packDetails('services', $this->_serlist);

        $this->_doc->amount = $this->docpanel->form2->total->getText();
        $isnew  = $this->_doc->document_id ==0;
        
        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

            $exeparent=false;  //списано в  редительском
            if($this->_doc->parent_id >0) {
                $bd = Document::load($this->_doc->parent_id)->cast();
                $bd->payamount = 0;
                $bd->payed = 0;
                $bd->save();
                if($bd->state==Document::STATE_WP) {
                   $bd->updateStatus(Document::STATE_PAYED);
           
                }
                if ($bd->meta_name == 'ServiceAct' ) {
                    $exeparent=true;
           
                }
                
                if ($bd->meta_name == 'Order' ) {
                    if($bd->state == Document::STATE_INPROCESS || $bd->state == Document::STATE_READYTOSHIP) {
                        $bd->updateStatus(Document::STATE_INSHIPMENT);
                    }                       
                    $bd->unreserve();
                }
                
            }
            
            // проверка на минус  в  количестве
            $allowminus = System::getOption("common", "allowminus");
            if ($allowminus != 1  && $exeparent==false) {

                foreach ($this->_itemlist as $item) {
                    $qty = $item->getQuantity($this->_doc->headerdata['store']);
                    if ($qty < $item->quantity) {
                        throw new \Exception("На складі всього ".H::fqty($qty)." ТМЦ {$item->itemname}. Списання у мінус заборонено");
                    }
                }
            }

            $this->_doc->save();
            if($isnew) {
                $this->_doc->updateStatus(Document::STATE_NEW);
            }


            $this->_doc->updateStatus(Document::STATE_EXECUTED);

            if (H::fa($this->_doc->payamount) > H::fa($this->_doc->payed)) {
                $this->_doc->updateStatus(Document::STATE_WP);
            }            
            

            if($this->pos->usefisc == 1) {
                if($this->docpanel->form3->passfisc->isChecked()) {
                    $this->_doc->headerdata["passfisc"]  = 1;
                } else {
                    $this->_doc->headerdata["passfisc"]  = 0;
                    if($this->_tvars['checkbox'] == true) {

                        $cb = new  \App\Modules\CB\CheckBox($this->pos->cbkey, $this->pos->cbpin) ;
                        $ret = $cb->Check($this->_doc) ;

                        if(is_array($ret)) {
                            $this->_doc->headerdata["fiscalnumber"] = $ret['fiscnumber'];
                            $this->_doc->headerdata["tax_url"] = $ret['tax_url'];
                            $this->_doc->headerdata["checkbox"] = $ret['checkid'];
                        } else {
                            throw new \Exception($ret);

                        }


                    }
                    if($this->_tvars['vkassa'] == true) {
                        $vk = new  \App\Modules\VK\VK($this->pos->vktoken) ;
                        $ret = $vk->Check($this->_doc) ;

                        if(is_array($ret)) {
                            $this->_doc->headerdata["fiscalnumber"] = $ret['fiscnumber'];
                            $this->_doc->headerdata["tax_url"] = $ret['tax_url'];
                            $this->_doc->headerdata["vkassa"] = $ret['checkid'];
                                                 
                        } else {
                            throw new \Exception($ret);

                        }         
                    }
                    if ($this->_tvars['ppo'] == true) {


                        $this->_doc->headerdata["fiscalnumberpos"]  = $this->pos->fiscalnumber;


                        $ret = \App\Modules\PPO\PPOHelper::check($this->_doc);
                        if ($ret['success'] == false && $ret['doclocnumber'] > 0) {
                            //повторяем для  нового номера
                            $this->pos->fiscdocnumber = $ret['doclocnumber'];
                            $this->pos->save();
                            $ret = \App\Modules\PPO\PPOHelper::check($this->_doc);
                        }
                        if ($ret['success'] == false) {
                            $this->setErrorTopPage($ret['data']);
                            throw new \Exception($ret['data']);
                        } else {
                            //  $this->setSuccess("Выполнено") ;
                            if ($ret['docnumber'] > 0) {
                                $this->pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                                $this->pos->save();
                                $this->_doc->headerdata["fiscalnumber"] = $ret['docnumber'];
                                $this->_doc->headerdata["fiscalamount"] = $ret['fiscalamount'];
                                $this->_doc->headerdata["fiscaltest"] = $ret['fiscaltest'];
                            } else {
                                throw new \Exception("Не повернено фіскальний номер");
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
            if($isnew) {
               $this->_doc->document_id =0;                
            }
            
            return;
        }
        $this->docpanel->form2->customer->setKey(0);
        $this->docpanel->form2->customer->setText('');

        $this->docpanel->form3->setVisible(false);
        $this->docpanel->form2->setVisible(false);
        $this->docpanel->formcheck->setVisible(true);
        $this->docpanel->form3->notes->setText('');
        $check = $this->_doc->generatePosReport();
        $this->docpanel->formcheck->showcheck->setText($check, true);

        $this->docpanel->formcheck->qrpaybtn->setVisible(false);
        $qr = $this->_doc->getQRPay()   ;
        if(is_array($qr)) {
            $this->docpanel->formcheck->qrpaybtn->setVisible(true);
            $this->qrimg->setText($qr['qr'], true);
        }
  

    }

    public function OnOpenShift($sender) {
 
        if($this->_tvars['checkbox'] == true) {


            $cb = new  \App\Modules\CB\CheckBox($this->pos->cbkey, $this->pos->cbpin) ;
            $ret = $cb->OpenShift() ;

            if($ret === true) {
                $this->setSuccess("Зміна відкрита");
            } else {
                $this->setError($ret);
            }
            if($this->pos->autoshift >0){
                $task = new  \App\Entity\CronTask()  ;
                $task->tasktype = \App\Entity\CronTask::TYPE_AUTOSHIFT;
                $t =   strtotime(  date('Y-m-d ') .  $this->pos->autoshift.':00' );  
                 
                $task->starton=$t;
                $task->taskdata= serialize(array(
                       'pos_id'=>$this->pos->pos_id, 
                       'type'=>'cb' 
       
                    ));         
                $task->save();
                    
            }  


            return;
        }

         if($this->_tvars['vkassa'] == true) {


            $vk = new  \App\Modules\VK\VK($this->pos->vktoken) ;
            $ret = $vk->OpenShift() ;

            if($ret === true) {
                $this->setSuccess("Зміна відкрита");
            } else {
                $this->setError($ret);
            }
            if($this->pos->autoshift >0){
                $task = new  \App\Entity\CronTask()  ;
                $task->tasktype = \App\Entity\CronTask::TYPE_AUTOSHIFT;
                $t =   strtotime(  date('Y-m-d ') .  $this->pos->autoshift.':00' );  
                 
                $task->starton=$t;
                $task->taskdata= serialize(array(
                       'pos_id'=>$this->pos->pos_id, 
                       'type'=>'vk' 
       
                    ));         
                $task->save();
                    
            }  


            return;
        }
        $ret = \App\Modules\PPO\PPOHelper::shift($this->pos->pos_id, true);
        if ($ret['success'] == false && $ret['doclocnumber'] > 0) {
            //повторяем для  нового номера
            $this->pos->fiscdocnumber = $ret['doclocnumber'];
            $this->pos->save();
            $ret = \App\Modules\PPO\PPOHelper::shift($this->pos->pos_id, true);
        }
        if ($ret['success'] == false) {
            $this->setErrorTopPage($ret['data']);
            return false;
        } else {
            $this->setSuccess("Зміна відкрита");
            if ($ret['doclocnumber'] > 0) {
                $this->pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                $this->pos->save();

            }
            \App\Modules\PPO\PPOHelper::clearStat($this->pos->pos_id);
            
            //задача  для  автозакрытия
            if($this->pos->autoshift >0){
                $task = new  \App\Entity\CronTask()  ;
                $task->tasktype = \App\Entity\CronTask::TYPE_AUTOSHIFT;
                $t =   strtotime(  date('Y-m-d ') .  $this->pos->autoshift.':00' );  
                  
                $task->starton=$t;
                $task->taskdata= serialize(array(
                       'pos_id'=>$this->pos->pos_id, 
                       'type'=>'ppro' 
       
                    ));         
                $task->save();
                    
            }              
            
        }


        $this->pos->save();
       
    }

    public function OnCloseShift($sender) {

        if($this->_tvars['checkbox'] == true) {

            $cb = new  \App\Modules\CB\CheckBox($this->pos->cbkey, $this->pos->cbpin) ;
            $ret = $cb->CloseShift() ;

            if($ret === true) {
                $this->setSuccess("Зміна закрита");
            } else {
                $this->setError($ret);
            }

            return;
        }
        if($this->_tvars['vkassa'] == true) {

            $vk = new  \App\Modules\VK\VK($this->pos->vktoken) ;
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
    //для ПРРО
    public function zform() {

        $stat = \App\Modules\PPO\PPOHelper::getStat($this->pos->pos_id);
        $rstat = \App\Modules\PPO\PPOHelper::getStat($this->pos->pos_id, true);

        $ret = \App\Modules\PPO\PPOHelper::zform($this->pos->pos_id, $stat, $rstat);
        if (strpos($ret['data'], 'ZRepAlreadyRegistered')) {
            return true;
        }
        if ($ret['success'] == false && ($ret['doclocnumber'] ??0 ) > 0) {
            //повторяем для  нового номера
            $this->pos->fiscdocnumber = $ret['doclocnumber'];
            $this->pos->save();
            $ret = \App\Modules\PPO\PPOHelper::zform($this->pos->pos_id, $stat, $rstat);
        }
        if ($ret['success'] == false) {
            $this->setErrorTopPage($ret['data']);
            return false;
        } else {

            if ($ret['doclocnumber'] > 0) {
                $this->pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                $this->pos->save();
            } else {
                $this->setError("Не повернено фіскальний номер");
                return;
            }
        }


        return true;
    }

    //для ПРРО
    public function closeshift() {
        $ret = \App\Modules\PPO\PPOHelper::shift($this->pos->pos_id, false);
        if ($ret['success'] == false && $ret['doclocnumber'] > 0) {
            //повторяем для  нового номера
            $this->pos->fiscdocnumber = $ret['doclocnumber'];
            $this->pos->save();
            $ret = \App\Modules\PPO\PPOHelper::shift($this->pos->pos_id, false);
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
                $this->pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                $this->pos->save();
            }
            \App\Modules\PPO\PPOHelper::clearStat($this->pos->pos_id);
        }


        return true;
    }

    //строка  списка чеков
    public function onDocRow($row) {
        $doc = $row->getDataItem();
        $row->add(new ClickLink('rownumber', $this, 'OnDocViewClick'))->setValue($doc->document_number);
        $row->add(new Label('rowamount', H::fa(($doc->payamount > 0) ? $doc->payamount : ($doc->amount > 0 ? $doc->amount : ""))));


        $row->add(new Label('rowdate', H::fd($doc->document_date)));
        $row->add(new Label('rownotes', $doc->notes));
        $row->add(new Label('rowauthor', $doc->username));
        $row->add(new ClickLink('checkedit'))->onClick($this, "onEdit");
        $row->checkedit->setVisible($doc->state < 4);

        $row->add(new \Zippy\Html\Link\RedirectLink('checkreturn', "\\App\\Pages\\Doc\\ReturnIssue", array(0,$doc->document_id)));
        $row->checkreturn->setVisible($doc->state > 4);
        if ($doc->document_id == $this->_doc->document_id) {
           // $row->setAttribute('class', 'table-success');
        }


        $row->add(new Label('rtlist'));
        $t ="<table   style=\"font-size:smaller\"> " ;

        $tlist=  $doc->unpackDetails('detaildata')  ;

        foreach($tlist as $prod) {
            $t .="<tr> " ;
            $t .="<td style=\"padding:2px\" >{$prod->itemname} </td>" ;
            $t .="<td style=\"padding:2px\" class=\"text-right\">". H::fqty($prod->quantity) ."</td>" ;
            $t .="<td style=\"padding:2px\" class=\"text-right\">". H::fa($prod->price) ."</td>" ;
            $t .="<td style=\"padding:2px\" class=\"text-right\">". H::fa($prod->quantity * $prod->price) ."</td>" ;

            $t .="</tr> " ;                                                
        }
        $tlist=  $doc->unpackDetails('services')  ;

        foreach($tlist as $prod) {
            $t .="<tr> " ;
            $t .="<td style=\"padding:2px\" >{$prod->service_name} </td>" ;
            $t .="<td style=\"padding:2px\" class=\"text-right\">". H::fa($prod->quantity) ."</td>" ;
            $t .="<td style=\"padding:2px\" class=\"text-right\">". H::fa($prod->price) ."</td>" ;
            $t .="<td style=\"padding:2px\" class=\"text-right\">". H::fa($prod->quantity * $prod->price) ."</td>" ;

            $t .="</tr> " ;
        }

        $t .="</table> " ;

        $row->rtlist->setText($t, true);
        $row->add(new ClickLink('checkfisc', $this, "onFisc"))->setVisible(($doc->headerdata['passfisc'] ?? 0) == 1) ;

        if($doc->state <5) {
           $row->checkfisc->setVisible(false);
        }
        if($this->pos->usefisc != 1) {
           $row->checkfisc->setVisible(false);
        }

    }

    public function updatechecklist($sender) {
        $conn = \ZDB\DB::getConnect();

        $where = "meta_name='PosCheck' and  document_date  >= " . $conn->DBDate(strtotime('-1 month'))    ;


        if ($sender instanceof Form) {
            $text = trim($sender->searchnumber->getText());
            $cust = $sender->searchcust->getKey();
            if (strlen($text) > 0) {
                $where .= " and document_number=" . Document::qstr($text);
            }
            if (strlen($text) == 0 && $cust > 0) {
                $where .= " and customer_id=" . $cust;
            }


        }
        $this->_doclist = Document::find($where, ' document_id desc');
        $this->checklistpan->checklist->Reload();
    }

    public function onFisc($sender) {

        $doc =  $sender->getOwner()->getDataItem();

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

            if($this->_tvars['checkbox'] == true) {

                $cb = new  \App\Modules\CB\CheckBox($this->pos->cbkey, $this->pos->cbpin) ;
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
                $vk = new  \App\Modules\VK\VK($this->pos->vktoken) ;
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


                $doc->headerdata["fiscalnumberpos"]  = $this->pos->fiscalnumber;


                $ret = \App\Modules\PPO\PPOHelper::check($doc);
                if ($ret['success'] == false && $ret['doclocnumber'] > 0) {
                    //повторяем для  нового номера
                    $this->pos->fiscdocnumber = $ret['doclocnumber'];
                    $this->pos->save();
                    $ret = \App\Modules\PPO\PPOHelper::check($this->_doc);
                }
                if ($ret['success'] == false) {
                      throw new \Exception($ret['data']);

                } else {
                    //  $this->setSuccess("Выполнено") ;
                    if ($ret['docnumber'] > 0) {
                        $this->pos->fiscdocnumber = $ret['doclocnumber'] + 1;
                        $this->pos->save();
                        $doc->headerdata["fiscalnumber"] = $ret['docnumber'];
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
        
        $this->updatechecklist(null);
    }

    public function onEdit($sender) {
        $item =  $sender->getOwner()->getDataItem();
        $doc = Document::load($item->document_id);
        if($doc->checkStates([Document::STATE_CANCELED])==false){  //если не  отменялся
           $doc->document_date=time();                
        }
        $this->loadDoc($doc);
    }

    public function fromPass($sender) {
        $pn= intval( \App\Session::getSession()->armpass ?? 0 );        
        $doc = Document::load($pn);
        if($doc != null && $doc->state <5) {
           $this->loadDoc($doc);    
        }
        \App\Session::getSession()->armpass = null;
        
    }
    
    public function loadDoc($doc) {
        $pn= intval( \App\Session::getSession()->armpass ?? 0 );        
        if($pn==$doc->document_id) {
            \App\Session::getSession()->armpass = null;
        }
        $this->_doc = $doc->cast();

        $this->docpanel->setVisible(true);
        $this->docpanel->form2->setVisible(true);
        $this->checklistpan->setVisible(false);
        $this->checklistpan->searchform->clean();


        $this->_itemlist = $this->_doc->unpackDetails('detaildata');
        $this->_serlist = $this->_doc->unpackDetails('services');

        $this->docpanel->form2->detail->Reload();
        $this->docpanel->form2->detailser->Reload();
        $this->calcTotal();

        $this->docpanel->form2->addcust->setVisible(true) ;
        $this->docpanel->form2->cinfo->setVisible(false) ;
        $this->docpanel->form2->custinfo->setText('');

        $this->docpanel->form2->customer->setKey(0);
        $this->docpanel->form2->customer->setText('');
        if($this->_doc->customer_id >0) {
            $this->docpanel->form2->customer->setKey($this->_doc->customer_id);
            $this->docpanel->form2->customer->setText($this->_doc->customer_name);
            $this->OnChangeCustomer($this->docpanel->form2->customer) ;
        }


        $this->docpanel->form2->promocode->setText($this->_doc->headerdata['promocode']);
        $this->docpanel->form2->bonus->setText($this->_doc->headerdata['bonus']);
        $this->docpanel->form2->totaldisc->setText($this->_doc->headerdata['totaldisc']);
        $this->docpanel->form2->prepaid->setText($this->_doc->headerdata['prepaid']);
        $this->docpanel->form3->payamount->setText('0');
        $this->docpanel->form3->payed->setText('0');
        $this->docpanel->form3->payedcard->setText('0');
        $this->docpanel->form3->exchange->setText('0');
        $this->docpanel->form3->trans->setText('') ;
        $this->docpanel->form2->setVisible(true);


    }

    public function OnDocViewClick($sender) {
        $this->_doc = $sender->getOwner()->getDataItem();
        $this->OnDocView();

    }

    public function OnDocView() {
        $this->checklistpan->statuspan->setVisible(true);

        $this->checklistpan->statuspan->docview->setDoc($this->_doc);
        $this->checklistpan->checklist->Reload(false);
        //      $this->updateStatusButtons();
        $this->goAnkor('dankor');
    }

    //тип  цены с  учетом  контрагента
    private function getPriceType() {
        $id = $this->docpanel->form2->customer->getKey();
        if ($id > 0) {
            $cust = \App\Entity\Customer::load($id);
            if (strlen($cust->pricetype) > 4) {
                return $cust->pricetype;
            }


        }

        return $this->_pt;
    }

    public function getPriceByQty($args, $post=null) {
        $item = Item::load($args[0]) ;
        $args[1] = str_replace(',', '.', $args[1]) ;
        $price = $item->getActionPriceByQuantity($args[1]);

        return  $price;

    }

    public function checkPromo($args, $post=null) {
        $code = trim($args[0]) ;
        if($code=='')  {
            return json_encode([], JSON_UNESCAPED_UNICODE);             
        }
        $r = \App\Entity\PromoCode::check($code,$this->docpanel->form2->customer->getKey())  ;
        if($r != ''){
            return json_encode(array('error'=>$r), JSON_UNESCAPED_UNICODE);                
        }
        $total=$this->_calcTotal();
        $p = \App\Entity\PromoCode::findByCode($code);
        $disc = doubleval($p->disc );
        if($disc >0)  {
            $td = H::fa( $total * ($p->disc/100) );
            $ret=array('disc'=>$td) ;
            return json_encode($ret, JSON_UNESCAPED_UNICODE);
             
        }        
        if($disc >0)  {
          
            if($total < $disc)  {
               $disc =  $total;
            }
            $ret=array('disc'=>$disc) ;
            return json_encode($ret, JSON_UNESCAPED_UNICODE);
             
        }        
        return json_encode([], JSON_UNESCAPED_UNICODE);             
       

    }

    public function OnPrint($sender) {


        if(intval(\App\System::getUser()->prtype) == 0) {


            $this->addAjaxResponse(" $('#showcheck').printThis() ");

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

    public function OnChangeItemSm($sender) {
        $id = $sender->getKey();
        $item = Item::load($id);
        $store = $this->form1->store->getValue();


        $customer_id = $this->docpanel->form2->customer->getKey();

        $pt=     $this->getPriceType() ;
        $price = $item->getPriceEx(array(
         'pricetype'=>$pt,
         'store'=>$store,
         'customer'=>$customer_id
         ));

        $qty = $item->getQuantity($store);
        $this->docpanel->form2->storeqtysm->setText("На складі: ".H::fqty($qty) );


    }

    public function OnAutoItemSm($sender) {
        $store = $this->form1->store->getValue();

        $partname = trim($sender->getText());


        $criteria = "  disabled <> 1 ";
        if ($store > 0) {
            $criteria .= "     and item_id in (select item_id from store_stock  where  store_id={$store})";
        }


        if (strlen($partname) > 0) {
            $like = Item::qstr('%' . $partname . '%');
            $partname = Item::qstr($partname);
            $criteria .= "  and  (itemname like {$like} or item_code like {$like}   or   bar_code like {$like} )";
        }

        

        $list = array();
        foreach (Item::findYield($criteria,"itemname") as $key => $value) {

            if(intval($value->useserial) != 0) {
                continue;
            }

            $list[$key] = $value->itemname;
            if (strlen($value->item_code) > 0) {
                $list[$key] = $value->itemname . '  (' . $value->item_code .')';
            }
            $price = $value->getPrice();
            $list[$key] =  $list[$key]  . ' ,' . H::fa($price);
        }

        return $list;


    }

    //штриз код
    public function addcodeOnClick($sender) {
        $barcode = trim($this->docpanel->form2->barcode->getText());
        $code0 = $barcode;
        $code = ltrim($barcode, '0');

        $store = $this->form1->store->getValue();
        $this->docpanel->form2->barcode->setText('');
        if ($code == '') {
            return;
        }


        $code_ = Item::qstr($code);
        $code__ = trim($code_,"'") ;        
        $item = Item::getFirst("  (item_code = {$code_} or bar_code = {$code_}   or detail like '%<bar_code1><![CDATA[{$code__}]]></bar_code1>%'   or detail like '%<bar_code2><![CDATA[{$code__}]]></bar_code2>%'   )");
      
        // проверка  на  стикер
        if ($item == null) {
       
            $item = Item::unpackStBC($barcode);
            if($item instanceof Item) {
                $item->pureprice = $item->getPurePrice();
                $this->_itemlist[ ] = $item;

                $this->docpanel->form2->detail->Reload();
                $this->calcTotal();  
                return;           
            }
        }  
          
        if ($item == null) {
            $this->setWarn("Товар з кодом `{$code}` не знайдено");
            \App\Application::$app->getResponse()->addJavaScript("new Audio('/assets/error.mp3').play()", true);
                
            return;
        }

       \App\Application::$app->getResponse()->addJavaScript("new Audio('/assets/good.mp3').play()", true);
        $qty = $item->getQuantity($store);
      
       
        foreach ($this->_itemlist as $ri => $_item) {
            if ($_item->bar_code == $code || $_item->item_code == $code) {
                $this->_itemlist[$ri]->quantity += 1;
                $this->docpanel->form2->detail->Reload();
                $this->calcTotal();
                $this->checkQty($item);
                return;
            }
        }

        $this->checkQty($item);
        $customer_id = $this->docpanel->form2->customer->getKey();

        $pt=     $this->getPriceType() ;
        $price = $item->getPriceEx(array(
           'pricetype'=>$pt,
           'store'=>$store,
           'customer'=>$customer_id
         ));
        $item->price = $price;
        $item->quantity = 1;
        $item->pureprice = $item->getPurePrice();
        if($item->pureprice > $item->price) {
            $item->disc = number_format((1 - ($item->price/($item->pureprice)))*100, 1, '.', '') ;
        }

        if ($this->_tvars["usesnumber"] == true && $item->useserial == 1) {

            $serial = '';
            $slist = $item->getSerials($store);
            if (count($slist) == 1) {
                $serial = array_pop($slist);
            }

            if (strlen($serial) == 0) {
                $this->setWarn('Потрібна партія виробника');
                $this->docpanel->editdetail->setVisible(true);
                $this->docpanel->form2->setVisible(false);

                $this->docpanel->editdetail->edittovar->setKey($item->item_id);
                $this->docpanel->editdetail->edittovar->setText($item->itemname);
                $this->docpanel->editdetail->editserial->setText('');
                $this->docpanel->editdetail->editquantity->setText('1');
                $this->docpanel->editdetail->editprice->setText($item->price);

                return;
            } else {
                $item->snumber = $serial;
            }
        }


        $this->_itemlist[ ] = $item;

        $this->docpanel->form2->detail->Reload();
        $this->calcTotal();
    }
    
    
    //ввод  в  упрощенном режиме
    public function addItemSmOnClick($sender) {
        $store = $this->form1->store->getValue();

        $id = $this->docpanel->form2->addtovarsm->getKey();
        if ($id == 0) {
            $this->setError("Не вибрано товар");
            return;
        }
        $n = trim($this->docpanel->form2->addtovarsm->getText());
        if (strlen($n) == 0) {
            $this->setError("Не вибрано товар");
            return;
        }


        $item = Item::load($id);


        $item->quantity = doubleval($this->docpanel->form2->qtysm->getText());
        if ($item->quantity == 0) {
            $this->setError("Не введена  кількість");
            return;
        }

        
        $this->docpanel->form2->addtovarsm->setKey(0);
        $this->docpanel->form2->addtovarsm->setText('');

        $this->docpanel->form2->qtysm->setText("");
        $this->docpanel->form2->storeqtysm->setText("");

        
        $qstock = $item->getQuantity($store);
 
        foreach ($this->_itemlist as $ri => $_item) {
            if ($item->item_id ==  $_item->item_id ) {
                $this->_itemlist[$ri]->quantity += $item->quantity;
                $this->docpanel->form2->detail->Reload();
                $this->calcTotal();
              
                return;
            }
        }
 
        $pt=     $this->getPriceType() ;
        $price = $item->getPriceEx(array(
         'pricetype'=>$pt,
         'quantity'=>$qstock,
         'store'=>$store

         ));


        $item->price = H::fa($price);

        $item->disc = '';
        $item->pureprice = $item->getPurePrice();
        if($item->pureprice > $item->price) {
            $item->disc = number_format((1 - ($item->price/($item->pureprice)))*100, 1, '.', '') ;
        }
 
 
        if($this->_rowid == -1) {
            $this->_itemlist[] = $item;
        } else {
            $this->_itemlist[$this->_rowid] = $item;
        }

  
        $this->docpanel->form2->detail->Reload();


        $this->calcTotal();

        $this->setSuccess("Позиція додана");

    }

    /**
    * предупреждение о заканчившемся  товаре
    * 
    * @param Item $item
     */
    private function checkQty(Item $item) {
        $store = $this->form1->store->getValue();

        $qty = $item->getQuantity($store);
        
        foreach ($this->_itemlist as $ri => $_item) {
            if ($item->item_id ==  $_item->item_id ) {
                
               $qty = $qty - $_item->quantity;
                
            }
        }
        

        if (  $qty <= 0  ) {
            $this->setError("Товару {$item->itemname} не залишилось на складі");
            return;            
        }
        if ($item->minqty > 0 && $qty < $item->minqty   ) {
            $this->setWarn("Товару {$item->itemname} залишилось менше  мінімальної кількості");
            return;                        
        }        
    }
    
    public function beforeRender() {
        
        $pn= intval( \App\Session::getSession()->armpass ?? 0 );        
        $this->docpanel->form2->topass->setVisible($pn==0);
        $this->docpanel->form2->frompass->setVisible($pn>0);
        
        parent::beforeRender()  ;
    }
}

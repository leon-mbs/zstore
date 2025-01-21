<?php

namespace App\Pages\Service;

use App\Entity\Customer;

use App\Entity\Category;
use App\Entity\Item;
use App\Entity\Service;
use App\Entity\PromoCode;
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
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use Zippy\Binding\PropertyBinding as Bind;

/**
 * Скидки и акции
 */
class Discounts extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowSer('Discounts')) {
            return;
        }
        //кнопки
        $this->add(new ClickLink('tabo', $this, 'onTab'));
        $this->add(new ClickLink('tabc', $this, 'onTab'));
        $this->add(new ClickLink('tabi', $this, 'onTab'));
        $this->add(new ClickLink('tabs', $this, 'onTab'));
        $this->add(new ClickLink('tabp', $this, 'onTab'));
        $this->add(new ClickLink('tabe', $this, 'onTab'));
        //панели
        $this->add(new Panel('otab'));
        $this->add(new Panel('ctab'));
        $this->add(new Panel('itab'));
        $this->add(new Panel('stab'));
        $this->add(new Panel('ptab'));
        $this->add(new Panel('etab'));

        $this->onTab($this->tabo);

        $common = System::getOptions("common");
        $this->_tvars['price1name'] = $common['price1'];

        $disc = System::getOptions("discount");
        if (!is_array($disc)) {
            $disc = array();
        }

        $form = $this->otab->add(new  Form("commonform"));
        $form->onSubmit($this, "onCommon");
        $form->add(new  TextInput("firstbay", $disc["firstbay"]));
        $form->add(new  TextInput("bonus1", $disc["bonus1"]));
        $form->add(new  TextInput("summa1", $disc["summa1"]));
        $form->add(new  TextInput("bonus2", $disc["bonus2"]));
        $form->add(new  TextInput("summa2", $disc["summa2"]));
        $form->add(new  TextInput("bonus3", $disc["bonus3"]));
        $form->add(new  TextInput("summa3", $disc["summa3"]));
        $form->add(new  TextInput("bonus4", $disc["bonus4"]));
        $form->add(new  TextInput("summa4", $disc["summa4"]));

    //покупатели
        $this->otab->add(new Form('pbfilter'))->onSubmit($this, 'OnPBAdd');
        $this->otab->pbfilter->add(new AutocompleteTextInput('pbsearchkey'))->onText($this, 'OnAutoCustomer');
        $this->otab->pbfilter->add(new TextInput('pbsearchbon'));
        

 
        $this->otab->add(new DataView('pblist', new BonusCustomerDataSource($this), $this, 'bcustomerlistOnRow'));
        $this->otab->pblist->setPageSize(H::getPG());
        $this->otab->add(new \Zippy\Html\DataList\Paginator('pbpag', $this->otab->pblist));

        $this->otab->pblist->Reload();
        
     //бонусы      
        $this->otab->add(new Form('blfilter'))->onSubmit($this, 'OnPL');
        $this->otab->blfilter->add(new TextInput('blsearch'));
      
        $this->otab->add(new DataView('listbon', new BonusListCustomerDataSource($this), $this, 'bcustomerlistBOnRow'));
        $this->otab->listbon->setPageSize(25);
        $this->otab->add(new \Zippy\Html\DataList\Paginator('lbpag', $this->otab->listbon));
        $this->otab->add(new Label('sumbonuses','0'));
        
        $this->Onpl($this->otab->blfilter);

        
        $form = $this->ctab->add(new  Form("discform"));
        $form->onSubmit($this, "onDisc");

        $form->add(new  TextInput("disc1", $disc["disc1"]));
        $form->add(new  TextInput("discsumma1", $disc["discsumma1"]));
        $form->add(new  TextInput("disc2", $disc["disc2"]));
        $form->add(new  TextInput("discsumma2", $disc["discsumma2"]));
        $form->add(new  TextInput("disc3", $disc["disc3"]));
        $form->add(new  TextInput("discsumma3", $disc["discsumma3"]));
        $form->add(new  TextInput("disc4", $disc["disc4"]));
        $form->add(new  TextInput("discsumma4", $disc["discsumma4"]));

        //покупатели
        $this->ctab->add(new Form('cfilter'))->onSubmit($this, 'OnCAdd');
        $this->ctab->cfilter->add(new AutocompleteTextInput('csearchkey'))->onText($this, 'OnAutoCustomer');
        $this->ctab->cfilter->add(new TextInput('csearchdisc'));

        $this->ctab->add(new  Form("clistform"))->onSubmit($this, 'OnCSave');

        $this->ctab->clistform->add(new DataView('clist', new DiscCustomerDataSource($this), $this, 'customerlistOnRow'));
        $this->ctab->clistform->clist->setPageSize(H::getPG());
        $this->ctab->clistform->add(new \Zippy\Html\DataList\Paginator('cpag', $this->ctab->clistform->clist));

        $this->ctab->clistform->clist->Reload();

        //категории
        $this->itab->add(new Form('gfilter'))->onSubmit($this, 'OnGAdd');
        $this->itab->gfilter->add(new DropDownChoice('gsearchkey', Category::getList(false, false), 0));
        $this->itab->gfilter->add(new Date('gsearchfrom'))->setDate(time());
        $this->itab->gfilter->add(new Date('gsearchto'))->setDate(strtotime("+7day", time()));
        $this->itab->gfilter->add(new TextInput('gsearchdisc'));


        //услуги
        $this->stab->add(new Form('sfilter'))->onSubmit($this, 'OnSAdd');
        $this->stab->sfilter->add(new DropDownChoice('ssearchkey', Service::findArray("service_name", "disabled<>1", "service_name"), 0));
        $this->stab->sfilter->add(new Date('ssearchfrom'))->setDate(time());
        $this->stab->sfilter->add(new Date('ssearchto'))->setDate(strtotime("+7day", time()));
        $this->stab->sfilter->add(new TextInput('ssearchdisc'));

        $this->stab->add(new DataView('slist', new DiscSerDataSource($this), $this, 'serlistOnRow'));
        $this->stab->slist->setPageSize(H::getPG());
        $this->stab->add(new \Zippy\Html\DataList\Paginator('spag', $this->stab->slist));

        $this->stab->slist->Reload();

        //товары

        $this->itab->add(new Form('ifilter'));
        $this->itab->ifilter->add(new AutocompleteTextInput('isearchkey'))->onText($this, 'OnAutoItem');
        $this->itab->ifilter->isearchkey->onChange($this, "OnIsearchKey", true);

        $this->itab->ifilter->add(new Date('isearchfrom'))->setDate(time());
        $this->itab->ifilter->add(new Date('isearchto'))->setDate(strtotime("+7day", time()));
        $this->itab->ifilter->add(new TextInput('isearchdisc'));
        $this->itab->ifilter->add(new SubmitButton('ifiltersbm'))->onClick($this, 'OnIAdd');


        $this->itab->add(new Form('itform'));

        $this->itab->itform->add(new DataView('ilist', new DiscItemDataSource($this), $this, 'itemlistOnRow'));
        $this->itab->itform->ilist->setPageSize(H::getPG());
        $this->itab->itform->add(new \Zippy\Html\DataList\Paginator('ipag', $this->itab->itform->ilist));
        $this->itab->itform->add(new SubmitLink('deleteall'))->onClick($this, 'OnDelAll');

        $this->itab->itform->ilist->Reload();

        $this->itab->add(new Form('iofilter')) ;
        $this->itab->iofilter->add(new AutocompleteTextInput('isearchokey'))->onText($this, 'OnAutoItem');
        $this->itab->iofilter->isearchokey->onChange($this, "OnIsearchoKey", true);
        $this->itab->iofilter->add(new TextInput('isearchoqty1'));
        $this->itab->iofilter->add(new TextInput('isearchoprice1'));
        $this->itab->iofilter->add(new TextInput('isearchoqty2'));
        $this->itab->iofilter->add(new TextInput('isearchoprice2'));

        $this->itab->iofilter->add(new SubmitButton('iofiltersbm'))->onClick($this, 'OnIOAdd');


        $this->itab->add(new DataView('iolist', new DiscItemODataSource($this), $this, 'oitemlistOnRow'));
        $this->itab->iolist->setPageSize(H::getPG());
        $this->itab->add(new \Zippy\Html\DataList\Paginator('iopag', $this->itab->iolist));
        $this->itab->iolist->Reload();

      //проимокоды
        $this->ptab->add(new Panel('listpan')) ;
        
        $this->ptab->listpan->add(new Form('pfilter'))->onSubmit($this,"onFilterPromo");
        $this->ptab->listpan->pfilter->add(new TextInput('psearchkey'));

        $this->ptab->listpan->add(new ClickLink('pcodeadd'))->onClick($this,"onAddPromo");

        $this->ptab->listpan->add(new DataView('plist', new PromoDataSource($this), $this, 'promolistOnRow'));
  
        $this->ptab->add(new Panel('formpan'))->setVisible(false) ;
        $this->ptab->formpan->add(new Form('pform'))->onSubmit($this,"savePCode") ;
        $this->ptab->formpan->pform->add(new ClickLink('cancelpadd'))->onClick($this,"cancelPCode") ;
        $this->ptab->formpan->pform->add(new TextInput('peditcode'));
        $this->ptab->formpan->pform->add(new Date('peditdate'));
        $this->ptab->formpan->pform->add(new TextInput('peditdisc'));
        $this->ptab->formpan->pform->add(new TextInput('peditdiscf'));
        $this->ptab->formpan->pform->add(new TextInput('peditbonus'))->setVisible(false);

        $this->ptab->formpan->pform->add(new AutocompleteTextInput('peditcust'))->onText($this, 'OnAutoCustomer');
        $this->ptab->formpan->pform->peditcust->setVisible(false);
        $this->ptab->formpan->pform->add(new CheckBox('peditcheck'));
        $this->ptab->formpan->pform->peditcheck->setVisible(false);

        $this->ptab->formpan->pform->add(new DropDownChoice('paddtype'))->onChange($this,"onPType") ;
        $this->ptab->listpan->plist->Reload();

        // сотрулники
        $form = $this->etab->add(new Form('empform'));
        $form->onSubmit($this, "onEmp");

        $form->add(new  TextInput("ebonussell", $disc["bonussell"] ??''));
        $form->add(new  TextInput("efineret", $disc["fineret"]??''));
 
        $this->add(new Form('formaddbc'))->onSubmit($this, 'onAddBonus');
        $this->formaddbc->add(new  TextInput("amountbc",''));
        $this->formaddbc->add(new AutocompleteTextInput('custbc'))->onText($this, 'OnAutoCustomer');
          
    }


    public function onCommon($sender) {
        $disc = System::getOptions("discount");
        if (!is_array($disc)) {
            $disc = array();
        }
        $disc["firstbay"] = $sender->firstbay->getText();
        $disc["bonus1"] = $sender->bonus1->getText();
        $disc["summa1"] = $sender->summa1->getText();
        $disc["bonus2"] = $sender->bonus2->getText();
        $disc["summa2"] = $sender->summa2->getText();
        $disc["bonus3"] = $sender->bonus3->getText();
        $disc["summa3"] = $sender->summa3->getText();
        $disc["bonus4"] = $sender->bonus4->getText();
        $disc["summa4"] = $sender->summa4->getText();
        System::setOptions("discount", $disc);
        $this->setSuccess('Збережено');
    }


    public function onDisc($sender) {
        $disc = System::getOptions("discount");
        if (!is_array($disc)) {
            $disc = array();
        }

        $disc["disc1"] = $sender->disc1->getText();
        $disc["discsumma1"] = $sender->discsumma1->getText();
        $disc["disc2"] = $sender->disc2->getText();
        $disc["discsumma2"] = $sender->discsumma2->getText();
        $disc["disc3"] = $sender->disc3->getText();
        $disc["discsumma3"] = $sender->discsumma3->getText();
        $disc["disc4"] = $sender->disc4->getText();
        $disc["discsumma4"] = $sender->discsumma4->getText();
        System::setOptions("discount", $disc);
        $this->setSuccess('Збережено');
    }


    public function onTab($sender) {

        $this->_tvars['tabcbadge'] = $sender->id == 'tabc' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";
        $this->_tvars['tabobadge'] = $sender->id == 'tabo' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";
        $this->_tvars['tabibadge'] = $sender->id == 'tabi' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";

        $this->_tvars['tabsbadge'] = $sender->id == 'tabs' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";
        $this->_tvars['tabpbadge'] = $sender->id == 'tabp' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";
        $this->_tvars['tabebadge'] = $sender->id == 'tabe' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";


        $this->ctab->setVisible($sender->id == 'tabc');
        $this->otab->setVisible($sender->id == 'tabo');
        $this->itab->setVisible($sender->id == 'tabi');

        $this->stab->setVisible($sender->id == 'tabs');
        $this->ptab->setVisible($sender->id == 'tabp');
        $this->etab->setVisible($sender->id == 'tabe');

    }


    //контрагенты
    public function OnCAdd($sender) {
        $c = \App\Entity\Customer::load($sender->csearchkey->getKey());
        if ($c == null) {
            return;
        }
        $d = doubleval($sender->csearchdisc->getText());
        if ($d > 0) {
            $c->discount = $d;
            $c->save();
            $this->ctab->clistform->clist->Reload();
        }
        $sender->clean();

    }

    public function customerlistOnRow($row) {
        $c = $row->getDataItem();
        $row->add(new  Label("cname", $c->customer_name));
        $row->add(new  Label("cphone", $c->phone));
        $row->add(new  TextInput("cdisc"))->setText(new  Bind($c, "discount"));
        $row->add(new  ClickLink('сdel'))->onClick($this, 'cdeleteOnClick');

    }

    public function OnCSave($sender) {
        $rows = $this->ctab->clistform->clist->getDataRows();
        foreach ($rows as $row) {
            $c = $row->getDataItem();
            if (doubleval($c->discount) > 0) {
                $c->save();
            } else {
                $c->discount = 0;
                $c->save();
            }
        }
        $this->ctab->clistform->clist->Reload();

        $this->setSuccess('Збережено');

    }

    public function cdeleteOnClick($sender) {
        $c = $sender->owner->getDataItem();
        $c->discount = 0;
        $c->save();
        $this->ctab->clistform->clist->Reload();


    }

    public function OnPBAdd($sender) {
        $c = \App\Entity\Customer::load($sender->pbsearchkey->getKey());
        if ($c == null) {
            return;
        }
        $d = $sender->pbsearchbon->getText();
        if ($d > 0) {
            $c->pbonus = $d;
            $c->save();
            $this->otab->pblist->Reload();
        }
        $sender->clean();
        

        $this->setSuccess('Збережено');
        $this->goAnkor('pbsearchkey') ;

    }   
   
    public function bcustomerlistOnRow($row) {
        $c = $row->getDataItem();
        $row->add(new  Label("pbname", $c->customer_name));
        $row->add(new  Label("pbphone", $c->phone));
        $row->add(new  Label("pbbonus", $c->pbonus));
        $row->add(new  ClickLink('pbdel'))->onClick($this, 'pbdeleteOnClick');

    }
    
    public function pbdeleteOnClick($sender) {
        $c = $sender->owner->getDataItem();
        $c->pbonus = 0;
        $c->save();
        $this->otab->pblist->Reload();
    }
   
    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 1);
    }
 
   public function onAddBonus($sender) {
        
        $am=intval($sender->amountbc->getText() );
        $cid=intval($sender->custbc->getKey() );
        $sender->amountbc->setText('') ;
        $sender->custbc->setText('') ;
        $sender->custbc->setKey(0) ;
         
        if($am != 0  && $cid >0) {
             
            $cb = new \App\Entity\CustAcc();

            $cb->customer_id = $cid;
          //  $cb->document_id = $this->document_id;
            $cb->amount =    $am;
            $cb->optype = \App\Entity\CustAcc::BONUS;
            $cb->createdon = time();
            $cb->save();
            
            $this->OnPL(null);
            if($am > 0) {
                $am = "+". $am;
            }
            $n = new \App\Entity\Notify();
            $n->user_id = \App\Entity\Notify::SYSTEM;

            $n->message = "Користувач ".System::getUser()->username." змінив ({$am}) бонуси контрагента  " .$sender->custbc->getText()  ;
            $n->save();            
            
            
        }
    }
 
     
     //список  бонусоы ц контрагентов
    public function OnPL($sender) {
      
        $this->otab->listbon->Reload();
        $conn= \ZDB\DB::getConnect()  ;
        $t = trim($this->otab->blfilter->blsearch->getText());


        $where = ""  ;
        if(strlen($t) > 0)  {
            $where .= "   customer_name like   " . Customer::qstr( '%'.$t.'%' ) .' and ' ;
        }        
        $on =  $conn->GetOne( "select sum(amount) from custacc_view  where {$where} optype=1  and  amount>0 " );
        $off = $conn->GetOne( "select sum(amount) from custacc_view  where {$where}  optype=1  and  amount<0 " );
        $this->otab->sumbonuses->setText($on +$off ); 

    }   
  
    public function bcustomerlistBOnRow($row) {
        $c = $row->getDataItem();
        $row->add(new  Label("lbname", $c->customer_name));
        $row->add(new  Label("lbphone", $c->phone));
        
        $blist=$c->getBonuses();
        
        $sum=0;
        $table="";
        foreach($blist as $b){
           $sum = $sum+ $b->bonus;   
           $table = $table . "<tr><td>".  H::fd($b->paydate)."</td>";   
           $table = $table . "<td>".  $b->document_number ."</td>";   
           $table = $table . "<td class=\"text-right\">".  $b->bonus ."</td></tr>";   
        }
        
        $row->add(new  Label("lbbonus", $sum));
        $row->add(new  Label("lbdetail", $table,true));


    }

    //услуги
    public function OnSAdd($sender) {
        $s = \App\Entity\Service::load($sender->ssearchkey->getValue());
        if ($s == null) {
            return;
        }
        $d = doubleval($sender->ssearchdisc->getText());
        if ($d > 0) {
            $s->actionprice = $d;
            $s->fromdate = $sender->ssearchfrom->getDate();
            $s->todate = $sender->ssearchto->getDate(true);
            if ($s->fromdate > $s->todate) {
                $this->setError("Невірний інтервал");
                return;
            }
            $s->save();
            $this->stab->slist->Reload();
        }

        $sender->ssearchdisc->setText("");
    }

    public function serlistOnRow($row) {
        $s = $row->getDataItem();
        $row->add(new  Label("sname", $s->service_name));
        $row->add(new  Label("sdisc"))->setText($s->actionprice);

        if ($s->fromdate < time() && $s->todate > time()) {
            $row->sdisc->setAttribute("class", "badge badge-success");
        }
        if ($s->fromdate > time()) {
            $row->sdisc->setAttribute("class", "badge badge-warning");
        }
        if ($s->todate < time()) {
            $row->sdisc->setAttribute("class", "badge badge-secondary");
        }

        $row->add(new  Label("sfrom"))->setText(H::fd($s->fromdate));
        $row->add(new  Label("sto"))->setText(H::fd($s->todate));
        $row->add(new  ClickLink('sdel'))->onClick($this, 'sdeleteOnClick');

    }

    public function sdeleteOnClick($sender) {
        $s = $sender->owner->getDataItem();
        $s->actionprice = 0;
        $s->save();
        $this->stab->slist->Reload();


    }


    //товары

    public function OnAutoItem($sender) {
        $text = trim($sender->getText());
        return Item::findArrayAC($text);
    }

    public function OnIsearchKey($sender) {
        $key = $sender->getKey();
        $it = Item::load($key) ;
        $pureprice= $it->getPurePrice();
        $this->itab->ifilter->isearchdisc->setText($pureprice) ;

    }

    public function OnIAdd($sender) {
        $k =  $this->itab->ifilter->isearchkey->getKey();
        $i = Item::load($k);
        if ($i == null) {
            $this->setError("Не вказано товар");

            return;
        }
        $d = doubleval($this->itab->ifilter->isearchdisc->getText());
        if ($d > 0) {
            $i->actionprice = $d;
            $i->actiondisc = 0;
            $i->actionqty1 = 0;
            $i->actionprice1 = 0;
            $i->fromdate = $this->itab->ifilter->isearchfrom->getDate();
            $i->todate = $this->itab->ifilter->isearchto->getDate(true);
            if ($i->fromdate > $i->todate) {
                $this->setError("Невірний інтервал");
                return;
            }
            $i->save();
            $this->itab->itform->ilist->Reload();
        }

        $this->itab->ifilter->isearchdisc->setText("");
        $this->itab->ifilter->isearchkey->setText("");
        $this->itab->ifilter->isearchkey->setKey(0);
    }

    public function OnIsearchoKey($sender) {
        $key = $sender->getKey();
        $it = Item::load($key) ;
        $pureprice= $it->getPurePrice();
        $this->itab->iofilter->isearchoprice1->setText($pureprice) ;
        $this->itab->iofilter->isearchoprice2->setText($pureprice) ;
    }

    public function OnIOAdd($sender) {
        $k = $this->itab->iofilter->isearchokey->getKey();
        $i = Item::load($k);
        if ($i == null) {
            $this->setError("Не вказано товар") ;
            return;
        }
        $d1 = doubleval($this->itab->iofilter->isearchoprice1->getText());
        $q1 = doubleval($this->itab->iofilter->isearchoqty1->getText());
        if ($d1 > 0 && $q1 > 1) {
            $i->actionprice1 = $d1;
            $i->actionqty1  = $q1;
            $i->actiondisc  = 0;
            $i->actionprice  = 0;
            $i->fromdate  = 0;
            $i->todate  = 0;
            $d2 = doubleval($this->itab->iofilter->isearchoprice2->getText());
            $q2 = doubleval($this->itab->iofilter->isearchoqty2->getText());
            if ($d2 > 0 && $q2 > 1) {
                $i->actionprice2 = $d2;
                $i->actionqty2 = $q2;
            }
            $i->save();
            $this->itab->itform->ilist->Reload();
        }

        $this->itab->iofilter->isearchoprice1->setText("");
        $this->itab->iofilter->isearchoprice2->setText("");
        $this->itab->iofilter->isearchoqty1->setText("");
        $this->itab->iofilter->isearchoqty2->setText("");
        $this->itab->iofilter->isearchokey->setText("");
        $this->itab->iofilter->isearchokey->setKey(0);

        $this->itab->iolist->Reload();
        $this->goAnkor('iofilter')  ;


    }

    public function oitemlistOnRow($row) {
        $i = $row->getDataItem();
        $row->add(new  ClickLink('odel'))->onClick($this, 'odeleteOnClick');
        $row->add(new  Label("ioname", $i->itemname));
        $row->add(new  Label("iocode", $i->item_code));
        $row->add(new  Label("ioqty1", H::fqty($i->actionqty1)));
        $row->add(new  Label("ioprice1", H::fa($i->actionprice1)));
        $row->add(new  Label("ioqty2", H::fqty($i->actionqty2)));
        $row->add(new  Label("ioprice2", H::fa($i->actionprice2)));

    }

    public function odeleteOnClick($sender) {
        $s = $sender->owner->getDataItem();
        $s->actionqty1 = 0;
        $s->save();
        $this->itab->iolist->Reload();
        $this->goAnkor('iofilter')  ;

    }

    public function itemlistOnRow($row) {
        $i = $row->getDataItem();
        $row->add(new  Label("icat_name", $i->cat_name));
        $row->add(new  Label("iname", $i->itemname));
        $row->add(new  Label("icode", $i->item_code));
        $row->add(new  Label("iprice"))->setText($i->actionprice);
        $row->iprice->setVisible($i->actionprice > 0);
        if ($i->fromdate < time() && $i->todate > time()) {
            $row->iprice->setAttribute("class", "badge badge-success");
        }
        if ($i->fromdate > time()) {
            $row->iprice->setAttribute("class", "badge badge-warning");
        }
        if ($i->todate < time()) {
            $row->iprice->setAttribute("class", "badge badge-secondary");
        }
        $row->add(new  Label("idisc"))->setText($i->actiondisc);
        $row->idisc->setVisible($i->actiondisc > 0);
        if ($i->fromdate < time() && $i->todate > time()) {
            $row->idisc->setAttribute("class", "badge badge-success");
        }
        if ($i->fromdate > time()) {
            $row->idisc->setAttribute("class", "badge badge-warning");
        }
        if ($i->todate < time()) {
            $row->idisc->setAttribute("class", "badge badge-secondary");
        }

        $row->add(new  Label("ifrom"))->setText(H::fd($i->fromdate));
        $row->add(new  Label("ito"))->setText(H::fd($i->todate));
        $row->add(new CheckBox('seldel', new \Zippy\Binding\PropertyBinding($i, 'seldel')));

    }

    public function OnDelAll($sender) {


        $ids = array();
        foreach ($this->itab->itform->ilist->getDataRows() as $row) {
            $item = $row->getDataItem();
            if ($item->seldel == true) {
                $item->actionprice = 0;
                $item->actiondisc = 0;
                $item->save();
            }
        }




        $this->itab->itform->ilist->Reload();

    }


    //категории
    public function OnGAdd($sender) {
        $g = \App\Entity\Category::load($sender->gsearchkey->getValue());
        if ($g == null) {
            $this->setError("Не вказано категорію");

            return;
        }
        $d = doubleval($sender->gsearchdisc->getText());
        if ($d > 0) {
            $g->discount = $d;
            $g->fromdate = $sender->gsearchfrom->getDate();
            $g->todate = $sender->gsearchto->getDate(true);
            if ($g->fromdate > $g->todate) {
                $this->setError("Невірний інтервал");
                return;
            }

            
            foreach (Item::findYield("disabled <> 1 and cat_id=" . $g->cat_id) as $item) {
                $item->actionprice = 0;
                $item->actiondisc = $d;
                $item->fromdate = $g->fromdate;
                $item->todate = $g->todate;
                $item->save();
            }
            $this->itab->itform->ilist->Reload();
        }

        $sender->gsearchdisc->setText("");
        $sender->gsearchkey->setValue(0);

    }


    //промо 
    public function onFilterPromo($rsender) {
       $this->ptab->listpan->plist->Reload();
    }

    public function promolistOnRow($row) {
        $p = $row->getDataItem();
 
        $row->add(new  Label("pcode", $p->code));
        $type="";
        if($p->type==1) $type="Одноразовий";
        if($p->type==2) $type="Багаторазовий";
        if($p->type==3) $type="Персональний";
        if($p->type==4) $type="Реферальний";

        $row->add(new  Label("ptype", $type));
        $disc = $p->disc.'%';
        
        if($p->type==4) {
           $disc = $p->disc . " (бонус {$p->refbonus})"  ;     
        }

        if($p->discf>0) {
           $disc = $p->discf;
        }

        $row->add(new  Label("pdisc", $disc));
        $row->add(new  Label("pused", $p->used));
        $row->add(new  Label("pcust", $p->customer_name));
        if($p->type==2){                                                                            
                                                                                       
           $q = Customer::findCnt("customer_id in (select customer_id from documents where content like '%<promocode><![CDATA[{$p->code}]]></promocode>%') ") ;
           if($q > 0) {
              $row->pcust->setText("Використали {$q}  ");    
           }
           
        }    
        
        $row->add(new  Label("pdateto", $p->enddate > 0 ? H::fd($p->enddate) :''));
        $row->add(new  ClickLink('pdel'))->onClick($this, 'pdeleteOnClick');
        
        if($p->enddate > 0 && $p->enddate < time()) {
           $p->disabled = 1;
        }
        $row->setAttribute('style', $p->disabled == 1 ? 'color: #aaa' : null);
        $row->pdel->setVisible($p->disabled == 0) ;

    }
    
    public function pdeleteOnClick($sender) {
        $p = $sender->owner->getDataItem();
        $code = PromoCode::load($p->id);

        if(strlen($code->used)==0){
            PromoCode::delete($p->id);            
        } else {
           $code->disabled=1;
           $code->save() ;   
        }

        $this->ptab->listpan->plist->Reload();

    }
   
    public function onAddPromo($sender) {
        $code=PromoCode::generate() ;
        $this->ptab->formpan->pform->clean();        
        $this->ptab->formpan->pform->peditcode->setText($code);
        $this->ptab->formpan->pform->peditcust->setText('');
        $this->ptab->formpan->pform->peditcust->setKey(0);
        $this->ptab->formpan->pform->peditcheck->setChecked(false);
        $this->ptab->formpan->pform->paddtype->setValue(0);
        
        $this->ptab->formpan->setVisible(true);
        $this->ptab->listpan->setVisible(false);
 
    }

    public function cancelPCode($sender) {
        $this->ptab->formpan->setVisible(false);
        $this->ptab->listpan->setVisible(true);
 
    }

    public function onPType($sender) {
        $t=$sender->getValue();
        $this->ptab->formpan->pform->peditcust->setVisible($t>2);
        $this->ptab->formpan->pform->peditcheck->setVisible($t==2);
        $this->ptab->formpan->pform->peditbonus->setVisible($t==4);

 
    }

    public function savePCode($sender) {
        
        $pc = new PromoCode() ;
        $pc->code = $sender->peditcode->getText() ;
        $pc->type = $sender->paddtype->getValue() ;
        if($pc->type==0) {
            $this->setError('Не вказано тип') ;
            return;
        }
        $pc->disc = doubleval($sender->peditdisc->getText() );
        $pc->discf = doubleval($sender->peditdiscf->getText() );
        $pc->refbonus = intval( $sender->peditbonus->getText() );
        $pc->enddate = $sender->peditdate->getDate();
        if($pc->enddate >0 && $pc->enddate < time()) {
           $this->setError('Неправильна дата') ;
           return; 
        }
        if($pc->disc == 0 && $pc->discf  == 0) {
           $this->setError('Не задана  знижка ') ;
           return; 
        }
        if($pc->disc >0 && $pc->discf > 0) {
           $this->setError('Вводиться або  процент або  сума') ;
           return; 
        }

  
        $pc->customer_id = (int)$sender->peditcust->getKey();
        if($pc->type >2 && $pc->customer_id ==0 ) {
           $this->setError('Не вибрано контрагента') ;
           return; 
        }
        $pc->customer_name = $sender->peditcust->getText();
        $pc->showcheck = $sender->peditcheck->isChecked() ? 1:0  ;
        
        $pc->save() ;
        
        $this->ptab->listpan->plist->Reload();
        $this->ptab->formpan->setVisible(false);
        $this->ptab->listpan->setVisible(true);
 
    }

    public function onEmp($sender) {
        $disc = System::getOptions("discount");
   

        $disc["fineret"] = $sender->efineret->getText();
        $disc["bonussell"] = $sender->ebonussell->getText();

        System::setOptions("discount", $disc);
        $this->setSuccess('Збережено');
    }
  
 
}

class DiscCustomerDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $conn = \ZDB\DB::getConnect();


        $where = "status = 0 and detail not like '%<type>2</type>%' and detail not like '%<isholding>1</isholding>%'     ";

        $where .= "   and detail   like  '%<discount>%' ";

        return $where;
    }

    public function getItemCount() {
        return Customer::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        return Customer::find($this->getWhere(), "customer_name ", $count, $start);
    }

    public function getItem($id) {

    }

}

class BonusCustomerDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $conn = \ZDB\DB::getConnect();


        $where = "status = 0 and detail not like '%<type>2</type>%' and detail not like '%<isholding>1</isholding>%'     ";

        $where .= "   and detail   like  '%<pbonus>%' ";

        return $where;
    }

    public function getItemCount() {
        return Customer::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        return Customer::find($this->getWhere(), "customer_name ", $count, $start);
    }

    public function getItem($id) {

    }

}

class BonusListCustomerDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $conn = \ZDB\DB::getConnect();

        $t = trim($this->page->otab->blfilter->blsearch->getText());

  
        $where = " status = 0  and customer_id in ( select customer_id  from custacc   where optype=1 group by customer_id having sum(amount)  <>0 ) ";
        if(strlen($t) > 0)  {
            $where .= " and customer_name like   " . Customer::qstr( '%'.$t.'%' );
        }
        return $where;
    }

    public function getItemCount() {
        return Customer::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        return Customer::find($this->getWhere(), "customer_name ", $count, $start);
    }

    public function getItem($id) {

    }

}


class DiscSerDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $conn = \ZDB\DB::getConnect();

        $where = "  disabled<>1  and    detail   like  '%<actionprice>%' ";

        return $where;
    }

    public function getItemCount() {
        return Service::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        return Service::find($this->getWhere(), "service_name ", $count, $start);
    }

    public function getItem($id) {

    }

}


class DiscItemDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $conn = \ZDB\DB::getConnect();

        $where = "  disabled<>1  and  (  detail   like  '%<actionprice>%'  or  detail   like  '%<actiondisc>%'  ) ";

        return $where;
    }

    public function getItemCount() {
        return Item::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        return Item::find($this->getWhere(), "itemname ", $count, $start);
    }

    public function getItem($id) {

    }

}

class DiscItemODataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $conn = \ZDB\DB::getConnect();

        $where = "  disabled <> 1  and  (  detail   like  '%<actionqty1>%'   ) ";

        return $where;
    }

    public function getItemCount() {
        return Item::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        return Item::find($this->getWhere(), "itemname ", $count, $start);
    }

    public function getItem($id) {

    }

}

class PromoDataSource implements \Zippy\Interfaces\DataSource
{
    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $conn = \ZDB\DB::getConnect();

        $where = "";

        $text = trim($this->page->ptab->listpan->pfilter->psearchkey->getText());
        if(strlen($text)>0) {
            $where = " code = ".$conn->qstr($text);
        }
        
        return $where;
    }

    public function getItemCount() {
        return PromoCode::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
         $where =$this->getWhere() ;
         if($where != "") {
            return PromoCode::find($where, "   id desc ", $count, $start);
         }
 
     
        $list = [];
              
        foreach(PromoCode::findYield("disabled=0  and coalesce(enddate,now()) >=now()", "   id desc ", $count, $start) as $p) {
            $list[] = $p; 
        }
        
        return $list;
    }

    public function getItem($id) {

    }

}

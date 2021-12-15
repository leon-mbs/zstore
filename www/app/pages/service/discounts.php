<?php

namespace App\Pages\Service;

use App\Entity\Customer;

use App\Entity\Category;
use App\Entity\Item;
use App\Entity\Service;
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
        $this->add(new ClickLink('tabo', $this, 'onTab'));
        $this->add(new ClickLink('tabc', $this, 'onTab'));
        $this->add(new ClickLink('tabi', $this, 'onTab'));

        $this->add(new ClickLink('tabs', $this, 'onTab'));
        $this->add(new Panel('otab'));
        $this->add(new Panel('ctab'));
        $this->add(new Panel('itab'));

        $this->add(new Panel('stab'));

        $this->onTab($this->tabo);

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
        $this->itab->gfilter->add(new DropDownChoice('gsearchkey', Category::getList(false,false), 0));
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

        $this->itab->add(new Form('ifilter'))->onSubmit($this, 'OnIAdd');
        $this->itab->ifilter->add(new AutocompleteTextInput('isearchkey'))->onText($this, 'OnAutoItem');
        $this->itab->ifilter->add(new Date('isearchfrom'))->setDate(time());
        $this->itab->ifilter->add(new Date('isearchto'))->setDate(strtotime("+7day", time()));
        $this->itab->ifilter->add(new TextInput('isearchdisc'));

        
        $this->itab->add(new Form('itform'));
        
        $this->itab->itform->add(new DataView('ilist', new DiscItemDataSource($this), $this, 'itemlistOnRow'));
        $this->itab->itform->ilist->setPageSize(H::getPG());
        $this->itab->itform->add(new \Zippy\Html\DataList\Paginator('ipag', $this->itab->itform->ilist));
        $this->itab->itform->add(new SubmitLink('deleteall'))->onClick($this, 'OnDelAll');
 
        $this->itab->itform->ilist->Reload();


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
        System::setOptions("discount", $disc);
        $this->setSuccess('saved');
    }


    public function onTab($sender) {

        $this->_tvars['tabcbadge'] = $sender->id == 'tabc' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";
        $this->_tvars['tabobadge'] = $sender->id == 'tabo' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";;
        $this->_tvars['tabibadge'] = $sender->id == 'tabi' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";;

        $this->_tvars['tabsbadge'] = $sender->id == 'tabs' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";;

        $this->ctab->setVisible($sender->id == 'tabc');
        $this->otab->setVisible($sender->id == 'tabo');
        $this->itab->setVisible($sender->id == 'tabi');

        $this->stab->setVisible($sender->id == 'tabs');

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

        $this->setSuccess('saved');

    }

    public function cdeleteOnClick($sender) {
        $c = $sender->owner->getDataItem();
        $c->discount = 0;
        $c->save();
        $this->ctab->clistform->clist->Reload();


    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 1);
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
            $s->todate = $sender->ssearchto->getDate(true);;
            if ($s->fromdate > $s->todate) {
                $this->setError("ts_invalidinterval");
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
    public function OnIAdd($sender) {
        $k = $sender->isearchkey->getKey();
        $i = Item::load($k);
        if ($i == null) {
            return;
        }
        $d = doubleval($sender->isearchdisc->getText());
        if ($d > 0) {
            $i->actionprice = $d;
            $i->actiondisc = 0;
            $i->fromdate = $sender->isearchfrom->getDate();
            $i->todate = $sender->isearchto->getDate(true);;
            if ($i->fromdate > $i->todate) {
                $this->setError("ts_invalidinterval");
                return;
            }
            $i->save();
            $this->itab->itform->ilist->Reload();
        }

        $sender->isearchdisc->setText("");
        $sender->isearchkey->setText("");
        $sender->isearchkey->setKey(0);
    }

    public function itemlistOnRow($row) {
        $i = $row->getDataItem();
        $row->add(new  Label("icat_name", $i->cat_name));
        $row->add(new  Label("iname", $i->itemname));
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
        if (false == \App\ACL::checkDelRef('ItemList')) {
            return;
        }

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
            return;
        }
        $d = doubleval($sender->gsearchdisc->getText());
        if ($d > 0) {
            $g->discount = $d;
            $g->fromdate = $sender->gsearchfrom->getDate();
            $g->todate = $sender->gsearchto->getDate(true);;
            if ($g->fromdate > $g->todate) {
                $this->setError("ts_invalidinterval");
                return;
            }

            $items = Item::find("disabled <> 1 and cat_id=" . $g->cat_id);
            foreach ($items as $item) {
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


    public function OnAutoItem($sender) {
        $text = trim($sender->getText());
        return Item::findArrayAC($text);
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


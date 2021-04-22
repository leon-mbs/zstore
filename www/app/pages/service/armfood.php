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
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * АРМ кассира общепита
 */
class ARMFood extends \App\Pages\Base
{

    private $_pos;
    private $_doc;
    public  $_itemlist   = array();
    public  $_catlist    = array();
    public  $_detaillist = array();
    public  $_doclist    = array();

    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowSer('ARMFood')) {
            return;
        }
        //обшие настройки
        $this->add(new Form('setupform'))->onSubmit($this, 'setupOnClick');

        $this->setupform->add(new DropDownChoice('pos', \App\Entity\Pos::findArray('pos_name', ''), 0));
        $this->setupform->add(new DropDownChoice('store', \App\Entity\Store::getList(), H::getDefStore()));
        $this->setupform->add(new DropDownChoice('pricetype', \App\Entity\Item::getPriceTypeList(), H::getDefPriceType()));
        $this->setupform->add(new DropDownChoice('nal', \App\Entity\MoneyFund::getList(false, false, 1), H::getDefMF()));
        $this->setupform->add(new DropDownChoice('beznal', \App\Entity\MoneyFund::getList(false, false, 2), H::getDefMF()));
        $this->setupform->add(new DropDownChoice('foodtype', array(), 1));

        //список  заказов
        $this->add(new Panel('orderlistpan'))->setVisible(false);
        $this->add(new ClickLink('neworder', $this, 'onNewOrder'));
        $this->orderlistpan->add(new DataView('orderlist', new ArrayDataSource($this, '_doclist'), $this, 'onDocRow'));

        //оформление заказа
        $this->add(new Panel('docpanel'))->setVisible(false);
        $this->docpanel->add(new ClickLink('toorderlist', $this, 'onOrderList'));

        $this->docpanel->add(new Panel('catpan'))->setVisible(false);

        $this->docpanel->catpan->add(new DataView('catlist', new ArrayDataSource($this, '_catlist'), $this, 'onCatRow'));

        $this->docpanel->add(new Panel('prodpan'))->setVisible(false);

        $this->docpanel->add(new Form('navform'));

        $this->docpanel->navform->add(new SubmitButton('btopay'))->onClick($this, 'topayOnClick');
        $this->docpanel->navform->add(new SubmitButton('baddnew'))->onClick($this, 'addnewOnClick');

        $this->docpanel->add(new Form('listsform'));
        $this->docpanel->listsform->add(new SubmitButton('bbackoptions'))->onClick($this, 'backoptionsOnClick');
        $this->docpanel->listsform->add(new SubmitButton('btopay'))->onClick($this, 'topayOnClick');

        $this->docpanel->add(new Form('payform'))->setVisible(false);
        $this->docpanel->payform->add(new SubmitButton('bbackitems'))->onClick($this, 'backoptionsOnClick');
        $this->docpanel->payform->add(new SubmitButton('btoprint'))->onClick($this, 'topayOnClick');
        $this->docpanel->payform->add(new SubmitButton('bnewcheck'))->onClick($this, 'addnewClick');

        $this->docpanel->add(new Form('delform'))->setVisible(false);
    }

    public function setupOnClick($sender) {
        $store = $this->setupform->store->getValue();
        $nal = $this->setupform->nal->getValue();
        $beznal = $this->setupform->beznal->getValue();
        $pricetype = $this->setupform->pricetype->getValue();
        $this->_pos = \App\Entity\Pos::load($this->setupform->pos->getValue());

        if ($store == 0 || $nal == 0 || $beznal == 0 || strlen($pricetype) == 0 || $this->_pos == null) {
            $this->setError(H::l("notalldata"));
            return;
        }

        $this->setupform->setVisible(false);
        $this->onOrderList($sender);
    }

    public function onNewOrder($sender) {
        $this->docpanel->setVisible(true);

        $this->orderlistpan->setVisible(false);
    }

    public function onOrderList($sender) {
        $this->docpanel->setVisible(false);
        $this->docpanel->prodpan->setVisible(false);
        $this->docpanel->catpan->setVisible(false);
        $this->docpanel->catpan->setVisible(false);
        $this->docpanel->payform->setVisible(false);
        $this->docpanel->delform->setVisible(false);

        $this->orderlistpan->setVisible(true);
        $this->updateorderlist();
    }

    public function addnewOnClick($sender) {
        $this->docpanel->catpan->setVisible(true);
        $this->_catlist = Category::find();
        $this->docpanel->catpan->catlist->Reload();
    }

    public function onDocRow($row) {
        $order = $row->getDataItem();
        $row->add(new Label('docnumber', $order->document_number));
    }

    private function updateorderlist() {
        $where = "meta_name='OrderFood' and state not in(9,15) ";
        $this->_doclist = Document::find($where, 'document_id');
        $this->orderlistpan->orderlist->Reload();
    }

    public function onCatRow($row) {
        $cat = $row->getDataItem();
        $row->add(new ClickLink('catbtn'))->onClick($this, 'onCatBtnClick');
        $row->catbtn->add(new Label('catname', $cat->cat_name));
        $row->catbtn->add(new Image('catimage', "/loadimage.php?id=" . $cat->image_id));
    }

    public function onCatBtnClick($sender) {
        $cat = $sender->getOwner()->getDataItem();
    }

    public function topayOnClick($sender) {
        $this->docpanel->setVisible(false);
        $this->payform->setVisible(true);
    }

}

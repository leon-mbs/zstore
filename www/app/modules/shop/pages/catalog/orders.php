<?php

namespace App\Modules\Shop\Pages\Catalog;

use App\Application as App;
use App\Helper as H;
use App\System;
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Modules\Shop\Entity\Product;
use Zippy\Html\Link\ClickLink;

class Orders extends Base
{
    public $_c;
    public $_order;
    public $_list = array();
    public $_dlist = array();

    public function __construct() {
        parent::__construct();

        $id = System::getCustomer();

        if ($id == 0) {
            App::Redirect("\\App\\Modules\\Shop\\Pages\\Catalog\\Userlogin");
            return;
        }
        $this->_c  = Customer::load($id);
        $this->add(new Panel("plist"));

        $this->plist->add(new DataView('list', new \Zippy\Html\DataList\ArrayDataSource($this, '_list'), $this, 'OnRow'));

        $this->add(new Panel("porder"));
        $this->porder->setVisible(false);
        $this->porder->add(new ClickLink("back", $this, "onBack"));
        $this->porder->add(new DataView('dlist', new \Zippy\Html\DataList\ArrayDataSource($this, '_dlist'), $this, 'OnDetRow'));

        $this->update();


    }

    private function update() {
        $this->_list = Document::find("customer_id={$this->_c->customer_id} and meta_name in ('Order','POSCheck','OrderFood') ", "document_id desc") ;
        $this->plist->list->Reload();
    }

    public function OnRow($row) {
        $order = $row->getDataItem();
        $order = $order->cast();
        $row->add(new Label('id', $order->document_id)) ;
        $row->add(new Label('date', H::fd($order->document_date)));
        $row->add(new Label('amount', H::fa($order->amount)));
        $row->add(new Label('status', Document::getStateName($order->state)));
        $row->add(new ClickLink('topay', $this, 'onPayment'))->setVisible($order->state==Document::STATE_WP) ;
        $row->add(new ClickLink('detail', $this, 'onOrder')) ;

    }
    public function onOrder($sender) {
        $this->_order = $sender->getOwner()->getDataItem();
        $this->_dlist = $this->_order->unpackDetails('detaildata');
        $this->porder->dlist->Reload();

        $this->plist->setVisible(false);
        $this->porder->setVisible(true);
    }
    public function onBack($sender) {

        $this->plist->setVisible(true);
        $this->porder->setVisible(false);
        $this->update();

    }
    public function OnDetRow($row) {
        $item = $row->getDataItem();
        $qty = $item->getQuantity();
        $row->add(new Label('itemname', $item->itemname)) ;
        $row->add(new Label('qty', H::fqty($item->quantity)));
        $row->add(new Label('price', H::fa($item->price)));
        $row->add(new Label('amount', H::fa($item->price*$item->quantity)));
        $row->add(new ClickLink('addcart', $this, 'onAddCart'))->setVisible($qty >0) ;


    }

    public function onAddCart($sender) {
        $item = $sender->getOwner()->getDataItem();
        $product = new Product($item->getData());

        $product->price = $product->getPrice() ;

        \App\Modules\Shop\Basket::getBasket()->addProduct($product);
        $this->setSuccess("Товар доданий до кошика");

        $this->resetURL();

    }

    public function onPayment($sender) {
        $order = $sender->getOwner()->getDataItem();

        App::Redirect("App\\Modules\\Shop\\Pages\\Catalog\\OrderPay", array($order->document_id)) ;


    }
}

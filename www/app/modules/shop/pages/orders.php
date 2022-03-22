<?php

namespace App\Modules\Shop\Pages;

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
use Zippy\Html\Link\ClickLink;

class Orders extends Base
{

    public $_c;
    public $_order;
    public  $_list = array();
 
    public function __construct() {
        parent::__construct();

        $id = System::getCustomer();

        if ($id == 0) {
            App::Redirect("\\App\\Modules\\Shop\\Pages\\Userlogin");
            return;
        }
        $this->_c  = Customer::load($id);
        $this->add(new Panel("plist"));
        
        $this->plist->add(new DataView('list', new \Zippy\Html\DataList\ArrayDataSource($this, '_list'), $this, 'OnRow'));

        $this->add(new Panel("porder"));
        
        $this->update();
 
 
    }

    private function update() {
       $this->_list = Document::find("customer_id={$this->_c->customer_id} and meta_name in ('Order','POSCheck','OrderFood') ","document_id desc") ;
       $this->list->Reload();  
    }
   
    public function OnRow($row) {
        $order = $row->getDataItem();
        $row->add(new Label('id', $order->document_id)) ;
        $row->add(new Label('date', H::fd($order->document_date)) );
        $row->add(new Label('amount', H::fa($order->amount)) );
        $row->add(new Label('status', Document::getStateName($order->state)) );
        $row->add(new ClickLink('detail', $this, 'onOrder')) ;
         
    }
    public function onOrder($sender) {
        $this->_order = $sender->getOwner()->getDataItem();
       
       
    }
}

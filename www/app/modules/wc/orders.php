<?php

namespace App\Modules\WC;

use App\Entity\Doc\Document;
use App\Entity\Item;
use App\System;
use App\Helper as H;
use Zippy\Binding\PropertyBinding as Prop;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use \App\Application as App;

class Orders extends \App\Pages\Base
{

    public $_neworders = array();
    public $_eorders   = array();

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'woocomerce') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(H::l('noaccesstopage'));

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');

        $this->add(new DataView('neworderslist', new ArrayDataSource(new Prop($this, '_neworders')), $this, 'noOnRow'));

        $this->add(new ClickLink('importbtn'))->onClick($this, 'onImport');

        $this->add(new ClickLink('refreshbtn'))->onClick($this, 'onRefresh');
        $this->add(new Form('updateform'))->onSubmit($this, 'exportOnSubmit');
        $this->updateform->add(new DataView('orderslist', new ArrayDataSource(new Prop($this, '_eorders')), $this, 'expRow'));
        $this->updateform->add(new DropDownChoice('estatus', array('completed' => 'Выполнен', 'shipped' => 'Доставлен', 'canceled' => 'Отменен'), 'completed'));
        $this->add(new ClickLink('checkconn'))->onClick($this, 'onCheck');

    }

    public function onCheck($sender) {

        Helper::connect();
        \App\Application::Redirect("\\App\\Modules\\WC\\Orders");
    }

    public function filterOnSubmit($sender) {
        $modules = System::getOptions("modules");

        $client = \App\Modules\WC\Helper::getClient();

        $this->_neworders = array();
        $page = 1;
        while(true) {


            $fields = array(
                'status' => 'pending', 'per_page' => 100, 'page' => $page
            );

            try {
                $data = $client->get('orders', $fields);
            } catch(\Exception $ee) {
                $this->setErrorTopPage($ee->getMessage());
                return;
            }
            $fields = array(
                'status' => 'on-hold', 'per_page' => 100, 'page' => $page
            );

            try {
                $data2 = $client->get('orders', $fields);
            } catch(\Exception $ee) {
                $this->setErrorTopPage($ee->getMessage());
                return;
            }
            if (is_array($data) && is_array($data2)) {
                $data = array_merge($data, $data2);
            }
            $page++;

            $c = count($data);
            if ($c == 0) {
                break;
            }
                    $conn = \ZDB\DB::getConnect();
 
            foreach ($data as $wcorder) {

                   $cnt  = $conn->getOne("select count(*) from documents_view where meta_name='Order' and content like '%<wcorder>{$wcorder->id}</wcorder>%' ")  ;

               // $isorder = Document::findCnt("meta_name='Order' and content like '%<wcorder>{$wcorder->id}</wcorder>%'");
                if (  intval($cnt) > 0) { //уже импортирован
                    continue;
                }

                $neworder = Document::create('Order');
                $neworder->document_number = $neworder->nextNumber();
                if (strlen($neworder->document_number) == 0) {
                    $neworder->document_number = 'WC00001';
                }
                

                //товары
                $j=0;
                $itlist = array();
                foreach ($wcorder->line_items as $product) {
                    //ищем по артикулу 
                    if (strlen($product->sku) == 0) {
                        continue;
                    }
                    $code = Item::qstr($product->sku);

                    $tovar = Item::getFirst('item_code=' . $code);
                    if ($tovar == null) {

                        $this->setWarn("nofoundarticle_inorder", $product->name, $wcorder->order_id);
                        continue;
                    }
                    $tovar->quantity = $product->quantity;
                    $tovar->price = \App\Helper::fa($product->price);
                    $j++;
                    $tovar->rowid = $j;

                    $itlist[$j] = $tovar;
                }
            if(count($itlist)==0)  {
                return;
            }                
                $neworder->packDetails('detaildata', $itlist);
                $neworder->headerdata['pricetype'] = 'price1';

                $neworder->headerdata['wcorder'] = $wcorder->id;
                $neworder->headerdata['outnumber'] = $wcorder->id;
                $neworder->headerdata['wcorderback'] = 0;
                $neworder->headerdata['salesource'] = $modules['wcsalesource'];
                $neworder->headerdata['wcclient'] = $wcorder->shipping->first_name . ' ' . $wcorder->shipping->last_name;
                $neworder->amount = H::fa($wcorder->total);
                $neworder->payamount = $neworder->amount;
                
               
                $neworder->document_date = time();
                $neworder->notes = "WC номер:{$wcorder->id};";
                $neworder->notes .= " Клієнт:" . $wcorder->shipping->first_name . ' ' . $wcorder->shipping->last_name . ";";
                if (strlen($wcorder->billing->email) > 0) {
                    $neworder->notes .= " Email:" . $wcorder->billing->email . ";";
                }
                if (strlen($wcorder->billing->phone) > 0) {
                    $neworder->notes .= " Тел:" . $wcorder->billing->phone . ";";
                }
                $neworder->notes .= " Адреса:" . $wcorder->shipping->city . ' ' . $wcorder->shipping->address_1 . ";";
                $neworder->notes .= " Комментар:" . $wcorder->customer_note . ";";

                $this->_neworders[$wcorder->id] = $neworder;
            }
        }
        $this->neworderslist->Reload();
    }

    public function noOnRow($row) {
        $order = $row->getDataItem();

        $row->add(new Label('number', $order->headerdata['wcorder']));
        $row->add(new Label('customer', $order->headerdata['wcclient']));
        $row->add(new Label('amount', H::fa($order->amount)));
        $row->add(new Label('comment', $order->notes));
        $row->add(new Label('date', \App\Helper::fdt(strtotime($order->document_date))));
    }

    public function onImport($sender) {
        $modules = System::getOptions("modules");

        foreach ($this->_neworders as $shoporder) {

            $shoporder->save();
            $shoporder->updateStatus(Document::STATE_NEW);
            $shoporder->updateStatus(Document::STATE_INPROCESS);
            if($modules['wcsetpayamount']==1){
                $shoporder->updateStatus(Document::STATE_WP);
            }              
            
        }

        $this->setInfo('imported_orders', count($this->_neworders));

        $this->_neworders = array();
        $this->neworderslist->Reload();
    }

    public function onRefresh($sender) {

        $this->_eorders = Document::find("meta_name='Order' and content like '%<wcorderback>0</wcorderback>%' and state <> " . Document::STATE_NEW);
        $this->updateform->orderslist->Reload();
    }

    public function expRow($row) {
        $order = $row->getDataItem();
        $row->add(new CheckBox('ch', new Prop($order, 'ch')));
        $row->add(new Label('number2', $order->document_number));
        $row->add(new Label('number3', $order->headerdata['wcorder']));
        $row->add(new Label('date2', \App\Helper::fdt($order->document_date)));
        $row->add(new Label('amount2', $order->amount));
        $row->add(new Label('customer2', $order->headerdata['wcclient']));
        $row->add(new Label('state', Document::getStateName($order->state)));
    }

    public function exportOnSubmit($sender) {
        $modules = System::getOptions("modules");
        $st = $this->updateform->estatus->getValue();

        $client = \App\Modules\WC\Helper::getClient();

        $elist = array();
        foreach ($this->_eorders as $order) {
            if ($order->ch == false) {
                continue;
            }
            $elist[] = $order;
        }
        if (count($elist) == 0) {
            $this->setError('noselorder');
            return;
        }

        $fields = array(
            'status' => $st
        );

        foreach ($elist as $order) {


            try {
                $data = $client->put('orders/' . $order->headerdata['wcorder'], $fields);
            } catch(\Exception $ee) {
                $this->setErrorTopPage($ee->getMessage());
                return;
            }

            $order->headerdata['wcorderback'] = 1;
            $order->save();
        }


        $this->setSuccess("refrehed_orders", count($elist));

        $this->_eorders = Document::find("meta_name='Order' and content like '%<wcorderback>0</wcorderback>%' and state <> " . Document::STATE_NEW);
        $this->updateform->orderslist->Reload();
    }

}

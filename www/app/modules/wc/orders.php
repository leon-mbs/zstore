<?php

namespace App\Modules\WC;

use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Customer;
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
use App\Application as App;

class Orders extends \App\Pages\Base
{
    public $_neworders = array();
    public $_eorders   = array();

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'woocomerce') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");

        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('eistatus', array('pending' => 'В очікуванні', 'processing' => 'В обробці','on-hold'=>'На  утриманні' ), 'pending'));

        $this->add(new DataView('neworderslist', new ArrayDataSource(new Prop($this, '_neworders')), $this, 'noOnRow'));

        $this->add(new ClickLink('importbtn'))->onClick($this, 'onImport');

        $this->add(new ClickLink('refreshbtn'))->onClick($this, 'onRefresh');
        $this->add(new Form('updateform'))->onSubmit($this, 'exportOnSubmit');
        $this->updateform->add(new DataView('orderslist', new ArrayDataSource(new Prop($this, '_eorders')), $this, 'expRow'));
        $this->updateform->add(new DropDownChoice('estatus', array('completed' => 'Виконаний', 'shipped' => 'Доставлений', 'cancelled' => 'Скасований'), 'completed'));
        $this->add(new ClickLink('checkconn'))->onClick($this, 'onCheck');
          
    }

    public function onCheck($sender) {

        Helper::connect();
        \App\Application::Redirect("\\App\\Modules\\WC\\Orders");
    }

    public function filterOnSubmit($sender) {
        $modules = System::getOptions("modules");

        $client = \App\Modules\WC\Helper::getClient();
        $st = $this->filter->eistatus->getValue();
        $this->_neworders = array();
        $page = 1;
        while(true) {


            $fields = array(
                'status' => $st, 'per_page' => 100, 'page' => $page
            );

            try {
                $data = $client->get('orders', $fields);
            } catch(\Exception $ee) {
                $this->setErrorTopPage($ee->getMessage());
                return;
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
                if (intval($cnt) > 0) { //уже импортирован
                    continue;
                }

                $neworder = Document::create('Order');
           


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

                        $this->setWarn("Не знайдено артикул товара {$product->name} в замовленні номер " .  $wcorder->order_id);
                        continue;
                    }
                    $tovar->quantity = $product->quantity;
                    $tovar->price = \App\Helper::fa($product->price);
                    $j++;
                    $tovar->rowid = $j;

                    $itlist[$j] = $tovar;
                }
                if(count($itlist)==0) {
                    return;
                }
                $neworder->packDetails('detaildata', $itlist);
                $neworder->headerdata['pricetype'] = 'price1';

                $neworder->headerdata['wcorder'] = $wcorder->id;
                $neworder->headerdata['outnumber'] = $wcorder->id;
                $neworder->headerdata['wcorderback'] = 0;
                $neworder->headerdata['salesource'] = $modules['wcsalesource'];
                $neworder->headerdata['phone'] = strlen($wcorder->billing->phone ??'') > 0 ? $wcorder->billing->phone :  ($wcorder->billing->phone ??'')   ;
                $neworder->headerdata['wcclient'] = trim($wcorder->shipping->last_name . ' ' . $wcorder->shipping->first_name);
                $neworder->amount = H::fa($wcorder->total);
                $neworder->payamount = $neworder->amount;

                if($modules['wcmf']>0) {
                  $neworder->headerdata['payment'] = $modules['wcmf'];
                }


                $neworder->document_date = time();
                $neworder->notes = "WC номер:{$wcorder->id};";
                $neworder->notes .= " Клієнт: " . trim($wcorder->shipping->last_name . ' ' . $wcorder->shipping->first_name).";";
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

            $shoporder->document_number = $shoporder->nextNumber();
            if (strlen($shoporder->document_number) == 0) {
                $shoporder->document_number = 'WC00001';
            }            


           if($modules['wcinsertcust']==1  && strlen($shoporder->headerdata['phone'] ?? '' )>0) {
                  $phone=\App\Util::handlePhone($shoporder->headerdata['phone']);
                  $cust = Customer::getByPhone($phone) ;
                  if ($cust == null) {
                        $cust = new Customer();
                        $cust->customer_name = trim($shoporder->headerdata['wcclient']);
                        $cust->type = Customer::TYPE_BAYER;
                        $cust->phone = $phone;
                        $cust->comment = "Клiєнт WC";
                        $cust->save();
                  }
                
                if ($cust != null) {
                    $shoporder->customer_id = $cust->customer_id  ;
                }                
                
            }

            
            $shoporder->save();
            $shoporder->updateStatus(Document::STATE_NEW);
            $shoporder->updateStatus(Document::STATE_INPROCESS);
            if($modules['wcsetpayamount']==1) {
                $shoporder->updateStatus(Document::STATE_WP);
            }
 

        }

        $this->setInfo("Імпортовано ".count($this->_neworders)." замовлень");


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
            $this->setError('Не обрано ордер');
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

        $this->setSuccess("Оновлено ".count($elist)." замовлень");


        $this->_eorders = Document::find("meta_name='Order' and content like '%<wcorderback>0</wcorderback>%' and state <> " . Document::STATE_NEW);
        $this->updateform->orderslist->Reload();
    }

}

/*
$order_statuses = array(
    'wc-pending'    => _x( 'Pending payment', 'Order status', 'woocommerce' ),
    'wc-processing' => _x( 'Processing', 'Order status', 'woocommerce' ),
    'wc-on-hold'    => _x( 'On hold', 'Order status', 'woocommerce' ),
    'wc-completed'  => _x( 'Completed', 'Order status', 'woocommerce' ),
    'wc-cancelled'  => _x( 'Cancelled', 'Order status', 'woocommerce' ),
    'wc-refunded'   => _x( 'Refunded', 'Order status', 'woocommerce' ),
    'wc-failed'     => _x( 'Failed', 'Order status', 'woocommerce' ),
);

*/
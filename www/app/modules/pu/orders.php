<?php

namespace App\Modules\PU;

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
use Zippy\WebApplication as App;

class Orders extends \App\Pages\Base
{

    public $_neworders = array();
    public $_eorders   = array();

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'promua') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(H::l('noaccesstopage'));

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");

        $statuses = Helper::connect() ;
        
        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('status', $statuses,'pending'));

        $this->add(new DataView('neworderslist', new ArrayDataSource(new Prop($this, '_neworders')), $this, 'noOnRow'));

        $this->add(new ClickLink('importbtn'))->onClick($this, 'onImport');

        $this->add(new ClickLink('refreshbtn'))->onClick($this, 'onRefresh');
        $this->add(new Form('updateform'))->onSubmit($this, 'exportOnSubmit');
        $this->updateform->add(new DataView('orderslist', new ArrayDataSource(new Prop($this, '_eorders')), $this, 'expRow'));
        $this->updateform->add(new DropDownChoice('estatus',  $statuses, 'delivered'));


    }

    
    public function filterOnSubmit($sender) {
        $modules = System::getOptions("modules");

        $client = \App\Modules\WC\Helper::getClient();

        $this->_neworders = array();
       
 
            try {
               $data = Helper::make_request("GET","/api/v1/orders/list?status=". $this->filter->status->getValue(),null);
 
            } catch(\Exception $ee) {
                $this->setErrorTopPage($ee->getMessage());
                return;
            }
 
       
           $conn = \ZDB\DB::getConnect();
 
            foreach ($data['orders'] as $puorder) {

               $cnt  = $conn->getOne("select count(*) from documents_view where meta_name='Order' and content like '%<puorder>{$puorder['id']}</wcorder>%' ")  ;

               // $isorder = Document::findCnt("meta_name='Order' and content like '%<wcorder>{$puorder->id}</wcorder>%'");
                if (  intval($cnt) > 0) { //уже импортирован
                    continue;
                }

                $neworder = Document::create('Order');
                $neworder->document_number = $neworder->nextNumber();
                if (strlen($neworder->document_number) == 0) {
                    $neworder->document_number = 'PU00001';
                }
                $neworder->customer_id = $modules['pucustomer_id'];

                //товары
                $j=0;
                $itlist = array();
                foreach ($puorder['products'] as $product) {
                    //ищем по артикулу 
                    if (strlen($product['sku']) == 0) {
                        continue;
                    }
                    $code = Item::qstr($product['sku']);

                    $tovar = Item::getFirst('item_code=' . $code);
                    if ($tovar == null) {

                        $this->setWarn("nofoundarticle_inorder", $product['name'], $puorder['order_id']);
                        continue;
                    }
                    $tovar->quantity = H::fqty($product['quantity']);
                    $tovar->price = H::fa($product['price']);
                    $j++;
                    $tovar->rowid = $j;

                    $itlist[$j] = $tovar;
                }
           
            if(count($itlist)==0)  {
                 return;
            }                
                $neworder->packDetails('detaildata', $itlist);
                $neworder->headerdata['pricetype'] = 'price1';

                $neworder->headerdata['puorder'] = $puorder['id'];
                $neworder->headerdata['outnumber'] = $puorder['id'];
                $neworder->headerdata['puorderback'] = 0;
                $neworder->headerdata['puclient'] = $puorder['client_first_name'] . ' ' . $puorder['client_last_name'];
                
                $neworder->amount = H::fa($puorder['full_price']);
                if($modules['pusetpayamount']==1){
                   $neworder->payamount = $neworder->amount;
                 
                }              
                
               
                $neworder->document_date = time();
                $neworder->notes = "PU номер:{$puorder['id']};";
                $neworder->notes .= " Клиент:" .$puorder['client_first_name'] . ' ' . $puorder['client_last_name'].';';
                if (strlen($puorder['email']) > 0) {
                    $neworder->notes .= " Email:" . $puorder['email'] . ";";
                }
                if (strlen($puorder['phone']) > 0) {
                    $neworder->notes .= " Тел:" . str_replace('+','',$puorder['phone'] ). ";";
                }
                if (strlen($puorder['delivery_option']['name']) > 0) {
                    $neworder->notes .= " Доставка:" . $puorder['delivery_option']['name'] . ";";
                }

                if (strlen($puorder['delivery_address']) > 0) {
                    $neworder->notes .= " Адрес:" . $puorder['delivery_address'] . ";";
                }
                if (strlen($puorder['client_notes']) > 0) {
                    $neworder->notes .= " Комментарий:" . $puorder['client_notes'] . ";";
                }

           
                $this->_neworders[$puorder['id']] = $neworder;
            }
        
        $this->neworderslist->Reload();
    }

    public function noOnRow($row) {
        $order = $row->getDataItem();

        $row->add(new Label('number', $order->headerdata['wcorder']));
        $row->add(new Label('customer', $order->headerdata['wcclient']));
        $row->add(new Label('amount', round($order->amount)));
        $row->add(new Label('comment', $order->notes));
        $row->add(new Label('date', \App\Helper::fdt(strtotime($order->document_date))));
    }

    public function onImport($sender) {
        $modules = System::getOptions("modules");

        foreach ($this->_neworders as $shoporder) {

            $shoporder->save();
            $shoporder->updateStatus(Document::STATE_NEW);
        }

        $this->setInfo('imported_orders', count($this->_neworders));

        $this->_neworders = array();
        $this->neworderslist->Reload();
    }

    public function onRefresh($sender) {

        $this->_eorders = Document::find("meta_name='Order' and content like '%<puorderback>0</puorderback>%' and state <> " . Document::STATE_NEW);
        $this->updateform->orderslist->Reload();
    }

    public function expRow($row) {
        $order = $row->getDataItem();
        $row->add(new CheckBox('ch', new Prop($order, 'ch')));
        $row->add(new Label('number2', $order->document_number));
        $row->add(new Label('number3', $order->headerdata['puorder']));
        $row->add(new Label('date2', \App\Helper::fdt($order->document_date)));
        $row->add(new Label('amount2', $order->amount));
        $row->add(new Label('customer2', $order->headerdata['puclient']));
        $row->add(new Label('state', Document::getStateName($order->state)));
    }

    public function exportOnSubmit($sender) {
        $modules = System::getOptions("modules");
        $st = $this->updateform->estatus->getValue();

       
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
                $json="{
                \"status\":\"pending\",
                \"ids\":[{$order->headerdata['ocorder']}]
                }" ;
                Helper::make_request("POST","/api/v1/orders/set_status",$json);
            } catch(\Exception $ee) {
                $this->setErrorTopPage($ee->getMessage());
                return;
            }

            $order->headerdata['puorderback'] = 1;
            $order->save();
        }


        $this->setSuccess("refrehed_orders", count($elist));

        $this->_eorders = Document::find("meta_name='Order' and content like '%<puorderback>0</puorderback>%' and state <> " . Document::STATE_NEW);
        $this->updateform->orderslist->Reload();
    }

}

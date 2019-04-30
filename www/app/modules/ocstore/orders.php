<?php

namespace App\Modules\OCStore;

use \App\System;
use \Zippy\Binding\PropertyBinding as Prop;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\WebApplication as App;

class Orders extends \App\Pages\Base
{

    public $_neworders = array();

    public function __construct()
    {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'ocstore') === false && System::getUser()->userlogin != 'admin') {
            System::setErrorMsg('Нет права доступа к странице');

            App::RedirectHome();
            return;
        }

        $modules = System::getOptions("modules");
        $statuses = System::getSession()->statuses;
        if (is_array($statuses) == false) {
            $statuses = array();
            $this->setWarn('Выполните соединение на странице настроек');
        }
        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new DropDownChoice('status', $statuses, 0));

        $this->add(new DataView('neworderslist', new ArrayDataSource(new Prop($this, '_neworders')), $this, 'noOnRow'));

        $this->add(new Form('importform'))->onSubmit($this, 'importOnSubmit');

    }

    public function filterOnSubmit($sender)
    {

        $status = $this->filter->status->getValue();

        $this->_neworders = array();
        $fields = array(
            'status_id' => $status,
        );
        $url = $site . '/index.php?route=api/zstore/orders&' . System::getSession()->octoken;
        $json = Helper::do_curl_request($url, $fields);
        $data = json_decode($json);
        if ($data->error == "") {

            foreach ($data->orders as $o) {
                $this->_neworders[$o->order_id] = unserialize(base64_decode($o->order));
            }

            $this->neworderslist->Reload();
        } else {
            $this->setError($data->error);
        }
    }

    public function doclistOnRow($row)
    {
        $order = $row->getDataItem();

        //$row->add(new Label('number', $order->document_number));
        //$row->add(new Label('customer', $order->document_number));
        //$row->add(new Label('amount', $order->document_number));
        //$row->add(new Label('date', $order->document_number));
    }
    public function importOnSubmit($sender)
    {
        $orders = $this->neworderslist->getDataSource()->getItems();

        foreach ($orders as $shoporder) {

            $isorder = \App\Entity\Doc\Document::findCnt("meta_name='Order' and content like <ocorder>{$shoporder->order_id}</ocorder>");
            if ($isorder > 0) { //уже импортирован
                continue;
            }

            $neworder = new \App\Entity\Doc\Order();

        }

        $this->_neworders = array();
        $this->neworderslist->Reload();
    }
}

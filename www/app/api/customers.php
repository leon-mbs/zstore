<?php

namespace App\API;

use App\Entity\Customer;
use App\Helper as H;

class customers extends JsonRPC
{
    //список  контрагентов
    public function list() {
        $list = array();

        foreach (Customer::findYield('', 'customer_name') as $cust) {

            $c = array(
                'customer_id'   => $cust->customer_id,
                'customer_name' => $cust->customer_name,
                'phone'         => $cust->phone,
                'email'         => $cust->email,
                'city'          => $cust->city,
                'type'          => $cust->type,
                'country'       => $cust->country,
                'address'       => $cust->address,
                'description'   => base64_encode($cust->comment)
            );
           
            $list[] = $c;
        }

        return $list;
    }

    public function save($args) {
        $cust = null;
        if ($args['customer_id'] > 0) {
            $cust = Customer::load($args['customer_id'] > 0);
        }
        if ($cust == null) {
            $cust = new Customer();
        }
        $cust->customer_name = $args['customer_name'];
        $cust->phone = $args['phone'];
        $cust->email = $args['email'];
        $cust->city = $args['city'];
        $cust->type = $args['type'];
        $cust->address = $args['address'];
        $cust->comment = base64_encode($args['description']);

        if (strlen($cust->customer_name) == 0) {
            throw new \Exception("Не задано назву контрагента");
        }

        $cust->save();
        return array('customer_id' => $cust->customer_id);
    }

}

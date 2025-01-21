<?php

namespace App\API;

use App\Helper as H;


class service extends JsonRPC
{
 
    public function list() {
        $list = array();

        foreach (\App\Entity\Service::findYield('', 'service_name') as $ser) {

            $c = array(
                'service_id'   => $ser->service_id,
                'service_name' => $ser->service_name ,
                'price'        => $ser->price,
                'category' => $ser->category
          
            );

            $list[] = $c;
        }

        return $list;
    }

}

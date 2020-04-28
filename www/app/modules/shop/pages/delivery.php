<?php

namespace App\Modules\Shop\Pages;

class Delivery extends Base
{

    public function __construct() {
        parent::__construct();

        $shop = \App\System::getOptions("shop");

        $this->_tvars['delivery'] = base64_decode($shop['delivery']);
    }

}

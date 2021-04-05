<?php

namespace App\Modules\Shop\Pages;

class News extends Base
{

    public function __construct() {
        parent::__construct();

        $shop = \App\System::getOptions("shop");

        $this->_tvars['news'] = base64_decode($shop['news']);
    }

}

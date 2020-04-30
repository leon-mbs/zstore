<?php

namespace App\Modules\Shop\Pages;

class AboutUs extends Base
{

    public function __construct() {
        parent::__construct();

        $shop = \App\System::getOptions("shop");
        $this->_tvars['aboutus'] = base64_decode($shop['aboutus']);
    }

}

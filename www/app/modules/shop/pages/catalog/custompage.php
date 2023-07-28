<?php

namespace App\Modules\Shop\Pages\Catalog;

class CustomPage extends Base
{
    public function __construct($pageuri) {
        parent::__construct();

        $shop = \App\System::getOptions("shop");
        $pages = $shop['pages'] ;
        if(!is_array($pages)) {
            $pages = array();
        }

        $this->_title =  $pages[$pageuri]->title;
        $this->_tvars['content'] = base64_decode($pages[$pageuri]->text);
    }

}

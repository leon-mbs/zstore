<?php

namespace App\Pages;

/**
 * Главная страница
 */
class Main extends Base
{

    public function __construct() {
        parent::__construct();


        
        $this->add(new \App\Widgets\WDebitors("wdebitors"));

        
        $this->add(new \App\Widgets\WMinQty("wminqty"));

        $this->add(new \App\Widgets\WSdate("wsdate"))->setVisible($this->_tvars["usesnumber"]);

        
        $this->add(new \App\Widgets\WRDoc("wrdoc"));

        

    }

    /*
    public function test($args,$post) {
        
      return "test"; 
    }  */
}

<?php

namespace App\Pages;

/**
 * Главная страница
 */
class Main extends Base
{

    public function __construct() {
        parent::__construct();



        
        $this->add(new \App\Widgets\WPlannedDocs("wplanned"));
        
        $this->add(new \App\Widgets\WDebitors("wdebitors"));
    }

    public function getPageInfo() {
        return "Статистика на  начало дня";
    }

}

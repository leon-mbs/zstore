<?php

namespace App\Pages;

class Error extends \App\Pages\Base
{

    public function __construct($error = '') {
        parent::__construct();

        $this->add(new \Zippy\Html\Label('msg', $error));
    }

}

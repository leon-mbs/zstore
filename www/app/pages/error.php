<?php

namespace App\Pages;

class Error extends \App\Pages\Base
{

    public function __construct($error = '') {
        parent::__construct();

        $this->add(new \Zippy\Html\Panel('errpan'))->setVisible(strlen($error) > 0);
        $this->errpan->add(new \Zippy\Html\Label('errmsg', $error));
    }

}

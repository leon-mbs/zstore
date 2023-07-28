<?php

namespace App;

/**
 * Класс  для  исправления  косяка
 */
class Thumb extends \PHPThumb\GD
{
    public function __construct($fileName, $options = array()) {
        $this->options = array();
        parent::__construct($fileName, $options);
    }

}

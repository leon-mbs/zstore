<?php

namespace App\Pages\Register;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

/**
 * журнал  доставок
 */
class DeliveryList extends \App\Pages\Base
{

    private $_doc = null;
 
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReg('DeliveryList')) {
            return;
        }
    }
}
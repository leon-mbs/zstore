<?php
  
namespace App\Pages;

use App\Entity\Firm;
use App\Helper as H;
use Zippy\Html\DataList\DataView;

use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\System;

class Subscribes extends \App\Pages\Base
{

    private $_sub;

    public function __construct() {
        parent::__construct();
     
        if (System::getUser()->rolename != 'admins') {
            System::setErrorMsg(H::l('onlyadminsaccess'));
            \App\Application::RedirectError();
            return false;
        }
        
    }
}

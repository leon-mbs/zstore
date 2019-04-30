<?php
  

namespace App\Modules\OCStore;

use \App\System;
use \Zippy\Binding\PropertyBinding as Prop;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\WebApplication as App;

class Items extends \App\Pages\Base
{
    public function __construct()
    {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'ocstore') === false && System::getUser()->userlogin != 'admin') {
            System::setErrorMsg('Нет права доступа к странице');

            App::RedirectHome();
            return;
        }
        
        
        
        
    }   
    
}

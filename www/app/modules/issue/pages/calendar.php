<?php
namespace App\Modules\Issue\Pages;

use \ZCL\DB\EntityDataSource as EDS;
use \Zippy\Binding\PropertyBinding as Prop;
use \Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\AutocompleteTextInput;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\File;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
 
use \App\Entity\Employee;
 
use \App\Entity\Doc\Document;
use \App\Helper as H;
use \App\System;
use \App\Application as App;


  
class Calendar extends \App\Pages\Base {
     public function __construct() {
        parent::__construct();
        


       $this->add(new \App\Calendar('calendar'))->setEvent($this, 'OnGal');

        
     }
    
}

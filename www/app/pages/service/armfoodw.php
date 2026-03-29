<?php

namespace App\Pages\Service;

use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Category;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Image;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use Zippy\Html\Link\BookmarkableLink;

/**
 * АРМ официанта  кафе
 */
class ARMFoodW extends \App\Pages\Base
{
    private $_pricetype;
    private $_worktype = 0;
    private $_pos;
    private $_store;
    public  $_pt       = -1;  //тип оплаты
    public  $_ct       = -1;  //тип чека


    private $_doc;
    public $_itemlist = [];
    public $_catlist  = [];
    public $_prodlist = [];
    public $_doclist  = [];
    
    public $_prodvarlist  = [];
    public $_vblist  = [];
    public $_vbdetlist  = [];
    private $_vbitem;
    
    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowSer('ARMFoodW')) {
            return;
        }
        $food = System::getOptions("food");
        if (!is_array($food)) {
            $food = array();
            $this->setWarn('Не вказано параметри в  налаштуваннях');
        }
        $this->_worktype = intval( $food['worktype'] );

        $this->_tvars['delivery'] = $food['delivery'] ?? 0;
        $this->_tvars['tables'] = $food['tables'] ?? 0;
        $this->_tvars['packicon'] = $food['pack'] ?? 0;
        $this->_tvars['diffbp'] = $food['diffbp'] ?? 0;
        $this->_tvars['baricon'] = $this->_worktype > 0  ;
       
        if($this->_worktype==0) {
           $this->_tvars['diffbp'] = 0;
        }
        if($this->_tvars['diffbp'] == 1) {
           $this->_tvars['baricon'] =0; 
        }

      
               

        
    }
        
}

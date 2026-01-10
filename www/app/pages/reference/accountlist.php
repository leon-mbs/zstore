<?php

namespace App\Pages\Reference;

use App\Entity\Account;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\Helper as H;

class AccountList extends \App\Pages\Base
{
    public $_acc = null;
   

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('AccountList')) {
            return;
        }
        
        
      
        $this->_tvars['acclist'] = [];
        
        foreach(Account::getList() as $c=>$n){
           $this->_tvars['acclist'][] = array('acc_code'=>$c,'acc_name'=>$n);  
        }
    }

 

  

  
 

  
}

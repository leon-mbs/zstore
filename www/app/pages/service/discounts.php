<?php

namespace App\Pages\Service;

use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Service;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Panel;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Скидки и акции
 */
class Discounts extends \App\Pages\Base
{    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowSer('Discounts')) {
            return;
        }
        $this->add(new ClickLink('tabo', $this, 'onTab'));
        $this->add(new ClickLink('tabc', $this, 'onTab'));
        $this->add(new ClickLink('tabi', $this, 'onTab'));
        $this->add(new ClickLink('tabg', $this, 'onTab'));
        $this->add(new ClickLink('tabs', $this, 'onTab'));
        $this->add(new Panel('otab'));
        $this->add(new Panel('ctab'));
        $this->add(new Panel('itab'));
        $this->add(new Panel('gtab'));
        $this->add(new Panel('stab'));

        $this->onTab($this->tabo);

        $disc = System::getOptions("discount");
        if (!is_array($disc)) {
            $disc = array( );
        }
      
        $form = $this->otab->add(new  Form("commonform")) ;
        $form->onSubmit($this,"onCommon") ;
        $form->add(new  TextInput("firstbay",$disc["firstbay"])) ;
        $form->add(new  TextInput("bonus1",$disc["bonus1"])) ;
        $form->add(new  TextInput("level2",$disc["level2"])) ;
        $form->add(new  TextInput("bonus2",$disc["bonus2"])) ;
        
        
     }
     
     
   public function onCommon($sender) {
        $disc = System::getOptions("discount");
        if (!is_array($disc)) {
            $disc = array( );
        }
        $disc["firstbay"] =  $sender->firstbay->getText();
        $disc["bonus1"] =  $sender->bonus1->getText();
        $disc["level2"] =  $sender->level2->getText();
        $disc["bonus2"] =  $sender->bonus2->getText();
        System::setOptions("discount",$disc) ;
        
   }
   
   public function onTab($sender) {

        $this->_tvars['tabcbadge'] = $sender->id == 'tabc' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";
        $this->_tvars['tabobadge'] = $sender->id == 'tabo' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";;
        $this->_tvars['tabibadge'] = $sender->id == 'tabi' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";;
        $this->_tvars['tabgbadge'] = $sender->id == 'tabg' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";;
        $this->_tvars['tabsbadge'] = $sender->id == 'tabs' ? "badge badge-dark  badge-pill " : "badge badge-light  badge-pill  ";;

        $this->ctab->setVisible($sender->id == 'tabc');
        $this->otab->setVisible($sender->id == 'tabo');
        $this->itab->setVisible($sender->id == 'tabi');
        $this->gtab->setVisible($sender->id == 'tabg');
        $this->stab->setVisible($sender->id == 'tabs');
        
    }
     
}
<?php

namespace App\Pages\Service;

use App\Entity\Customer;
 
use App\Entity\Category;
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
        
        //покупатели
        $this->ctab->add(new Form('cfilter'))->onSubmit($this, 'OnCSearch');
        $this->ctab->cfilter->add(new TextInput('csearchkey'));
      
        $this->ctab->add(new  Panel("clistpan"));
           
        $this->ctab->clistpan->add(new DataView('clist', new DiscCustomerDataSource($this), $this, 'customerlistOnRow'));
        $this->ctab->clistpan->clist->setPageSize(H::getPG());
        $this->ctab->clistpan->add(new \Zippy\Html\DataList\Paginator('cpag', $this->ctab->clistpan->clist));

        $this->ctab->clistpan->clist->Reload();
       
        
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
  
   public function OnCSearch($sender) { 
      $this->ctab->clistpan->clist->Reload();
     
   } 
   public function customerlistOnRow($row) {  
       $c = $row->getDataItem();   
       $row->add(new  Label("cname",$c->customer_name)) ;
       $row->add(new  Label("cphone",$c->phone)) ;
       $row->add(new  Label("cbonus",$c->bonus == "0" ? "":$c->bonus)) ;
       $row->add(new  Label("cdisc",$c->discount == "0" ? "":$c->discount)) ;
       
   }
     
}

class DiscCustomerDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {
 
        $conn = \ZDB\DB::getConnect();

        $search = trim($this->page->ctab->cfilter->csearchkey->getText());
        $where = "status = 0 and detail not like '%<type>2</type>%' and detail not like '%<isholding>1</isholding>%'     ";
     
        if (strlen($search) > 0) {
            $search = Customer::qstr('%' . $search . '%');
            $where .= " and (customer_name like  {$search} or phone like {$search} or email like {$search}    )";
        }     
        
        return $where;
    }

    public function getItemCount() {
        return Customer::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {

        return Customer::find($this->getWhere(), $sortfield . " " . $asc, $count, $start, "*, coalesce(  (select  count(*) from  documents where  documents.customer_id= customers_view.customer_id and documents.state>3 ),0)  as bonus");
    }

    public function getItem($id) {

    }

}
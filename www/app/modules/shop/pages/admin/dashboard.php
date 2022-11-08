<?php

namespace App\Modules\Shop\Pages\Admin;

use App\Application as App;
use App\Entity\Item;
use App\Modules\Shop\Entity\Product;
use App\System;
use App\Helper as H;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\File;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Panel;
use Zippy\Html\Label;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;

class Dashboard extends \App\Pages\Base
{
    public $_pages = array();
   
    public function __construct() {
        parent::__construct();
        if (strpos(System::getUser()->modules, 'shop') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg('noaccesstopage');
            App::RedirectError();
            return;
        }

 
        
        $shop = System::getOptions("shop");
        if (!is_array($shop)) {
            $shop = array();
        }

        $this->updateGraph();
    }

   
    public function updateGraph() {
       
        $conn =   \ZDB\DB::getConnect();
  
        $grvisdays = array();
        $grvisdays_ = array();
        $grviscnt = array();
  
        $d = new \App\DateTime() ;
        for($i=0;$i<31;$i++) {
           $grvisdays_[]  = $d->getTimestamp() ;
           $d->subDay(1) ;
                         
        }
  
        $grvisdays_ = array_reverse($grvisdays_) ;
        foreach($grvisdays_ as $dd)  {
           $grvisdays[]=  date('m-d',$dd);

           $cnt = $conn->GetOne("select count(*) from stats where category = " . H::STAT_HIT_SHOP . " and date(dt)=". $conn->DBDate($dd) );
           $grviscnt[]= intval($cnt);     
        }
        $this->_tvars['grvisdays'] = json_encode($grvisdays);
        $this->_tvars['grviscnt'] = json_encode($grviscnt);
   
   
        $grordersdays = array();
        $grorderscnt = array();
        foreach($grvisdays_ as $dd)  {
           $grordersdays[]=  date('m-d',$dd);

           $cnt = $conn->GetOne("select count(*) from stats where category = " . H::STAT_ORDER_SHOP . " and date(dt)=". $conn->DBDate($dd) );
           $grorderscnt[]= intval($cnt);     
        }
  
        $this->_tvars['grordersdays'] = json_encode($grordersdays);
        $this->_tvars['grorderscnt'] = json_encode($grorderscnt);
         
    }
        
    public function updateSiteMap($sarg,$post=null) {


        $sm = _ROOT . 'sitemap.xml';
        @unlink($sm);
        $xml = "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";

        $prods = Product::find(" disabled <> 1 and detail  not  like '%<noshop>1</noshop>%' ");
        foreach ($prods as $p) {
            if (strlen($p->sef) > 0) {
                $xml = $xml . " <url><loc>" . _BASEURL . "{$p->sef}</loc></url>";
            } else {
                $xml = $xml . " <url><loc>" . _BASEURL . "sp/{$p->item_id}</loc></url>";
            }
        }
        $xml .= "</urlset>";
        file_put_contents($sm, $xml);
         
    }

 
    
}

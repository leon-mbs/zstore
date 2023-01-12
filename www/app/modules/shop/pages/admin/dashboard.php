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
  
        $vis=[];
  
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

           $cnt = $conn->GetOne("select count(*) from stats where category = " . H::STAT_HIT_SHOP . " and date(dt)=". $conn->DBDate($dd) );
      
           $vis[]=[ date('m-d',$dd),intval($cnt)];
             
        }
     
        $this->_tvars['vis'] = json_encode($vis);
   
        $ord=[];
  
        foreach($grvisdays_ as $dd)  {

           $cnt = $conn->GetOne("select count(*) from stats where category = " . H::STAT_ORDER_SHOP . " and date(dt)=". $conn->DBDate($dd) );
         
           $ord[]=[date('m-d',$dd),intval($cnt)];
               
        }
  

        $this->_tvars['ord'] = json_encode($ord);
 
        $sql = " content  LIKE '%<shoporder>1</shoporder>%' "  ;
        $sql .= " and date(document_date) <= " . $conn->DBDAte(time());
        $sql .= " and date(document_date) > " . $conn->DBDAte( strtotime("- 30 day",time()) );
        $docs = \App\Entity\Doc\Document::find($sql) ;
        $items = array();
        $cats = array();
        foreach($docs as $doc){
            foreach($doc->unpackDetails('detaildata') as $it){
                if(!isset($items[$it->itemname])) $items[$it->itemname] = 0;
                if(!isset($cats[$it->cat_name])) $cats[$it->cat_name] = 0;
                $items[$it->itemname] += ($it->quantity * $it->price) ;
                $cats[$it->cat_name] += ($it->quantity * $it->price) ;
            }
        }
        asort($items,SORT_NUMERIC) ;
        asort($cats,SORT_NUMERIC) ;
        $items = array_reverse($items,true) ;
        $cats = array_reverse($cats,true) ;

        $this->_tvars['topitems']  = array();;
        $this->_tvars['topcats'] = array();;
        $this->_tvars['topview'] = array();;
        foreach($items as $k=>$v) {
            $this->_tvars['topitems'][]= array('v'=>$v,'n'=>$k);             
            if(count($this->_tvars['topitems'])==10) break;
        }
        foreach($cats as $k=>$v) {
            $this->_tvars['topcats'][]= array('v'=>$v,'n'=>$k);             
            if(count($this->_tvars['topcats'])==10) break;
        }
        
        
        $sql = "select cnt,itemname from (select keyd,count(*) as cnt from stats where category = " . H::STAT_VIEW_ITEM . " and date(dt)>" . $conn->DBDAte( strtotime("- 30 day",time()) ) ;
        $sql .= "group by keyd ) t join items i on i.item_id = t.keyd order  by  t.cnt desc  "    ;
        $res = $conn->Execute($sql);
        foreach($res as $row) {
            $this->_tvars['topview'][]= array('v'=>$row['cnt'],'n'=>$row['itemname']);             
            if(count($this->_tvars['topview'])==10) break;
        }
            
    
    }
        
  

 
    
}

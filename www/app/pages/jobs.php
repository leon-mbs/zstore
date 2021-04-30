<?php

namespace App\Pages;


use App\Entity\Event;
use App\Helper as H;
use App\System;
use App\Application as App;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Panel;
use Zippy\Html\Label;
use Zippy\Html\Form\Date;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\RedirectLink;
 

class Jobs extends \App\Pages\Base
{
    
    public function __construct() {
        parent::__construct();
        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }
        
        $this->add(new Panel('listpan'));
        
        $this->listpan->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->listpan->filter->add(new TextInput('searchtext'));
        
        $this->listpan->add(new  ClickLink('addnew',$this,'onAddNew')) ;
        
        
        $this->ds = new \ZCL\DB\EntityDataSource("\\App\\Entity\\Event", "  user_id=" . $user->user_id, " isdone asc,  (date(eventdate) > date(now( )))  asc, eventdate desc");

        $this->listpan->add(new DataView("nlist", $this->ds, $this, 'OnRow'));
        $this->listpan->nlist->setPageSize(H::getPG());
        $this->listpan->add(new \Zippy\Html\DataList\Pager("pag", $this->listpan->nlist));
        
        $this->add(new  Form('addeventform'))->setVisible(false); 
        $this->addeventform->onSubmit($this, 'onSave');
        $this->addeventform->add(new \ZCL\BT\DateTimePicker('addeventdate', time()));
        $this->addeventform->add(new TextInput('addeventtitle'));
        $this->addeventform->add(new TextArea('addeventdesc'));
        $this->addeventform->add(new DropDownChoice('addeventnotify', array(1 => "1 час", 2 => "2 часа", 4 => "4 часа", 8 => "8 часов", 16 => "16 часов", 24 => "24 часа"), 0));
        $this->addeventform->add(new ClickLink('cancel',$this,'onCancel'));
       
        
        $this->listpan->nlist->Reload();       
    }   
     public function OnRow($row) {
        $event = $row->getDataItem();


        $row->add(new RedirectLink("customer","\\App\\Pages\\Reference\\CustomerList",array($event->customer_id)))->setValue($event->customer_name );
        
        $row->add(new Label("description"))->setText($event->description );
        $row->add(new Label("title"))->setText($event->title );
        $row->add(new Label("date", \App\Helper::fdt($event->eventdate)));
        $row->add(new ClickLink('toon',$this, 'onDoneClick'))->setVisible($event->isdone!=1);
        $row->add(new ClickLink('tooff',$this, 'onDoneClick'))->setVisible($event->isdone==1);
        $row->add(new Label("stwait"))->setVisible(false);
        $row->add(new Label("sttoday"))->setVisible(false);
        $row->add(new Label("stpast"))->setVisible(false);
        $row->add(new Label("stdone"))->setVisible(false);
        $date = new \Carbon\Carbon();
        $date = $date->addDay();
        $start = $date->startOfDay()->timestamp;
          
        if($start <=  $event->eventdate){
            $row->stwait->setVisible(true) ;  
        }  else
        if($start >  $event->eventdate && time() <  $event->eventdate){
            $row->sttoday->setVisible(true) ;  
        }  else
        if(time() >  $event->eventdate){
            $row->stpast->setVisible(true) ;  
        }
        if($event->isdone==1){
            $row->stdone->setVisible(true) ;  
            $row->stwait->setVisible(false) ;  
            $row->sttoday->setVisible(false) ;  
            $row->stpast->setVisible(false) ;  
        }
   
 
    }

   public function onDoneClick($sender) {
         $item = $sender->getOwner()->getDataItem();
         
         $item->isdone = strpos($sender->id,"toon") === 0  ?1:0 ;
         $item->save();
         $this->listpan->nlist->Reload();       
  
         
    }
     
    public function onAddNew($sender) {
           
      $this->listpan->setVisible(false) ;   
      $this->addeventform->setVisible(true) ;   
      $this->addeventform->clean() ;   
      $this->addeventform->addeventdate->setDate(time()) ;   
        
        
        
        
    }
    public function onSave($sender) {
        $event = new \App\Entity\Event();
        $event->title = $this->addeventform->addeventtitle->getText();
        $event->description = $this->addeventform->addeventdesc->getText();
        $event->eventdate = $this->addeventform->addeventdate->getDate();
        $event->user_id = System::getUser()->user_id;
        

        if (strlen($event->title) == 0) {
            return;
        }
        $event->save();

        $nt = $this->addeventform->addeventnotify->getValue();
        if ($nt > 0) {

            $n = new \App\Entity\Notify();
            $n->user_id = System::getUser()->user_id;
            $n->dateshow = $event->eventdate - ($nt * 3600);
            $n->message = "<b>" . $event->title . "</b>" . "<br>" . $event->description;
            

            $n->save();
        }
         
      $this->listpan->nlist->Reload(); 
      $this->listpan->setVisible(true) ;   
      $this->addeventform->setVisible(false) ;   
         
    }
    public function onCancel($sender) {
           
      $this->listpan->setVisible(true) ;   
      $this->addeventform->setVisible(false) ;   
    }
     
    
    public function filterOnSubmit($sender) {
        $where='user_id=' . System::getUser()->user_id;
        $text = trim($sender->searchtext->getText());
        if (strlen($text) > 0) {
          $text = Event::qstr('%' . $text . '%');
          $where= " ( description like {$text} or title like {$text} or customer_name like {$text}) and user_id=" . System::getUser()->user_id;    
        }
        
        $this->ds->setWhere();
        $this->nlist->Reload();
    }   
}

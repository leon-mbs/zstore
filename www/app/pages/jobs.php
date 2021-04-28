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
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Panel;
use Zippy\Html\Label;
use Zippy\Html\Form\Date;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\DataList\ArrayDataSource;

class Jobs extends \App\Pages\Base
{
    
    public function __construct() {
        parent::__construct();
        $user = System::getUser();
        if ($user->user_id == 0) {
            App::Redirect("\\App\\Pages\\Userlogin");
        }
        
        $this->add(new Form('filter'))->onSubmit($this, 'filterOnSubmit');
        $this->filter->add(new TextInput('searchtext'));

        $this->ds = new EntityDataSource("\\App\\Entity\\Event", "  user_id=" . $user->user_id, " isdone asc, eventdate desc");

        $this->add(new DataView("nlist", $this->ds, $this, 'OnRow'));
        $this->nlist->setPageSize(H::getPG());
        $this->add(new \Zippy\Html\DataList\Pager("pag", $this->nlist));
        $this->nlist->Reload();       
    }   
     public function OnRow($row) {
        $event = $row->getDataItem();


        $row->add(new Label("title"))->setText($event->title );
        $row->add(new Label("date", \App\Helper::fdt($event->eventdate)));
        $row->add(new ClickLink('toon',$this, 'onDoneClick'))->setVisible($item->isdone==1);
        $row->add(new ClickLink('tooff',$this, 'onDoneClick'))->setVisible($item->isdone!=1);
 
    }

   public function onDoneClick($sender) {
         $item = $sender->getOwner()->getDataItem();
         
         $item->isdone = strpos($sender->id,"tooff") === 0  ?1:0 ;
          $this->nlist->Reload();  
         
    }
     
    
    public function filterOnSubmit($sender) {
        $text = trim($sender->searchtext->getText());
        if (strlen($text) == 0) {
            return;
        }
        $text = Event::qstr('%' . $text . '%');
        $this->ds->setWhere(" ( description like {$text} or title like {$text} or customer_name like {$text}) and user_id=" . System::getUser()->user_id);
        $this->nlist->Reload();
    }   
}

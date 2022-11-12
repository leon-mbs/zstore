<?php

namespace App\Modules\Shop\Pages\Admin;

use App\Application as App;
use App\Entity\Item;
use App\Modules\Shop\Entity\Product;
use App\System;
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

class Custom extends \App\Pages\Base
{
    public $_pages = array();
   
    public function __construct() {
        parent::__construct();
        if (strpos(System::getUser()->modules, 'shop') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg('noaccesstopage');
            App::RedirectError();
            return;
        }


 
        $this->add(new Panel('pagespan'));
        
        $this->pagespan->add(new ClickLink('padd'))->onClick($this, 'paddOnClick');
        $this->pagespan->add(new DataView('plist', new ArrayDataSource($this, "_pages"), $this, 'plistOnRow'));
    
        
        $this->add(new Form('pageform'))->onSubmit($this, 'savePageOnClick');
        $this->pageform->add(new TextArea('pagetext'));
        $this->pageform->add(new TextInput('pagelink'));
        $this->pageform->add(new TextInput('pagetitle'));
        $this->pageform->add(new TextInput('oldlink'));
        $this->pageform->add(new TextInput('pageorder'));
        $this->pageform->setVisible(false) ;
        $this->pageform->add(new ClickLink('pcancel'))->onClick($this, 'pcancelOnClick');
         
        
        $shop = System::getOptions("shop");
        if (!is_array($shop)) {
            $shop = array();
        }
        $this->_pages =    $shop['pages'];
        if (!is_array($this->_pages)) {
            $this->_pages = array();
        }   

 
 
        $this->pagespan->plist->Reload() ;
    
    }

 
 
 
   
    public function plistOnRow($row) {
        $p = $row->getDataItem();
        $row->add(new Label("ptitle",$p->title));
        $row->add(new Label("plink",$p->link));
        $row->add(new Label("porder",$p->order));
        $row->add(new ClickLink("pedit",$this,"peditOnClick"));
        $row->add(new ClickLink("pdel",$this,"pdelOnClick"));
    }
    public function paddOnClick($sender) {
        $this->pageform->clean();  
        $this->pageform->setvisible(true);  
        $this->pagespan->setvisible(false);  
       
    }
    public function peditOnClick($sender) {
        $page = $sender->getOwner()->getDataItem();
       
       
        $this->pageform->oldlink->setText($page->link)  ;
        $this->pageform->pagelink->setText($page->link)  ;
        $this->pageform->pagetitle->setText($page->title) ;
        $this->pageform->pageorder->setText($page->order) ;
       
        $this->pageform->pagetext->setText(@base64_decode($page->text));
         
        $this->pageform->setvisible(true);  
        $this->pagespan->setvisible(false);  
        
    }
    public function pcancelOnClick($sender) {
         
        $this->pageform->setvisible(false);  
        $this->pagespan->setvisible(true);  
       
    }
    public function pdelOnClick($sender) {
        
        $p = $sender->getOwner()->getDataItem();
        
        $this->_pages = array_diff_key($this->_pages, array($p->link => $this->_pages[$p->link]));
       
        $shop = System::getOptions("shop");
        $shop['pages'] = $this->_pages ;    
        System::setOptions("shop", $shop);
        
        $this->pagespan->plist->Reload() ;
       
    }
 
  public function savePageOnClick($sender) {

        $oldlink = $sender->oldlink->getText();
        if(strlen($oldlink)>0)  {
             $this->_pages = array_diff_key($this->_pages, array($oldlink => $this->_pages[$oldlink]));
    
        }
        
        $page = new \App\DataItem();
        $page->link = $sender->pagelink->getText()  ;
        $page->title = $sender->pagetitle->getText() ;
        $page->order = $sender->pageorder->getText() ;
        
        $page->text = base64_encode($sender->pagetext->getText());
 
        $this->_pages[$page->link] = $page;
        
    usort($this->_pages, function($a, $b) {
            return $a->order > $b->order;
        });       
        $pages=array();
        foreach($this->_pages as $p){
            $pages[$p->link]=$p;
        }
        $this->_pages =$pages;
        $shop = System::getOptions("shop");
        $shop['pages'] = $this->_pages; 
        System::setOptions("shop", $shop);
        
        $this->pagespan->plist->Reload() ;
 
       $this->pageform->setvisible(false);  
        $this->pagespan->setvisible(true);  
    
    }
     
}

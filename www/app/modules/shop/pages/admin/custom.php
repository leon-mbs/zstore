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
    public $_newlist = array();
   
    public function __construct() {
        parent::__construct();
        if (strpos(System::getUser()->modules, 'shop') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");
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
  
        $this->add(new Form('newform'))->onSubmit($this, 'newaddOnClick');
        $this->newform->add(new AutocompleteTextInput('newitem'))->onText($this, 'OnAutoItem');;
 
        $this->add(new DataView('newlist', new ArrayDataSource($this, "_newlist"), $this, 'newlistOnRow'));
 
 
        $this->pagespan->plist->Reload() ;
        
        $ar = @unserialize(\App\Helper::getVal('shop_newlist') ) ;
        if(is_array($ar)) {
           $this->_newlist = $ar;
        }   
        $this->newlist->Reload() ;
    
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

   public function newaddOnClick($sender) {
        $item_id = $sender->newitem->getKey() ;
        $itemname=$sender->newitem->getText() ;
        $item = Item::load($item_id) ;
        if($item != null)  {
           $tmp = array(); 
           $di=new \App\DataItem() ;
           $di->item_id = $item_id;
           $di->itemname = $itemname;
           $di->image_id = $item->image_id;
           $tmp[] = $di;
           foreach($this->_newlist as $v) {
               $tmp[] = $v; 
           }
           $this->_newlist= $tmp;
           $this->newlist->Reload() ;
           $sender->newitem->setKey(0);             
           $sender->newitem->setText('');  
           
           \App\Helper::setVal('shop_newlist',serialize($tmp))  ;
        }                                                                 
   }  

    public function newlistOnRow($row) {
       $i = $row->getDataItem();
       $row->add(new Label("newname",$i->itemname));
       $row->add(new ClickLink("newdel",$this,"newdelOnClick"));
       $row->add(new \Zippy\Html\Link\BookmarkableLink('imagelistitem'))->setValue("/loadimage.php?t=t&id={$i->image_id}");
       if(strlen( $i->thumb)>0) {
           $row->imagelistitem->setValue( $i->thumb);    
       }
       $row->imagelistitem->setAttribute('href', "/loadimage.php?id={$item->image_id}");
    
    }

    public function newdelOnClick($sender) {
           $i = $sender->getOwner()->getDataItem();
           $tmp= array();
           foreach($this->_newlist as $v) {
               if($v->item_id==$i->item_id)    continue;
               $tmp[] = $v; 
           }
           $this->_newlist= $tmp;
           $this->newlist->Reload() ;
       
           \App\Helper::setVal('shop_newlist',serialize($tmp))  ;
          
   
    }  
   
    public function OnAutoItem($sender) {
        $text = Item::qstr('%' . $sender->getText() . '%');
 
        return Item::findArray('itemname', " disabled <> 1 and detail  not  like '%<noshop>1</noshop>%' and (itemname like {$text} or item_code like {$text})  ", 'itemname');
    }      
}

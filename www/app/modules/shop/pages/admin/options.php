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

class Options extends \App\Pages\Base
{
    public $_pages = array();
   
    public function __construct() {
        parent::__construct();
        if (strpos(System::getUser()->modules, 'shop') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg('noaccesstopage');
            App::RedirectError();
            return;
        }


        $this->add(new Form('shop'))->onSubmit($this, 'saveShopOnClick');

        $this->shop->add(new DropDownChoice('shopordertype', array(), 0));
        

        $this->shop->add(new DropDownChoice('shopdefpricetype', \App\Entity\Item::getPriceTypeList()));
        $this->shop->add(new DropDownChoice('shopdefbranch', \App\Entity\Branch::getList()));
        $this->shop->add(new TextInput('email'));
        $this->shop->add(new TextInput('shopname'));
        $this->shop->add(new TextInput('currencyname'));
        $this->shop->add(new TextInput('phone'));
        $this->shop->add(new File('logo'));
        $this->shop->add(new CheckBox('uselogin'));
        $this->shop->add(new CheckBox('usefilter'));
        $this->shop->add(new CheckBox('usefeedback'));
        $this->shop->add(new CheckBox('usemainpage'));
        $this->shop->add(new DropDownChoice('salesource', \App\Helper::getSaleSources(), "0"));
        
        
        $this->add(new Form('pay'))->onSubmit($this, 'savePayOnClick');
        $this->pay->add(new DropDownChoice('paysystem',array() ))->onChange($this, 'onPaySystem');
        $this->pay->add(new DropDownChoice('mf', \App\Entity\MoneyFund::getList(2) ));
        $this->pay->add(new TextInput('lqpublic'  ));
        $this->pay->add(new TextInput('lqpriv'  ));
        $this->pay->add(new TextInput('wpsevret'  ));
        $this->pay->add(new TextInput('wpmacc'  ));
        $this->pay->add(new TextInput('wpsite'  ));
        
        
        $this->add(new Panel('adminpan'));
        $this->adminpan->add(new ClickLink('updatesitemap'))->onClick($this, 'updateSiteMapOnClick');
        
        $this->adminpan->add(new ClickLink('padd'))->onClick($this, 'paddOnClick');
        $this->adminpan->add(new DataView('plist', new ArrayDataSource($this, "_pages"), $this, 'plistOnRow'));
    
        
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

        $this->shop->shopdefbranch->setValue($shop['defbranch']);
        $this->shop->shopordertype->setValue($shop['ordertype']);
        $this->shop->shopdefpricetype->setValue($shop['defpricetype']);
        $this->shop->salesource->setValue($shop['salesource']);
        $this->shop->currencyname->setText($shop['currencyname']);
        $this->shop->uselogin->setChecked($shop['uselogin']);
        $this->shop->usefilter->setChecked($shop['usefilter']);
        
        $this->shop->usefeedback->setChecked($shop['usefeedback']);
        $this->shop->usemainpage->setChecked($shop['usemainpage']);
        $this->shop->shopname->setText($shop['shopname']);
        $this->shop->email->setText($shop['email']);
        $this->shop->currencyname->setText($shop['currencyname']);
        $this->shop->phone->setText($shop['phone']);
        
        $this->pay->paysystem->setValue($shop['paysystem']);
        $this->pay->mf->setValue($shop['mf_id']);
        $this->pay->lqpublic->setText($shop['lqpublic']);
        $this->pay->lqpriv->setText($shop['lqpriv']);
        $this->pay->wpsevret->setText($shop['wpsevret']);
        $this->pay->wpmacc->setText($shop['wpmacc']);
        $this->pay->wpsite->setText($shop['wpsite']);
        $this->onPaySystem(null);
        
        $this->adminpan->plist->Reload() ;
    
    }

    public function savePayOnClick($sender) {
        $shop = System::getOptions("shop");
        if (!is_array($shop)) {
            $shop = array();
        }
        $shop['paysystem'] = $sender->paysystem->getValue();
        $shop['mf_id'] =  intval($sender->mf->getValue() ); 
        if($shop['mf_id']==0) {
            $this->setError('noselmf');
            return;
        }
        $shop['lqpriv'] =  $sender->lqpriv->getText() ; 
        $shop['lqpublic'] = $sender->lqpublic->getText() ; 
        $shop['wpsevret'] = $sender->wpsevret->getText() ; 
        $shop['wpmacc'] = $sender->wpmacc->getText() ; 
        $shop['wpsite'] = $sender->wpsite->getText() ; 

        System::setOptions("shop", $shop);
        $this->setSuccess('saved');
        
    }
    
    public function onPaySystem($sender) {
         if($sender!= null) {
             $this->goAnkor('paysystem') ;   
         }
         $ps = intval($this->pay->paysystem->getValue()) ;
         $this->pay->mf->setVisible($ps>0);
         $this->pay->lqpriv->setVisible($ps==2);
         $this->pay->lqpublic->setVisible($ps==2);
         $this->pay->wpsevret->setVisible($ps==1);
         $this->pay->wpmacc->setVisible($ps==1);
         $this->pay->wpsite->setVisible($ps==1);

    }    
    
    public function saveShopOnClick($sender) {
        $shop = System::getOptions("shop");
        if (!is_array($shop)) {
            $shop = array();
        }


        $shop['defbranch'] = $this->shop->shopdefbranch->getValue();
        $shop['ordertype'] = $this->shop->shopordertype->getValue();
        $shop['defpricetype'] = $this->shop->shopdefpricetype->getValue();
        $shop['salesource'] = $this->shop->salesource->getValue();
        $shop['email'] = $this->shop->email->getText();
        $shop['shopname'] = $this->shop->shopname->getText();
        $shop['currencyname'] = $this->shop->currencyname->getText();
        $shop['phone'] = $this->shop->phone->getText();
        $shop['uselogin'] = $this->shop->uselogin->isChecked() ? 1 : 0;
        $shop['usefilter'] = $this->shop->usefilter->isChecked() ? 1 : 0;
        
        $shop['usefeedback'] = $this->shop->usefeedback->isChecked() ? 1 : 0;
        $shop['usemainpage'] = $this->shop->usemainpage->isChecked() ? 1 : 0;

        $file = $sender->logo->getFile();
        if (strlen($file["tmp_name"]) > 0) {
            $imagedata = getimagesize($file["tmp_name"]);

            if (preg_match('/(gif|png|jpeg)$/', $imagedata['mime']) == 0) {
                $this->setError('invalidformat');
                return;
            }

            if ($imagedata[0] * $imagedata[1] > 10000000) {
                $this->setError('toobigimage');
                return;
            }

            $name = basename($file["name"]);
            move_uploaded_file($file["tmp_name"], _ROOT . "upload/" . $name);

            $shop['logo'] = "/upload/" . $name;
        }
        System::setOptions("shop", $shop);
        $this->setSuccess('saved');
    }

    public function updateSiteMapOnClick($sender) {


        $sm = _ROOT . 'sitemap.xml';
        @unlink($sm);
        $xml = "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";

        $prods = Product::find(" disabled <>1 and detail  not  like '%<noshop>1</noshop>%' ");
        foreach ($prods as $p) {
            if (strlen($p->sef) > 0) {
                $xml = $xml . " <url><loc>" . _BASEURL . "{$p->sef}</loc></url>";
            } else {
                $xml = $xml . " <url><loc>" . _BASEURL . "sp/{$p->item_id}</loc></url>";
            }
        }
        $xml .= "</urlset>";
        file_put_contents($sm, $xml);
        $this->setSuccess('refreshed');
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
        $this->adminpan->setvisible(false);  
       
    }
    public function peditOnClick($sender) {
        $page = $sender->getOwner()->getDataItem();
       
       
        $this->pageform->oldlink->setText($page->link)  ;
        $this->pageform->pagelink->setText($page->link)  ;
        $this->pageform->pagetitle->setText($page->title) ;
        $this->pageform->pageorder->setText($page->order) ;
       
        $this->pageform->pagetext->setText(@base64_decode($page->text));
         
        $this->pageform->setvisible(true);  
        $this->adminpan->setvisible(false);  
        
    }
    public function pcancelOnClick($sender) {
         
        $this->pageform->setvisible(false);  
        $this->adminpan->setvisible(true);  
       
    }
    public function pdelOnClick($sender) {
        
        $p = $sender->getOwner()->getDataItem();
        
        $this->_pages = array_diff_key($this->_pages, array($p->link => $this->_pages[$p->link]));
       
        $shop = System::getOptions("shop");
        $shop['pages'] = $this->_pages ;    
        System::setOptions("shop", $shop);
        
        $this->adminpan->plist->Reload() ;
       
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
        
        $this->adminpan->plist->Reload() ;
 
       $this->pageform->setvisible(false);  
        $this->adminpan->setvisible(true);  
    
    }
    
}

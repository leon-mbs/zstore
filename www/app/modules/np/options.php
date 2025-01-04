<?php

namespace App\Modules\NP;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Binding\PropertyBinding as Bind;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Label;
use App\Application as App;

class Options extends \App\Pages\Base
{
    public $_gablist = array();    
    
    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'np') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }

        $modules = System::getOptions("modules");

        $form = $this->add(new Form("cform"));

        $form->add(new TextInput('apikey', $modules['npapikey']));

        $form->onSubmit($this, 'saveapiOnClick');

     

        $form = $this->add(new Form("oform"));

        $form->add(new AutocompleteTextInput('city'))->onText($this, 'onTextCity');
        $form->city->onChange($this, 'onCity');
        $form->add(new AutocompleteTextInput('point'))->onText($this, 'onTextPoint');;
        
        $form->add(new TextInput('tel'))->setText($modules['nptel']??'');
 
        $form->onSubmit($this, 'savedataOnClick');        
        
        $form->city->setKey($modules['npcityref']);
        $form->city->setText($modules['npcity']);
        $form->point->setKey($modules['nppointref']);
        $form->point->setText($modules['nppoint']);
        
        if(strlen( $modules['npgl'] ?? '') >0) {
           $this->_gablist = unserialize( $modules['npgl'] );    
        }
   
   
        $this->add(new Form("gabform"))->onSubmit($this,'addGab');   
        $this->gabform->add(new TextInput('gw')) ;
        $this->gabform->add(new TextInput('gh')) ;
        $this->gabform->add(new TextInput('gd')) ;
      
        $this->add(new DataView('gablist', new ArrayDataSource(new Bind($this, '_gablist')), $this, 'gabListOnRow'));
        $this->gablist->Reload();
        
        
   
    }

    public function saveapiOnClick($sender) {
        $apikey = $this->cform->apikey->getText();

        $modules = System::getOptions("modules");

        $modules['npapikey'] = $apikey;

        System::setOptions("modules", $modules);
        $this->setSuccess('Збережено');
        
    }

    public function savedataOnClick($sender) {
        $cityref = $this->oform->city->getKey();
        $city = $this->oform->city->getText();
        $pointref = $this->oform->point->getKey();
        $point = $this->oform->point->getText();

        $modules = System::getOptions("modules");

        $modules['npcity'] = $city;
        $modules['npcityref'] = $cityref;
        $modules['nppoint'] = $point;
        $modules['nppointref'] = $pointref;
        $modules['nptel'] = $this->oform->tel->getText();

        System::setOptions("modules", $modules);
        $this->setSuccess('Збережено');
    }

   
    public function onTextCity($sender) {
        $text = $sender->getText()  ;
        $api = new Helper();
        $list = $api->searchCity($text);

        if($list['success']!=true) return;
        $opt=[];  
        foreach($list['data'] as $d ) {
            foreach($d['Addresses'] as $c) {
               $opt[$c['Ref']]=$c['Present']; 
            }
        }
        
        return $opt;
       
    }

    public function onCity($sender) {
     
        $this->oform->point->setKey('');
        $this->oform->point->setText('');
    }
  
    public function onTextPoint($sender) {
        $text = $sender->getText()  ;
        $ref=  $this->oform->city->getKey();
        $api = new Helper();
        $list = $api->searchPoints($ref,$text);
       
   if($list['success']!=true) return;
        $opt=[];  
        foreach($list['data'] as $d ) {
           $opt[$d['WarehouseIndex']]=$d['Description']; 
        }
        
        return $opt;        
    }

     
    public function gabListOnRow(  $row) {
        $item = $row->getDataItem();
        $row->add(new Label('gabname',$item->gabname ));
        $row->add(new ClickLink('delgab', $this, 'onDelgab'));
    }
    public function onDelgab($sender) {
        $item = $sender->getOwner()->getDataItem();
        $tmp=[];
        foreach($this->_gablist as $i=>$v){
            if($i==$item->id) {
                continue;
            }
            $tmp[$i] = $v;
        }
        $this->_gablist = $tmp;
        $this->gablist->Reload();
        
        $modules = System::getOptions("modules");
        $modules['npgl'] = serialize($this->_gablist);
        System::setOptions("modules", $modules);
        
    }
    public function addGab($sender) {
        $ls = new \App\DataItem();
        
        $w=$sender->gw->getText();
        $h=$sender->gh->getText();
        $d=$sender->gd->getText();
        $sender->clean();
        
        $ls->gabname = "{$w}x{$h}x{$d}";
        $ls->id = time();
        
        
        
        $this->_gablist[$ls->id] = $ls;
        $this->gablist->Reload();
      
        $modules = System::getOptions("modules");
        $modules['npgl'] = serialize($this->_gablist);
        System::setOptions("modules", $modules);

        
    }

}

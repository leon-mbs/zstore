<?php

namespace App\Modules\NP;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
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

        $form = $this->add(new Form("formcache"));

        $form->onSubmit($this, 'savecacheOnClick');

        $form = $this->add(new Form("oform"));
        $form->add(new DropDownChoice('area'))->onChange($this, 'onArea');
        $form->add(new DropDownChoice('city'))->onChange($this, 'onCity');
        $form->add(new DropDownChoice('point'));
        $form->add(new TextInput('tel'))->setText($modules['nptel']);

        if(strlen( $modules['npgl'] ?? '') >0) {
           $this->_gablist = unserialize( $modules['npgl'] );    
        }
   
   
        $this->add(new Form("gabform"))->onSubmit($this,'addGab');   
        $this->gabform->add(new TextInput('gw')) ;
        $this->gabform->add(new TextInput('gh')) ;
        $this->gabform->add(new TextInput('gd')) ;
      
        $this->add(new DataView('gablist', new ArrayDataSource(new Bind($this, '_gablist')), $this, 'gabListOnRow'));
        $this->gablist->Reload();
        
        
        
        $form->onSubmit($this, 'savedataOnClick');

        $this->updateData();
    }

    public function saveapiOnClick($sender) {
        $apikey = $this->cform->apikey->getText();

        $modules = System::getOptions("modules");

        $modules['npapikey'] = $apikey;

        System::setOptions("modules", $modules);
        $this->setSuccess('Збережено');
        $this->updateData();
    }

    public function savedataOnClick($sender) {
        $arearef = $this->oform->area->getValue();
        $area = $this->oform->area->getValueName();
        $cityref = $this->oform->city->getValue();
        $city = $this->oform->city->getValueName();
        $pointref = $this->oform->point->getValue();
        $point = $this->oform->point->getValueName();

        $modules = System::getOptions("modules");

        $modules['nparea'] = $area;
        $modules['nparearef'] = $arearef;
        $modules['npcity'] = $city;
        $modules['npcityref'] = $cityref;
        $modules['nppoint'] = $point;
        $modules['nppointref'] = $pointref;
        $modules['nptel'] = $this->oform->tel->getText();

        System::setOptions("modules", $modules);
        $this->setSuccess('Збережено');
    }

    private function updateData() {
        $modules = System::getOptions("modules");
        if (strlen($modules['npapikey']) == 0) {
            return;
        }


        $api = new Helper();

        $areas = $api->getAreaListCache();

        $this->oform->area->setOptionList($areas);

        $this->oform->area->setValue($modules['nparearef']);

        if (strlen($modules['nparearef']) > 0) {
            $this->onArea($this->oform->area);
            $this->oform->city->setValue($modules['npcityref']);
        }
        if (strlen($modules['npcityref']) > 0) {
            $this->onCity($this->oform->city);
            $this->oform->point->setValue($modules['nppointref']);
        }
    }

    public function onArea($sender) {

        $api = new Helper();
        $list = $api->getCityListCache($sender->getValue());

        $this->oform->city->setOptionList($list);
    }

    public function onCity($sender) {

        $api = new Helper();
        $list = $api->getPointListCache($sender->getValue());

        $this->oform->point->setOptionList($list);
    }

    public function savecacheOnClick($sender) {


        try {
            
           $api = new Helper();

           $ret = $api->updatetCache()  ;
           
           if(strlen($ret['error'] ??'')>0 ) {
               $this->setError($ret['error']);                           
               return;
           }
           if(strlen($ret['warn'] ??'')>0 ) {
               $this->setWarn($ret['warn']);                           
           }
           
        } catch(\Exception $ee) {
            $msg = $ee->getMessage();
            $this->setError($msg);            
        }
        
        $this->updateData();

        $this->setSuccess('Збережено');
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

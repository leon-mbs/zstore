<?php

namespace App\Modules\NP;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use Zippy\WebApplication as App;

class Options extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'np') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(\App\Helper::l('noaccesstopage'));

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

        $form->onSubmit($this, 'savedataOnClick');

        $this->updateData();
    }

    public function saveapiOnClick($sender) {
        $apikey = $this->cform->apikey->getText();

        $modules = System::getOptions("modules");

        $modules['npapikey'] = $apikey;

        System::setOptions("modules", $modules);
        $this->setSuccess('saved');
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
        $this->setSuccess('saved');
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

        @unlink(_ROOT . "upload/arealist.dat");
        @unlink(_ROOT . "upload/citylist.dat");
        @unlink(_ROOT . "upload/pointlist.dat");
        
        @mkdir(_ROOT . "upload") ;
        
        $api = new Helper();

        $areas = array();
        $tmplist = $api->getAreas();
        if($tmplist['success']==FALSE) {
            if(count($tmplist['errors'])>0) {
                $this->setError(array_pop($tmplist['errors'])) ;
                return;
            }
            if(count($tmplist['warnings'])>0) {
                $this->setWarn(array_pop($tmplist['warnings'])) ;
                
            }
            
        } 
        foreach ($tmplist['data'] as $a) {
            $areas[$a['Ref']] = $a['Description'];
        }

        $d = serialize($areas);

        file_put_contents(_ROOT . "upload/arealist.dat", $d);

        $cities = array();

        $tmplist = $api->getCities(0);

        foreach ($tmplist['data'] as $a) {
            $cities[] = array('Ref' => $a['Ref'], 'Area' => $a['Area'], 'Description' => $a['Description']);
        }

        $d = serialize($cities);

        file_put_contents(_ROOT . "upload/citylist.dat", $d);

        $wlist = array();
        $tmplist = $api->getWarehouses('');

        foreach ($tmplist['data'] as $a) {
            $wlist[] = array('Ref' => $a['Ref'], 'City' => $a['CityRef'], 'Description' => $a['Description']);
        }


        $d = serialize($wlist);

        file_put_contents(_ROOT . "upload/pointlist.dat", $d);

        $this->updateData();

        $this->setSuccess('saved');
    }

}

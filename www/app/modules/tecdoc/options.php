<?php

namespace App\Modules\Tecdoc;

use App\System;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\WebApplication as App;
use Zippy\Html\Link\ClickLink;

class Options extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'tecdoc') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectHome();
            return;
        }

        $modules = System::getOptions("modules");

        $form = $this->add(new Form("cform"));

        $form->add(new TextInput('ipath', $modules['td_ipath']));
        $form->add(new TextInput('dbhost', $modules['td_dbhost']));
        $form->add(new TextInput('dbname', $modules['td_dbname']));
        $form->add(new TextInput('dbuser', $modules['td_dbuser']));
        $form->add(new TextInput('dbpass', $modules['td_dbpass']));
        $form->add(new TextInput('bpkey', $modules['td_bpkey']));
        $form->add(new TextInput('omkey', $modules['td_omkeys']));

        $form->add(new DropDownChoice('defpricetype', \App\Entity\Item::getPriceTypeList(), $modules['td_pricetype']));
        $form->add(new DropDownChoice('defstore', \App\Entity\Store::getList(), $modules['td_store']));

        $form->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $form->add(new SubmitButton('savetd'))->onClick($this, 'saveOnClick');
        $form->add(new SubmitButton('savebp'))->onClick($this, 'saveOnClick');
        $form->add(new SubmitButton('saveom'))->onClick($this, 'saveOnClick');
        $form->add(new ClickLink('chconn'))->onClick($this, 'chconnOnClick');

    }


    public function chconnOnClick($sender) {
       try{
           $api = new DBHelper();
           $this->setSuccess("З'єднання успiшно") ;
       } catch (\Exception $e){
            $this->setError($e->getMessage()) ; 
       }
    }
    
    public function saveOnClick($sender) {

        $modules = System::getOptions("modules");
        
        if($sender->id=="save")  {
            $modules['td_pricetype'] = $this->cform->defpricetype->getValue();
            $modules['td_store'] = $this->cform->defstore->getValue();
        }
        
        if($sender->id=="save")  {
            $modules['td_ipath'] = $this->cform->ipath->getText();
            $modules['td_ipath']  = rtrim($modules['td_ipath'],'/' ) .'/';
            $modules['td_dbhost'] = $this->cform->dbhost->getText();
            $modules['td_dbname'] = $this->cform->dbname->getText();
            $modules['td_dbuser'] = $this->cform->dbuser->getText();
            $modules['td_dbpass'] = $this->cform->dbpass->getText();
        }

        if($sender->id=="savebp")  {
            $modules['td_bpkey'] = trim( $this->cform->bpkey->getText() );
        }

        if($sender->id=="saveom")  {
            $modules['td_omkey'] = trim($this->cform->omkey->getText() );
        }
 
        System::setOptions("modules", $modules);
        $this->setSuccess('saved');
    }

}

<?php

namespace App\Pages;

use \App\Entity\User;
use \App\System;
use \Zippy\WebApplication as App;
use \Zippy\WebApplication as H;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\File;
use \App\Entity\Customer;
use \App\Entity\Employee;
use \App\Entity\Equipment;
use \App\Entity\Service;
use \App\Entity\Item;
use \App\Entity\Stock;
use \App\Entity\Image;

class Migration extends \App\Pages\Base {

 

    public function __construct() {
        parent::__construct();
        if (System::getUser()->userlogin != 'admin') {
            System::setErrorMsg('К странице имеет  доступ только администратор ');
            App::RedirectHome();
            return false;
        }

        $this->add(new Form('expform'))->onSubmit($this, 'onExport');
        $storelist = \App\Entity\Store::getList();
        $storelist[10000]='По всем складам';
        $this->expform->add(new DropDownChoice('storeexp',$storelist,0));
        $this->expform->add(new CheckBox('userexp'));
        $this->expform->add(new CheckBox('custexp'));
        $this->expform->add(new CheckBox('empexp'));
        $this->expform->add(new CheckBox('serexp'));
        $this->expform->add(new CheckBox('eqexp'));
        $this->expform->add(new CheckBox('itemexp'));
        
        $this->add(new Form('impform'))->onSubmit($this, 'onImport');
 
        $this->impform->add(new DropDownChoice('storeimp',\App\Entity\Store::getList(),0));
        $this->impform->add(new CheckBox('userimp'));
        $this->impform->add(new CheckBox('custimp'));
        $this->impform->add(new CheckBox('empimp'));
        $this->impform->add(new CheckBox('serimp'));
        $this->impform->add(new CheckBox('eqimp'));
        $this->impform->add(new CheckBox('itemimp'));
        $this->impform->add(new CheckBox('delimp'));
        $this->impform->add(new File('fileimp'));
        
    }

    
    public  function onExport($sender) {
       $data = new MigrationData();
       if($sender->userexp->isChecked())   {
           $data->user = User::find("disabled <> 1 and userlogin <> 'admin'" );
       }
       if($sender->custexp->isChecked())   {
           $data->cust = Customer::find('status <> ' .Customer::STATUS_DISABLED);
       }
       if($sender->empexp->isChecked())   {
           $data->emp = Employee::find('disabled <> 1'  );
       }
       if($sender->eqexp->isChecked())   {
           $data->eq = Equipment::find('disabled <> 1'  );
       }
       if($sender->serexp->isChecked())   {
           $data->ser = Service::find('disabled <> 1'  );
       }
       if($sender->itemexp->isChecked())   {
           $store =$sender->storeexp->getValue();
           $items = Item::find('disabled <> 1'  );
           
           foreach($items  as  $item){
               $d = new MigrationItem();
               $d->item = $item;
               if($item->image_id>0){
                   $d->image =   Image::load($item->image_id);
               }
               
               if($store > 0){
                  
                  $where ="qty<>0 and item_id={$item->item_id}";
                  if($store  < 10000) $where .=" and store_id=".$store;
                  
                  $d->stocks = Stock::find($where);
                   
               }
               
               
               $data->items[]=$d;
               
           }
           
             
       }
       
       
        $file = serialize($data) ;
        
        $size = strlen($file);
        if ($size > 0) {
 
              header('Content-Type: application/octet-stream');
              header('Content-Disposition: attachment; filename=migration.dat'  );
              header('Content-Transfer-Encoding: binary');
      
           
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . $size);
 
            flush();
            echo $file; die;
        }
    }
    public  function onImport($sender) {
  
       $file = $sender->fileimp->getFile();
       $content = file_get_contents($file['tmp_name']);
       $data = @unserialize($content)   ;
       if(($data instanceof MigrationData) == false)  {
           $this->setError('Неверный формат');
           return ;
       }
       $message = "" ; 
       if($sender->userimp->isChecked() && count($data->user)>0)   {
           $i=0;
           foreach($data->user  as $user){
               $user->user_id=0;
               
               
               $cnt = User::findCnt('userlogin  = ' . User::qstr($user->userlogin));
               if($cnt >0) continue;
               
               $user->save();
               $i++;
           }  
           if($i>0)$message .= "{$i} пользователей  "  ;
       }  
       if($sender->custimp->isChecked() && count($data->cust)>0)   {
           $i=0;
           foreach($data->cust  as $cust){
               $cust->customer_id=0;
               
               
               $cnt = Customer::findCnt('customer_name  = ' . Customer::qstr($cust->customer_name));
               if($cnt >0) continue;
               
               $cust->save();
               $i++;
           }  
           if($i>0)$message .= "{$i} контрагентов  "  ;
       }  
       if($sender->empimp->isChecked() && count($data->emp)>0)   {
           $bid = \App\Session::getSession()->branch_id; //если  выбран  конкретный

           $i=0;
           foreach($data->emp  as $emp){
               $emp->employee_id=0;
               
               $_emp = Employee::getFirst("login = '{$emp->login}'");
               if ($_emp != null )  $emp->login='';
               
               
               $cnt = Employee::findCnt('emp_name  = ' . Employee::qstr($emp->emp_name));
               if($cnt >0) continue;
           
               if($bid>0) {
                   $emp->branch_id = $bid;
               }
                            
               $emp->save();
               $i++;
           }  
           if($i>0)$message .= "{$i} сотрудников  "  ;
       }  
       
       if($sender->eqimp->isChecked() && count($data->eq)>0)   {
           $i=0;
           foreach($data->eq  as $eq){
               $eq->eq_id=0;
                 
               $cnt = Equipment::findCnt('eq_name  = ' . Equipment::qstr($eq->eq_name));
               if($cnt >0) continue;
               
               $eq->save();
               $i++;
           }  
           if($i>0)$message .= "{$i} оборудования  "  ;
       }  
       if($sender->serimp->isChecked() && count($data->ser)>0)   {
           $i=0;
           foreach($data->eq  as $ser){
               $ser->service_id=0;
                 
               $cnt = Service::findCnt('service_name  = ' . Service::qstr($ser->service_name));
               if($cnt >0) continue;
               
               $ser->save();
               $i++;
           }  
           if($i>0)$message .= "{$i} сервисов и работ  "  ;
       }  
       if($sender->serimp->isChecked() && count($data->ser)>0)   {
           $i=0;
           foreach($data->eq  as $ser){
               $ser->service_id=0;
                 
               $cnt = Service::findCnt('service_name  = ' . Service::qstr($ser->service_name));
               if($cnt >0) continue;
               
               $ser->save();
               $i++;
           }  
           if($i>0)$message .= "{$i} сервисов и работ  "  ;
       }  
       if($sender->itemimp->isChecked() && count($data->items)>0)   {
           $i=0;
           foreach($data->items  as $d){
               $item = $d->item;
               $item->item_id=0;
                 
               $cnt = Item::findCnt('item_code  = ' . Item::qstr($item->item_code));
               if($cnt >0) continue;
               $cnt = Item::findCnt('itemname  = ' . Item::qstr($item->itemname));
               if($cnt >0) continue;
               
               if($d->image instanceof Image) {
                   $d->image->image_id=0;
                   $d->image->save();
                   $item->image_id = $d->image->image_id;
               }
               
               $item->save();
               $i++;
                 
           }  
           if($i>0)$message .= "{$i} ТМЦ  "  ;
       }  
       
       if(strlen($message)>0){
          $message = "Импортировано ". $message;   
          $this->setSuccess($message);  
       }
       
           
    }
    
}

class  MigrationData {
    public $user = array();
    public $emp = array();
    public $cust = array();
    public $eq = array();
    public $ser = array();
    public $items= array();
    
    
}
class MigrationItem{
    public $item;
    public $image=null;
    public $stocks=array();
}

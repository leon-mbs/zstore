<?php
  
namespace App\Modules\PromUA;

use \Zippy\Html\Form\Form;
use \App\Entity\Item;
use \App\Entity\Store;
use \App\Entity\Stock;
use \App\Entity\Document;
use \App\Entity\Customer;
use \App\Helper as H;
use \App\System;

class Orders extends \App\Pages\Base
{
 
    public function __construct()
    {
         parent::__construct();
        
         if (strpos(System::getUser()->modules, 'promua') === false && System::getUser()->userlogin != 'admin') {
            System::setErrorMsg('Нет права доступа к странице');

            App::RedirectHome();
            return;
         }   
        
         $form = $this->add(new Form("iform"));
         $form->add(new \Zippy\Html\Form\File("filename"));
         $form->onSubmit($this,'onImport')   ;
        
        
    }
    public function onImport($sender) {
        $file = $sender->filename->getFile();
        if (strlen($file['tmp_name']) == 0) {
            $this->setError('Не  выбран  файл');
            return;
        }

 
        $xml =  simplexml_load_file($file['tmp_name']) ;
        if(!$xml instanceof \SimpleXMLElement ){
            $this->setError("Неверный файл");
            return;
        }
        
         
 
        foreach ($xml->children() as $order) {
            $atr = $order->attributes();
            $id =(int)$atr['id'] ;
            $state = (string)$atr['state'] ;
            $source = (string)$atr['source'] ;
            
            
            
            
            $name = (string)  $order->name;
            $phone= (string)  $order->phone;
            $email= (string)  $order->email;
            
            
            
            foreach ($order->items->children() as $item) {
               $price= (int)  $item->price;
               $price= (int)  $item->price;
               $name= (string)  $item->name;
               $sku= (string)  $item->sku;
               $quantity= (int)  $item->quantity; 
               
                            
            }
             
        }       
       
            
    }
} 

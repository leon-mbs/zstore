<?php

namespace App\Modules\Paperless;

use App\System;
use App\Helper as H;
use App\Entity\Doc\Document;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use Zippy\WebApplication as App;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\BookmarkableLink;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;

class DocList extends \App\Pages\Base
{

     

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'paperless') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(H::l('noaccesstopage'));

            App::RedirectError();
            return;
        }
 
    }

    public function init($arg,$post=null){
        $user = \App\System::getUser() ;
  
  
        $cust= \App\Entity\Customer::find("status = 0 and customer_id IN  ( select customer_id FROM documents_view WHERE  meta_name  IN ('Invoice','GoodsIssue','ServiceAct')  and content   not like '%<paperless>%')");
  
        $ret = [];  
        $ret['clist']  =  [];
        foreach($cust as $c){
           $ret['clist'][] = array('key'=>$c->customer_id,'value'=>$c->customer_name);     
        }
        $ret['firmid']  =  0;
        $ret['firms']  =  [];
        foreach(\App\Entity\Firm::find('') as $f){
           if(strlen($f->ppokeyid)==0) continue;
           $ret['firms'][] = array('key'=>$f->firm_id,'value'=>$f->firm_name);     
           if($ret['firmid']==0) $ret['firmid']  = $f->firm_id;//первую
        }
      
         
        return json_encode($ret, JSON_UNESCAPED_UNICODE);     
       
    }    
 
    public function loaddocs($arg,$post=null){
      //  $user = \App\System::getUser() ;
  
        $sql = "meta_name='{$arg[1]}' and content  not  like '%paperless%' and customer_id  >0 ";
        if($arg[0] > 0){
            $sql .= " and customer_id={$arg[0]} ";
        }
        $docs=Document::find($sql,"document_id desc");
  
        $ret = [];  
        $ret['docs']  =  [];
        foreach($docs as $d){
           $ret['docs'][] = array('id'=>$d->document_id,
                                  'number'=>$d->document_number,
                                  'cname'=>$d->customer_name,
                                  'date'=> H::fd($d->document_date),
                                  'amount'=> H::fa($d->payamount)  
                                  
                                  );     
        }
     
        unset($docs) ;
         
        return json_encode($ret, JSON_UNESCAPED_UNICODE);     
       
    }    
 
    
    public function mark($arg,$post=null){
        $ids = $post;
        foreach(Document::find("document_id  in ({$ids})") as $doc){
            $doc->headerdata['paperless'] = 0;
            $doc->save();
        };
        
    }

    public function send($arg,$post=null){
        try{

            $doc = Document::load($arg[0]);    
      
           
            $doc = $doc->cast();
            $name = $doc->document_number;
            //pdf
            $html = $doc->generateReport();
            $dompdf = new \Dompdf\Dompdf(array('isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans'));
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $pdf = $dompdf->output();

            //sign
            if($arg[1] > 0) {
                $firm = \App\Entity\Firm::load($arg[1])  ;
                
                $ret = \App\Modules\PPO\PPOHelper::send($pdf,"doc",$firm,true) ;
                if($ret['success'] != true) {
                    return $name." ".$ret['data'];     
                }
                $pdf = $ret['signed'] ;
                
                
            }
            
            //send
            $token =  System::getSession()->pltoken;
            $c = \App\Entity\Customer::load($doc->customer_id) ; 
           
            list($ok,$data) = Helper::send($token,$pdf,$name,$c->email)  ;
            $doc->headerdata['paperless'] = 0;
            $doc->save();
            if(strlen($c->email)==0){
                return $name." ok ".H::l("noemail");                                 
            }
                
            return $name." ok";                 
        }catch(\Exception $e){
           H::log($name .' '. $e->getMessage()) ; 
           return $name ." ".$e->getMessage();     
            
        }
       
        
  
    }    
    
    
     public function connect($arg,$post=null){
        list($ok,$data) = Helper::connect()  ;
        if($ok == "ok"){
           System::getSession()->pltoken = $data;
       
           return "";     
               
        }
        return $data;     
   
   
   
     }
}

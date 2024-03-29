<?php

namespace App\Modules\VDoc;

use App\System;
use App\Helper as H;
use App\Entity\Doc\Document;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use App\Application as App;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\BookmarkableLink;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;

class DocList extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'vdoc') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }

    }

    public function init($arg, $post=null) {
        $user = \App\System::getUser() ;
  
        $ret = [];
        $ret['clist']  =  [];
        foreach(        \App\Entity\Customer::findYield("status = 0 and customer_id IN  ( select customer_id FROM documents_view WHERE  meta_name  IN ('Invoice','GoodsIssue','ServiceAct')  and content   not like '%<vdoc>%')") as $c) {
            if(($c->edrpou ?? '')=='' ) {
                continue   ;
            }
            
            $ret['clist'][] = array('key'=>$c->customer_id,'value'=>$c->customer_name);
        }
        $ret['firmid']  =  0;
        $ret['firms']  =  [];
        foreach(\App\Entity\Firm::find('disabled <> 1') as $f) {
           
            $ret['firms'][] = array('key'=>$f->firm_id,'value'=>$f->firm_name);
            if($ret['firmid']==0) {
                $ret['firmid']  = $f->firm_id;
            }//первую
        }


        return json_encode($ret, JSON_UNESCAPED_UNICODE);

    }

    public function loaddocs($arg, $post=null) {
        //  $user = \App\System::getUser() ;

        $sql = " (firm_id={$arg[2]} or coalesce(firm_id,0)=0) and  meta_name='{$arg[1]}' and state >4 and content  not  like '%vdoc%' and customer_id  >0 ";
        if($arg[0] > 0) {
            $sql .= " and customer_id={$arg[0]} ";
        } else {
            return [];
        }
        

        $ret = [];
        $ret['docs']  =  [];
        foreach(Document::findYield($sql, "document_id desc") as $d) {
            $ret['docs'][] = array('id'=>$d->document_id,
                                   'number'=>$d->document_number,
                                   'cname'=>$d->customer_name,
                                   'date'=> H::fd($d->document_date),
                                   'amount'=> H::fa($d->payamount)

                                   );
        }

        

        return json_encode($ret, JSON_UNESCAPED_UNICODE);

    }


    public function mark($arg, $post=null) {
        $ids = $post;
        foreach(Document::findYield("document_id  in ({$ids})") as $doc) {
            $doc->headerdata['vdoc'] = 0;
            $doc->save();
        }

    }

    public function check($arg, $post=null) {
        $firm = \App\Entity\Firm::load($arg[0])  ;
        if(strlen($firm->vdoc)==0) {
            return "Не задано токен Вчасно";
        }
        if(strlen($firm->tin)==0) {
            return "Компанiя повинна мати ЄДРПОУ";
        }
        if($arg[1]==true && strlen($firm->ppokeyid)==0 ) {
            return "Не заданий ключ для КЕП";
        }
      
             
        return "";
    }
    public function send($arg, $post=null) {
        $name ='';
        try {

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

            $firm = \App\Entity\Firm::load($arg[1])  ;
            
            //sign
            if($arg[2] == true) {
                 
                $ret = \App\Modules\PPO\PPOHelper::send($pdf, "doc", $firm, true) ;
                if($ret['success'] != true) {
                    return $name." ".$ret['data'];
                }
                $pdf = $ret['signed'] ;


            }

            //send

            $c = \App\Entity\Customer::load($doc->customer_id) ;

           
            $na=[];
            $na[]= $firm->tin  ;
            $na[]= $c->edrpou ;
            $na[]=  date('Ymd', $doc->document_date) ;
            $na[]=  str_replace(' ','', $doc->meta_desc) ;
            $na[]=  str_replace(' ','', $doc->document_number) ;
            
            
            $filename = implode('_',$na) .'.pdf'; ;
        //    $filename= "2475406556_3235608644_20170213_Рахунок_РН-026.pdf";
            
            list($ok, $data) = Helper::senddoc( $pdf, $filename,$firm->vdoc  )  ;
            if($ok != "ok") {
                return $name ." ".$data;
            }
            
            $doc->headerdata['vdoc'] = 0;
            $doc->save();
      

            return $name." ok";
        } catch(\Exception $e) {
            H::log($name .' '. $e->getMessage()) ;
            return $name ." ".$e->getMessage();

        }



    }

   
}

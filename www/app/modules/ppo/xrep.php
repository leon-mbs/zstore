<?php

namespace App\Modules\PPO;

use App\Application as App;
use App\System;
use App\Entity\Pos;
use App\Helper as H;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;

 /**
 * Х-отчет
 */ 
class XRep extends \App\Pages\Base
{
 
    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'ppo') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg("Немає права доступу до сторінки");

            App::RedirectError();
            return;
        }
        $this->add(new Form('filter'))->onSubmit($this, 'OnSubmit');
        $this->filter->add(new DropDownChoice('pos',Pos::findArray("pos_name"," details like '%<usefisc>1</usefisc>%' ","pos_name"),0 ));
       
 
        $this->add(new Panel('detail'))->setVisible(false);

        $this->detail->add(new Label('preview'));


    }

   
    public function OnSubmit($sender) {
        
        $pos= Pos::load($this->filter->pos->getValue() );
        if($pos==  null) return "";
      
        $firm = \App\Helper::getFirmData( );

        if( ($pos->firmname ??'')=='') {
            $pos->firmname = $firm['firm_name']  ;
        }
        if( ($pos->ipn ??'')=='') {
            $pos->ipn = $firm['ipn'] ??''  ;
        }
        if( ($pos->tin ??'')=='') {
            $pos->tin = $firm['tin']  ;
        }      
      
        
        $html = $this->generateReport($pos);
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";


        $this->detail->setVisible(true);        
        
    }
        
        
    public function generateReport($pos) {
       
       
       $ret=  \App\Modules\PPO\PPOHelper::shiftTotal( $pos);
       if($ret == false) {
            $this->setError("Сервер недоступний або зміна закрита");
            return ;
        }
        if(!is_array($ret['Totals'])) {
            $this->setError("Сервер недоступний або зміна закрита") ;
            return ;
        }
        $zt="";
    
        $header = [];
        
        $header['firmname'] = $pos->firmname;
        $header['inn'] = strlen($pos->ipn) > 0 ? $pos->ipn : false;
        $header['tin'] = $pos->tin;
        $header['address'] = $pos->address;
        $header['pointname'] = $pos->pointname;
        $header['posnumber'] = $pos->fiscalnumber;
            
        
        $header['created_at'] = H::fdt(time() );
        $header['cnt'] =$ret['Totals']['Real']['OrdersCount']??0;
        $header['rcnt'] =$ret['Totals']['Ret']['OrdersCount']??0;
        $header['pays'] =[];
        $header['rpays'] =[];
        $header['sin'] =false;
        $header['sout'] =false;

        
        
       foreach($ret['Totals']['Real']['PayForm'] ??[]  as $form) {
             $header['pays'][]= [ 'label'=>$form['PayFormName'],  'sum'=> H::fa($form['Sum'])];
         
        }  
        
        foreach($ret['Totals']['Ret']['PayForm']??[]  as $form) {
            $header['rpays'][]= [ 'label'=>$form['PayFormName'],  'sum'=> H::fa($form['Sum'])];
    
        }
       
        if(($ret['Totals']['ServiceInput'] ??0) >0){
             $header['sin'] =  H::fa( $ret['Totals']['ServiceInput']);
        }
        if(($ret['Totals']['ServiceOutput'] ??0) >0){
             $header['sout'] =  H::fa( $ret['Totals']['ServiceOutput']);
        }
         
        $report = new \App\Report('report/prro_xrep.tpl');

        $html = $report->generate($header);

        return $html;
    }
 

 

}

<?php

namespace App\Modules\VK;

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

        if (strpos(System::getUser()->modules, 'vkassa') === false && System::getUser()->rolename != 'admins') {
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
        
        $html = $this->generateReport($pos);
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";


        $this->detail->setVisible(true);        
        
    }
        
        
    public function generateReport($pos) {
        $vk = new \App\Modules\VK\VK($pos->vktoken) ;
        
        $ret = $vk->GetRep();
        if( !is_array($ret)){
            $this->setError($ret) ;
            return;
        }    
       
        $header = [];
        $header['created_at'] = H::fdt(time() );
        $header['cnt'] =$ret['receipt']['count_p'];
        $header['rcnt'] =$ret['receipt']['count_m'];
        $header['nal'] =0;
        $header['card'] =0;
        $header['rnal'] =0;
        $header['rcard'] =0;
        
        
        foreach($ret['pays'] as $p){
             if($p['type']==0) {
                $header['nal'] += doubleval($p['sum_p'] ) ;
                $header['rnal'] += doubleval($p['sum_m'] ) ;
             }
             if($p['type']==1) {
                $header['card'] += doubleval($p['sum_p'] ) ;
                $header['rcard'] += doubleval($p['sum_m'] ) ;
             }
        }
       
        $header['total'] = H::fa($header['nal'] + $header['card'] - $header['rnal'] - $header['rcard']  );
        $header['isrnal'] = $header['rnal'] > 0;
        $header['isrcard'] = $header['rcard'] > 0;
        $header['nal'] = H::fa($header['nal']);
        $header['card'] =H::fa($header['card']);
        $header['rnal'] =H::fa($header['rnal']);
        $header['rcard'] =H::fa($header['rcard']);
         
        $report = new \App\Report('report/vk_xrep.tpl');

        $html = $report->generate($header);

        return $html;
    }
 

 

}

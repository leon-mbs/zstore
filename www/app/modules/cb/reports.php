<?php

namespace App\Modules\CB;

use App\Application as App;
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

 
class Reports extends \App\Pages\Base
{
 
    public function __construct() {
        parent::__construct();


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
        $cb = new \App\Modules\CB\CheckBox($pos->cbkey, $pos->cbpin) ;
        
        $ret = $cb->GetReports();
        if( !is_array($ret)){
            $this->setError($ret) ;
            return;
        }    
        $header = [];
        $header['created_at'] = H::fdt(  strtotime($ret['created_at'] ) );
        $header['cnt'] =$ret['sell_receipts_count'];
        $header['nal'] =0;
        $header['card'] =0;
        $header['rnal'] =0;
        $header['rcard'] =0;
        
        
        foreach($ret['payments'] as $p){
             if($p['type']=='CASH') {
                $header['nal'] += doubleval($p['sell_sum']/100) ;
                $header['rnal'] += doubleval($p['return_sum']/100) ;
             }
             if($p['type']=='CASHLESS') {
                $header['card'] += doubleval($p['sell_sum']/100) ;
                $header['rcard'] += doubleval($p['return_sum']/100) ;
             }
        }
       
        $header['total'] = H::fa($header['nal'] + $header['card'] - $header['rnal'] - $header['rcard']  );
        $header['isrnal'] = $header['rnal'] > 0;
        $header['isrcard'] = $header['rcard'] > 0;
        $header['nal'] = H::fa($header['nal']);
        $header['card'] =H::fa($header['card']);
        $header['rnal'] =H::fa($header['rnal']);
        $header['rcard'] =H::fa($header['rcard']);
         
        $report = new \App\Report('report/cb_report.tpl');

        $html = $report->generate($header);

        return $html;
    }
 

 

}

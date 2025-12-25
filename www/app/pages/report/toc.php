<?php

namespace App\Pages\Report;

use App\Helper as H;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

 
class Toc extends \App\Pages\Base
{
    private $_cci = array();


    public function __construct() {
        parent::__construct();

        if (false == \App\ACL::checkShowReport('Toc')) {
            return;
        }

        $this->add(new Form('filter'));
        $this->filter->add(new DropDownChoice('period', [], 1));
        $this->filter->add(new SubmitButton('start' ))->onClick($this, 'OnSubmit');
         
        $this->add(new Panel('detail'))->setVisible(false);

    
        $this->detail->add(new Label('preview'));


    }


    public function OnSubmit($sender) {

        $html = $this->generateReport();
        $this->detail->preview->setText($html, true);
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";


        $this->detail->setVisible(true);
    }

    private function generateReport() {
        
        $conn = \ZDB\DB::getConnect();
 

        $period = (int)$this->filter->period->getValue();
        $end=strtotime('-7 day') ;
        $start=strtotime("-{$period} month",$start) ;
      
        $from = $conn->DBDate($start); 
        $to = $conn->DBDate($end); 
        
           //Актуальність складів
        $detail1=[] ;
      
        $header = array(
           "_detail1" => $detail1,
           "isdetail1" => count($detail1) > 0


                        
        );
        $report = new \App\Report('report/toc.tpl');

        $html = $report->generate($header);

        return $html;
    }

 

}

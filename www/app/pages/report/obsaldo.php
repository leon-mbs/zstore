<?php

namespace App\Pages\Report;

use App\Application as App;
use App\Entity\Account;
use App\Entity\AccEntry;

use App\Helper as H;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Label;
use Zippy\Html\Link\RedirectLink;
use Zippy\Html\Panel;

/**
 * Оборотно-сальдовая  ведомость
 */
class ObSaldo extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('ObSaldo')) {
            return;
        }

        $this->add(new Form('filter'));
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));

        $this->filter->add(new SubmitButton('show'))->onClick($this, 'OnSubmit');

        $this->add(new Panel('detail'))->setVisible(false);

        $this->detail->add(new Label('preview'));
        \App\Session::getSession()->issubmit = false;
    }

 
 

    public function OnSubmit($sender) {


        $this->detail->setVisible(true);

        $html = $this->generateReport();
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";
        $this->detail->preview->setText($html, true);

              

    }

    private function generateReport() {

        $conn = \ZDB\DB::getConnect();
        $from = $this->filter->from->getDate();
        $to = $this->filter->to->getDate();
       
        $bdt=0;
        $bct=0;
        $edt=0;
        $ect=0;
       
        $an = Account::getWithEntry();
        $acclist = Account::getList();
        $acclist=array_keys($acclist)  ;
   
       
        $detail=[];
         
        foreach($acclist as $acc){
            
           $s=Account::getSaldo($acc,$from); 
           $o=Account::getOb($acc,$from,$to); 
           if($s['dt']==0 && $s['ct']==0 && $o['dt']==0 && $o['ct']==0 ) {
               continue;
           }
           $row =  [
           'acc'=>$acc,
           'startdt'=>H::fa($s['dt']),
           'startct'=>H::fa($s['ct']),
           'amountdt'=>H::fa($o['dt']),
           'amountct'=>H::fa($o['ct']) 
           
           ];  
           $enddt =0;
           $endct =0;
           
           if ($o['dt'] > 0) {
                $amountdt = $o['dt'];
                $enddt += $s['dt'] + $o['dt'];
                $endct += $s['ct'];
            }
            if ($o['ct'] > 0) {
                $amountct = $o['ct'];
                $endct += $s['ct'] + $o['ct'];
                $enddt += $s['dt'];
            }

            if ($enddt - $endct > 0) {
                $enddt = $enddt - $endct;
                $endct = 0;
            } else {
                $endct = $endct - $enddt;
                $enddt = 0;
            }   
            
           $row['enddt'] = $enddt;
           $row['endct'] = $endct;
                    
           $detail[]= $row;
           if(in_array($acc,$an)){
               $bdt +=$s['dt'];
               $bct +=$s['ct'];
               $edt +=$enddt;
               $ect +=$endct;
           }
                
        }       
        
          if ($edt - $ect > 0) {
                $edt = $edt - $ect;
                $ect = 0;
            } else {
                $ect = $ect - $edt;
                $edt = 0;
            }            
           if ($bdt - $bct > 0) {
                $bdt = $bdt - $bct;
                $bct = 0;
            } else {
                $bct = $bct - $bdt;
                $bdt = 0;
            }


        $header = array('datefrom'      =>H::fd($from),
                        "_detail"       => $detail,
                        "bdt"       => $bdt >0 ? H::fa($bdt) :'',
                        "bct"       => $bct >0 ? H::fa($bct):'',
                        "edt"       => $edt >0 ? H::fa($edt):'',
                        "ect"       => $ect >0 ? H::fa($ect):'',
                        'dateto'    => H::fd($to)) ;
 

        $report = new \App\Report('report/obsaldo.tpl');

        $html = $report->generate($header);

        return $html;
    }

 


}

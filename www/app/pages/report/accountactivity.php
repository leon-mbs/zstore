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
 * Движение по  счету
 */
class AccountActivity extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('AccountActivity')) {
            return;
        }

        $this->add(new Form('filter'));
        $this->filter->add(new Date('from', time() - (7 * 24 * 3600)));
        $this->filter->add(new Date('to', time()));

        $list=Account::     getUsedList(true);
        
       
        
        $this->filter->add(new DropDownChoice('acc', $list ));
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
         
        $acc = $this->filter->acc->getValue();
        $acc=$conn->qstr($acc);

        $w = "";
        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $w .= " and " . $c;
        }        
        
    
        $from_ = $this->filter->from->getDate();
        $to_ = $this->filter->to->getDate();
        $from = $conn->DBDate($from_) ;
        $to = $conn->DBDate($to_)  ;

        
        $detail = array();
        
        $sql = "select coalesce(sum(amount),0) from  acc_entry_view where accdt={$acc} and createdon < {$from} {$w}";
        $startdt = $conn->GetOne($sql);
        $sql = "select coalesce(sum(amount),0) from  acc_entry_view where accct={$acc} and createdon < {$from} {$w}" ;
        $startct = $conn->GetOne($sql);
        
        //сворачиваем
        if($startdt > $startct) {
           $startdt = $startdt- $startct; 
           $startct=0;
        }   else {
           $startct = $startct- $startdt; 
           $startdt=0;
            
        }
        
        
//        GROUP_CONCAT(distinct document_number) as docs
     
     
        $sql = "select coalesce(sum(case when accdt = {$acc} then amount else 0 end ) ,0) as ad,
        coalesce(sum(case when accct = {$acc} then amount else 0 end ) ,0) as ac ,  createdon 
        from acc_entry_view  where (accdt={$acc} or accct={$acc} ) and createdon >= {$from} and createdon <= {$to} {$w} group by  createdon  order  by  createdon    ";

        $rs = $conn->Execute($sql);
       

        foreach ($rs as $row) {

            $amountdt = 0;
            $amountct = 0;
            $enddt = 0;
            $endct = 0;
            if ($row['ad'] > 0) {
                $amountdt = $row['ad'];
                $enddt = $startdt + $row['ad'];
                $endct = $startct;
            }
            if ($row['ac'] > 0) {
                $amountct = $row['ac'];
                $endct = $startct + $row['ac'];
                $enddt = $startdt;
            }

            if ($enddt - $endct > 0) {
                $enddt = $enddt - $endct;
                $endct = 0;
            } else {
                $endct = $endct - $enddt;
                $enddt = 0;
            }                             

            $detail[] = array(
                "date" => H::fd(strtotime($row['createdon'])),
               
                "amountdt" => H::fa($amountdt),
                "amountct" => H::fa($amountct),
                "startdt" => H::fa($startdt),
                "startct" => H::fa($startct),
                "enddt" => H::fa($enddt),
                "endct" => H::fa($endct)
            );

            $startdt = $enddt;
            $startct = $endct;



        }


        $header = array('datefrom'      =>H::fd($from_),
                        "_detail"       => $detail,
                        "acc"       => $this->filter->acc->getValueName(),
                        'dateto'        => H::fd($to_)) ;
 

        $report = new \App\Report('report/accountactivity.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function getData() {


        $html = $this->generateReport();
        \App\Session::getSession()->printform = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $html . "</body></html>";

        return $html;

    }


}

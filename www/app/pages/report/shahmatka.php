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
 * Шахматная  ведомость
 */
class Shahmatka extends \App\Pages\Base
{
    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowReport('Shahmatka')) {
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
        
        $sql="CREATE TABLE if not exists acc_entry_tmp (
          id int NOT NULL AUTO_INCREMENT,
          accdt varchar(5)  NULL,
          accct varchar(5)  NULL,
          amount decimal(12, 2) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MEMORY DEFAULT CHARSET=utf8  ";
        $conn->Execute($sql);
        $conn->Execute("delete from acc_entry_tmp");
        
        $from_ = $this->filter->from->getDate();
        $to_ = $this->filter->to->getDate();
       
        $to = $conn->DBDate($to_);
        $from = $conn->DBDate($from_);
        
        $w = " createdon >= {$from}  and createdon <= {$to} ";
        $bc = \App\ACL::getBranchConstraint();
        if (strlen($bc) > 0) {
            $w .= " and " . $bc  ;
        }       
        $sql =" INSERT  INTO  acc_entry_tmp (accdt,accct,amount)   
                select accdt,accct, coalesce( SUM(amount),0) as am FROM acc_entry_view where {$w}  GROUP  by  accdt,accct ";
                
        $conn->Execute($sql);
         
        
       $acclist = Account::getList();
       $acclist=array_keys($acclist)  ;

        $detail = array();
       
        $top = array(array('cell' => ''));
      
        $bottom = array(array('cell' => 'Кредит', 'bold' => true));


        $from = strtotime($this->filter->from->getValue());
        $to = strtotime($this->filter->to->getValue());

        foreach ($acclist as $acc) {

            $data = Account::getOb($acc,$from, $to);  //получаем остатки  и  обороты  на  период

            $top[] = array('cell' => $acc,   'right' => true,   'bold' => true);

            $bottom[] = array('cell' => H::fa($data['ct']),'right' => true, 'bold' => true);
        }
        $top[] = array('cell' => 'Дебет', 'right' => true, 'bold' => true);
        $bottom[] = array('cell' => '');
     

        $detail[] = array('row' => $top);
        foreach ($acclist as $acc) {
            $arr = array();
            $data = Account::getOb($acc,$from, $to);  //получаем остатки  и  обороты  на  период
            $arr[] = array('cell' => $acc,  'bold' => true);

            foreach ($acclist as $acc2) {

               $acc_dt = $conn->qstr($acc . '%');
               $acc_ct = $conn->qstr($acc2 . '%');

               $sql = "select coalesce(sum(amount),0) from   acc_entry_tmp where   accdt like {$acc_dt} and  accct like {$acc_ct} "  ;

               $am = $conn->GetOne($sql);
                
               $arr[] = array('cell' => H::fa($am),'right' => true,);
            }
            $arr[] = array('cell' => H::fa($data['dt']),'right' => true, 'bold' => true);

            $detail[] = array('row' => $arr);
        }
        $detail[] = array('row' => $bottom);

        $header = array(
            "_detail" => $detail,
            'from' => H::fd($from_),
            'to' =>  H::fd($to_),
            'cols' => count($acclist) +2
        );
       

        $report = new \App\Report('report/shahmatka.tpl');

        $html = $report->generate($header);

        return $html;
    }

  


}
